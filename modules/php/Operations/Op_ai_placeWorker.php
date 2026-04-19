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
 * AI Worker Placement
 *
 * RULES:
 * The AI places Green Workers on Townsfolk Cards, Yellow Workers on Land Cards,
 * and Blue Workers on Water Cards. They resolve all printed actions of a space
 * when placing a Worker.
 *
 * If given the option of two Workers, the AI will prioritize placing a Green Worker.
 *
 * Params from scheme cards define available worker colors:
 *   ai_placeWorker(green)           — only green
 *   ai_placeWorker(green/blue)      — green preferred, blue fallback
 *   ai_placeWorker(green/yellow)    — green preferred, yellow fallback
 */
class Op_ai_placeWorker extends AiOperation {
    /**
     * Get allowed worker colors from operation params, in priority order.
     * Params are like "green", "green/blue", "green/yellow"
     */
    function getAllowedWorkerColors(): array {
        $params = $this->getParams();
        if (!$params) {
            // Default: green priority, then all others
            return ["green", "blue", "yellow"];
        }
        return explode("/", $params);
    }

    /**
     * Find an available worker of the given color in AI's supply
     */
    function findWorkerInSupply(string $color): ?string {
        $owner = $this->getOwner();
        $workers = $this->game->tokens->getTokensOfTypeInLocation("worker_$color", "tableau_$owner");
        if (empty($workers)) {
            return null;
        }
        return array_key_first($workers);
    }

    /**
     * Get mainarea cards of the given type, sorted by token_state (position 1-4)
     */
    function getMainareaCards(string $cardType): array {
        return $this->game->tokens->getTokensOfTypeInLocation("card_$cardType", "mainarea", null, "token_state");
    }

    /**
     * Select which card to place the worker on using positional priority.
     * Respects the denied list for denied cards.
     */
    function selectTargetCard(string $cardType, string $workerColor = ""): ?string {
        $cards = $this->getMainareaCards($cardType);
        if (empty($cards)) {
            return null;
        }

        // Build position-indexed array (0-3) with card keys, null for invalid/filtered positions
        $skip = $this->getDataField("denied", []);
        $positions = [null, null, null, null];
        foreach ($cards as $cardKey => $info) {
            $pos = (int) $info["state"] - 1; // Convert 1-based state to 0-based index
            $positions[$pos] = $cardKey;
        }

        // Null out denied cards
        if (!empty($skip)) {
            foreach ($positions as $i => $cardKey) {
                if ($cardKey !== null && in_array($cardKey, $skip)) {
                    $positions[$i] = null;
                }
            }
            // Cannot fully deny — reset if all nulled
            if (array_filter($positions) === []) {
                foreach ($cards as $cardKey => $info) {
                    $positions[(int) $info["state"] - 1] = $cardKey;
                }
            }
        }

        // Null out cards that already have a worker of the same color
        if ($workerColor) {
            foreach ($positions as $i => $cardKey) {
                if ($cardKey !== null) {
                    $workers = $this->game->tokens->getTokensOfTypeInLocation("worker_$workerColor", $cardKey);
                    if (!empty($workers)) {
                        $positions[$i] = null;
                    }
                }
            }
            if (array_filter($positions) === []) {
                return null;
            }
        }

        $prio = $this->getPositionPriority();
        $dir = $this->getNextPositionPriorityDirection($prio);
        $sorted = custom_array_rotate($positions, $prio - 1, $dir);

        return reset($sorted) ?: null;
    }

    /**
     * Commit worker placement: handle influence, place worker, queue board action.
     */
    function commitPlacement(string $worker, string $targetCard, string $cardType): void {
        $owner = $this->getOwner();

        // Handle influence interaction (commit phase — influence return only, no player choice)
        $this->queue("ai_cardInteract", $owner, ["card" => $targetCard, "buy" => false]);

        // Place the worker on the card. State=1 marks it as placed-this-turn so the AI
        // cannot retrieve it or acquire this card later in the same turn (RULES.md line 252).
        // Op_turn::auto clears the marker at the start of the next turn.
        $state = (int) $this->game->tokens->db->getTokenState($targetCard);
        $this->dbSetTokenLocation(
            $worker,
            $targetCard,
            1,
            clienttranslate('${player_name} places ${token_name} on ${card_type} position ${pos}'),
            ["pos" => $state, "card_type" => $this->game->getTokenName($cardType)]
        );

        // Resolve the printed action of the space
        $workerRule = $this->game->getRulesForAndAssert("action_{$cardType}_{$state}", "r", "");
        $this->queue($workerRule);
    }

    public function isVoid(): bool {
        // Check if we can place any worker at all
        $colors = $this->getAllowedWorkerColors();
        foreach ($colors as $color) {
            $worker = $this->findWorkerInSupply($color);
            if ($worker === null) {
                continue;
            }
            $cardType = self::COLOR_CARD_TYPE[$color] ?? null;
            if ($cardType === null) {
                continue;
            }
            $cards = $this->getMainareaCards($cardType);
            if (!empty($cards)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Auto-resolve: AI places a worker
     */
    public function auto(): bool {
        $owner = $this->getOwner();

        // If a card was already confirmed (player allowed interaction), commit directly
        $confirmedCard = $this->getDataField("confirmed_card");
        $confirmedWorker = $this->getDataField("confirmed_worker");
        $confirmedCardType = $this->getDataField("confirmed_card_type");
        if ($confirmedCard && $confirmedWorker && $confirmedCardType) {
            $this->commitPlacement($confirmedWorker, $confirmedCard, $confirmedCardType);
            return true;
        }

        $colors = $this->getAllowedWorkerColors();

        foreach ($colors as $color) {
            $worker = $this->findWorkerInSupply($color);
            if ($worker === null) {
                continue;
            }

            $cardType = self::COLOR_CARD_TYPE[$color] ?? null;
            $this->game->systemAssert("Invalid worker color $color", $cardType);

            $targetCard = $this->selectTargetCard($cardType, $color);
            if ($targetCard === null) {
                continue;
            }

            // Check for opponent influence before committing
            $inf = $this->game->tokens->getTokensOfTypeInLocation("influence", $targetCard);
            $infKey = array_key_first($inf);
            if ($infKey) {
                $infOwner = getPart($infKey, 1);
                if ($infOwner !== $owner) {
                    // Opponent influence found — ask player to allow or deny
                    $this->queue("ai_cardInteractChoice", $infOwner, [
                        "card" => $targetCard,
                        "caller" => $this->getTypeFullExpr(),
                        "caller_data" => [
                            "denied" => $this->getDataField("denied", []),
                            "buy" => false,
                            "confirmed_card" => $targetCard,
                            "confirmed_worker" => $worker,
                            "confirmed_card_type" => $cardType,
                        ],
                    ]);
                    return true;
                }
            }

            // No opponent influence — commit immediately
            $this->commitPlacement($worker, $targetCard, $cardType);
            return true;
        }

        // No valid worker placement found
        $this->notifyMessage(clienttranslate('${player_name} cannot place a worker'));
        return true;
    }
}
