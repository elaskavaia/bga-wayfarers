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
     * Uses sum value of most recent scheme cards to determine position.
     */
    function selectTargetCard(string $cardType): ?string {
        $cards = $this->getMainareaCards($cardType);
        if (empty($cards)) {
            return null;
        }

        $prio = $this->getPositionPriority();
        $cardKeys = array_keys($cards);
        $dir = $this->getNextPositionPriorityDirection($prio);
        $sorted = custom_array_rotate($cardKeys, $prio - 1, $dir);

        return $sorted[0];
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
        $colors = $this->getAllowedWorkerColors();

        foreach ($colors as $color) {
            $worker = $this->findWorkerInSupply($color);
            if ($worker === null) {
                continue;
            }

            $cardType = self::COLOR_CARD_TYPE[$color] ?? null;
            $this->game->systemAssert("Invalid worker color $color", $cardType);

            $targetCard = $this->selectTargetCard($cardType);
            if ($targetCard === null) {
                continue;
            }

            // Handle influence interaction if there's influence on the card
            $this->queue("ai_cardInteract", $owner, ["card" => $targetCard, "buy" => false]);

            // Place the worker on the card
            $state = (int) $this->game->tokens->db->getTokenState($targetCard);
            $this->dbSetTokenLocation(
                $worker,
                $targetCard,
                0,
                clienttranslate('${player_name} places ${token_name} on ${card_type} position ${pos}'),
                ["pos" => $state, "card_type" => $this->game->getTokenName($cardType)]
            );

            // Resolve the printed action of the space
            $workerRule = $this->game->getRulesForAndAssert("action_{$cardType}_{$state}", "r", "");
            $this->queue($workerRule);

            return true;
        }

        // No valid worker placement found
        $this->notifyMessage(clienttranslate('${player_name} cannot place a worker'));
        return true;
    }
}
