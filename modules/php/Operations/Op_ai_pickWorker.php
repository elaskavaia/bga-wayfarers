<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * implementation : © Alena Laskavaia <laskava@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 */

declare(strict_types=1);

namespace Bga\Games\wayfarers\Operations;

use Bga\Games\wayfarers\OpCommon\AiOperation;

use function Bga\Games\wayfarers\custom_array_rotate;
use function Bga\Games\wayfarers\getPart;

/**
 * AI Worker Retrieval
 *
 * RULES:
 * - Prioritize Green Workers first
 * - Then use resource track color priority for worker color
 * - Use sum value to choose among multiple workers of same color
 */
class Op_ai_pickWorker extends AiOperation {
    // Worker color priority: green first, then resource track priority
    const WORKER_COLORS = ["green", "blue", "yellow"];

    /**
     * Get available workers on cards, grouped by color.
     * Filters out workers on denied cards.
     */
    function getAvailableWorkersByColor(): array {
        $workersByColor = [];
        $denied = $this->getDataField("denied", []);

        // Get all public workers on cards
        $publicWorkers = $this->game->tokens->getTokensOfTypeInLocation("worker", "card_%");

        foreach ($publicWorkers as $key => $worker) {
            // Filter out workers on denied cards
            if (!empty($denied) && in_array($worker["location"], $denied)) {
                continue;
            }

            // Exclude workers placed this turn (state=1) — RULES.md line 252.
            if ((int) $worker["state"] !== 0) {
                continue;
            }

            $color = getPart($key, 1);

            if (!isset($workersByColor[$color])) {
                $workersByColor[$color] = [];
            }
            $workersByColor[$color][$key] = $worker;
        }

        return $workersByColor;
    }

    /**
     * Get worker color priority: green first, then resource track priority
     */
    function getWorkerColorPriority(): array {
        // Start with green priority
        $priority = ["green"];

        // Add resource track color priority (excluding green which is already first)
        $resourcePriority = $this->getColorPriority();
        foreach ($resourcePriority as $color) {
            if ($color !== "green" && $color !== "black") {
                // Only blue and yellow workers exist (no black workers)
                $priority[] = $color;
            }
        }

        return $priority;
    }

    /**
     * Select which worker to pick based on color and positional priority
     */
    function selectWorker(): ?string {
        $workersByColor = $this->getAvailableWorkersByColor();
        if (empty($workersByColor)) {
            return null;
        }

        $colorPriority = $this->getWorkerColorPriority();

        // Try each color in priority order
        foreach ($colorPriority as $color) {
            if (!isset($workersByColor[$color]) || empty($workersByColor[$color])) {
                continue;
            }

            $workers = $workersByColor[$color];

            // If only one worker of this color, pick it
            if (count($workers) === 1) {
                return array_key_first($workers);
            }

            // Multiple workers of same color - use positional priority
            // Get card positions for each worker
            $workersByPosition = [];
            foreach ($workers as $workerKey => $workerInfo) {
                $cardLocation = $workerInfo["location"];
                // Get card state (position) from the card token
                $cardState = (int) $this->game->tokens->db->getTokenState($cardLocation);
                $workersByPosition[$cardState][$workerKey] = $workerInfo;
            }

            // Build 4-slot position array with nulls for empty positions
            $positions = [null, null, null, null];
            foreach ($workersByPosition as $pos => $workersAtPos) {
                $positions[$pos - 1] = $pos; // Convert 1-based to 0-based index
            }

            // Use sum value for positional selection
            $prio = $this->getPositionPriority();
            $dir = $this->getNextPositionPriorityDirection($prio);
            $sortedPositions = custom_array_rotate($positions, $prio - 1, $dir);

            // Return first worker at selected position
            $selectedPosition = reset($sortedPositions);
            return array_key_first($workersByPosition[$selectedPosition]);
        }

        return null;
    }

    /**
     * Commit worker retrieval: handle influence and move worker to AI tableau.
     */
    function commitPick(string $workerKey, string $card): void {
        $owner = $this->getOwner();

        // Handle influence interaction (commit phase — influence return only, no player choice)
        $this->queue("ai_cardInteract", $owner, ["card" => $card, "buy" => false]);

        // Move worker to AI's tableau
        $this->dbSetTokenLocation($workerKey, "tableau_$owner", 0, clienttranslate('${player_name} picks ${token_name}'));
    }

    public function isVoid(): bool {
        // Check if there are any workers available to pick
        return $this->selectWorker() === null;
    }

    /**
     * Auto-resolve: AI picks a worker
     */
    public function auto(): bool {
        $owner = $this->getOwner();

        // If already confirmed (player allowed interaction), commit directly
        $confirmedWorker = $this->getDataField("confirmed_worker");
        $confirmedCard = $this->getDataField("confirmed_card");
        if ($confirmedWorker && $confirmedCard) {
            $this->commitPick($confirmedWorker, $confirmedCard);
            return true;
        }

        $workerKey = $this->selectWorker();

        if ($workerKey === null) {
            // If denied list is non-empty and no workers available, reset and retry
            $denied = $this->getDataField("denied", []);
            if (!empty($denied)) {
                $this->withDataField("denied", []);
                $workerKey = $this->selectWorker();
            }
            if ($workerKey === null) {
                $this->notifyMessage(clienttranslate('${player_name} cannot pick any worker'));
                return true;
            }
        }

        // Get the card the worker is on
        $workerInfo = $this->game->tokens->db->getTokenInfo($workerKey);
        $card = $workerInfo["location"];

        // Check for opponent influence before committing
        $inf = $this->game->tokens->getTokensOfTypeInLocation("influence", $card);
        $infKey = array_key_first($inf);
        if ($infKey) {
            $infOwner = getPart($infKey, 1);
            if ($infOwner !== $owner) {
                // Opponent influence found — ask player to allow or deny
                $this->queue("ai_cardInteractChoice", $infOwner, [
                    "card" => $card,
                    "caller" => $this->getTypeFullExpr(),
                    "caller_data" => [
                        "denied" => $this->getDataField("denied", []),
                        "buy" => false,
                        "confirmed_worker" => $workerKey,
                        "confirmed_card" => $card,
                    ],
                ]);
                return true;
            }
        }

        // No opponent influence — commit immediately
        $this->commitPick($workerKey, $card);
        return true;
    }
}
