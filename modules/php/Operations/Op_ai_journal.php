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

/**
 * AI Journaling
 *
 * RULES (from RULES.md lines 318-333):
 *
 * The AI's path on the Journal Track is dictated by the color of all faceup Scheme Cards:
 * - Majority Blue Scheme Cards: AI takes the higher path.
 * - Majority Red Scheme Cards: AI takes the lower path.
 * - Equal number of Blue and Red Scheme Cards: AI follows the color of the most recently revealed Scheme Card.
 *
 * If only one path is available, the AI takes that path.
 * In the last column of the Journal Track, the AI will never take the middle option.
 * If the space they would move into is blocked, they move to the other available space in the final column.
 *
 * Before Journaling, check the AI's position relative to your Player Marker:
 * - If the AI is behind, they spend one Black Influence to move an extra space if possible.
 * - If the AI is in the same column, this costs two Black Influence.
 * - If the AI is ahead, this costs three Black Influence.
 *
 * The AI ignores all costs/requirements on the Journal Track but gains all rewards.
 * In the final column, they gain a Pink Upgrade Tile instead of an Inspiration Card.
 */
class Op_ai_journal extends AiOperation {
    /**
     * Determine path preference based on faceup scheme card colors
     * @return string "blue" or "red"
     */
    function getPathPreference(): string {
        $owner = $this->getOwner();
        $cards = $this->game->tokens->getTokensOfTypeInLocation("card_scheme", "tableau_$owner", null, "token_state");

        $blueCount = 0;
        $redCount = 0;
        $mostRecentColor = "blue";

        foreach ($cards as $cardId => $cardInfo) {
            $color = $this->game->getRulesFor($cardId, "t", "blue");
            if ($color === "blue") {
                $blueCount++;
            } else {
                $redCount++;
            }
            $mostRecentColor = $color; // last one in sorted array
        }

        if ($blueCount > $redCount) {
            return "blue";
        } elseif ($redCount > $blueCount) {
            return "red";
        } else {
            return $mostRecentColor;
        }
    }

    /**
     * Get available journal positions AI can move to
     */
    function getPossibleMoves() {
        $owner = $this->getOwner();
        $markerId = "marker_$owner";
        $currentState = (int) $this->game->tokens->db->getTokenState($markerId);
        $conn = $this->game->getRulesFor("jpos_$currentState", "conn", "");

        $res = [];
        if ($conn === "") {
            return $res; // Already at terminal position
        }

        $positions = explode(",", (string) $conn);
        foreach ($positions as $pos) {
            $pos = (int) trim($pos);
            $connector = $this->getConnectorId($currentState, $pos);

            // AI always marks as OK - ignores requirements
            $res["jpos_$pos"] = [
                "pos" => $pos,
                "connector" => $connector,
            ];
        }
        return $res;
    }

    function getConnectorId(int $currentState, int $newState) {
        // board part number is the same for both sides
        $boardPart = $this->game->getRulesFor("jconn_{$currentState}_{$newState}_0", "location", "mainboard_1");
        $side = (int) $this->game->tokens->db->getTokenState($boardPart);
        $connector = "jconn_{$currentState}_{$newState}_{$side}";
        return $connector;
    }
    /**
     * Select journal position based on path preference.
     * Positions are ordered as listed in the conn field:
     * first = upper path, last = lower path.
     */
    function selectPosition(array $availablePositions): ?int {
        if (empty($availablePositions)) {
            return null;
        }

        $pathPreference = $this->getPathPreference();

        // If only one option, take it
        if (count($availablePositions) == 1) {
            return array_values($availablePositions)[0]["pos"];
        }

        // Connection order from getPossibleMoves preserves CSV conn field order:
        // first = upper/North, last = lower/South
        $positions = array_values(array_map(fn($item) => $item["pos"], $availablePositions));

        // Check if we're in final column (3 options)
        if (count($positions) == 3) {
            // AI never takes middle option in final column
            if ($pathPreference === "blue") {
                return $positions[0]; // first = upper/North
            } else {
                return $positions[2]; // last = lower/South
            }
        }

        // For 2 options, choose based on path preference
        if ($pathPreference === "blue") {
            return $positions[0]; // first = upper/North path
        } else {
            return $positions[1]; // last = lower/South path
        }
    }

    /**
     * Check if AI should spend black influence for extra journaling
     */
    function getBlackInfluenceAmountForDoubleAdvance(): int {
        $owner = $this->getOwner();
        $aiMarkerId = "marker_$owner";
        $aiState = (int) $this->game->tokens->db->getTokenState($aiMarkerId);

        // Get human player marker position
        $humanPlayerId = $this->game->getFirstPlayer();
        $humanMarkerId = "marker_" . $this->game->custom_getPlayerColorById($humanPlayerId);
        $humanState = (int) $this->game->tokens->db->getTokenState($humanMarkerId);

        // Extract column from position (rough approximation - positions increase as we progress)
        $aiColumn = $this->getColumn($aiState);
        $humanColumn = $this->getColumn($humanState);

        if ($aiColumn < $humanColumn) {
            // AI is behind
            return 1;
        } elseif ($aiColumn == $humanColumn) {
            // Same column
            return 2;
        } else {
            // AI is ahead
            return 3;
        }
    }

    /**
     * Journal column is determined by position number (every 10 positions is a new column, e.g., 0-9 = col 0, 10-19 = col 1, etc.)
     */
    function getColumn(int $pos): int {
        if ($pos == 0) {
            return 0;
        }
        return (int) floor($pos / 10);
    }

    function journalOneStep() {
        $owner = $this->getOwner();
        $markerId = "marker_$owner";
        $availablePositions = $this->getPossibleMoves();
        $selectedPos = $this->selectPosition($availablePositions);

        if ($selectedPos === null) {
            $this->notifyMessage(clienttranslate('${player_name} cannot journal (already at terminal position)'), []);
            return false;
        }

        // Move marker to selected position
        $this->dbSetTokenState($markerId, $selectedPos, clienttranslate('${player_name} journals to position ${num}'), [
            "num" => $selectedPos,
        ]);

        // Position Bonus
        $selected = "jpos_$selectedPos";
        $r = $this->game->getRulesForAndAssert($selected, "r");
        $r = str_replace("upgPink/cardInsp", "upgPink", $r);
        $this->queue($r, $owner, ["jpos" => $selected, "reason" => $selected]);

        // Check if end game is triggered (terminal position)
        $conn = $this->game->getRulesFor("jpos_$selectedPos", "conn", "");
        if ($conn === "") {
            $this->game->triggerEndGame($this->getPlayerId());
            return false; // cannot journal again
        }

        return true;
    }

    /**
     * Auto-resolve: AI journals
     */
    public function auto(): bool {
        if ($this->isFinal()) {
            $this->journalOneStep();
            return true;
        }

        $owner = $this->getOwner();
        // Attempt to spend black influence for extra journaling
        $requiredBlackInf = $this->getBlackInfluenceAmountForDoubleAdvance();
        $blackInf = $this->game->countGuildInfluence("guild_black", $owner);

        $more = $this->journalOneStep();

        if ($more && $blackInf >= $requiredBlackInf) {
            // Spend black influence and journal extra space
            $this->notifyMessage(clienttranslate('${player_name} journals an extra space'));
            $this->queue("{$requiredBlackInf}n_infBlack");
            $this->queue("ai_journal", $owner, ["final" => 1]);
        }

        return true;
    }

    function isFinal(): bool {
        return $this->getDataField("final", 0) == 1;
    }
}
