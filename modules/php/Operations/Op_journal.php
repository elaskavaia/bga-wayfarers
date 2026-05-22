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

use Bga\Games\wayfarers\Game;
use Bga\Games\wayfarers\Material;
use Bga\Games\wayfarers\OpCommon\Operation;

use function Bga\Games\wayfarers\getPart;

class Op_journal extends Operation {
    public function auto(): bool {
        if ($this->getPlayerId() == Game::PLAYER_AUTOMA) {
            $this->queue("ai_" . $this->getType(), $this->game->getAutomaColor());
            return true;
        }
        return parent::auto();
    }
    function getPossibleMoves() {
        $owner = $this->getOwner();
        $markerId = "marker_$owner";
        $currentState = (int) $this->game->tokens->db->getTokenState($markerId);
        $conn = $this->game->getRulesFor("jpos_$currentState", "conn", "");

        $res = [];
        if ($conn === "") {
            return $res;
        }

        $positions = explode(",", (string) $conn);
        foreach ($positions as $pos) {
            $pos = (int) trim($pos);
            $connector = $this->getConnectorId($currentState, $pos);
            $name = $pos;

            if ($this->game->isJournalSpaceBlocked($pos)) {
                $res["jpos_$pos"] = [
                    "q" => Material::ERR_OCCUPIED,
                    "name" => $pos,
                    "token_id" => $connector,
                ];
                continue;
            }

            $achived = false;
            $prereq = $this->game->getRulesFor($connector, "r", "");

            if (str_starts_with($prereq, "Op")) {
                // Operations are costs (e.g., Op(n_infBlack), Op(n_infBlue,n_infYellow))
                $op = $this->instanciateOperation(trim(substr($prereq, 2), "()"));
                $achived = !$op->isVoid();
                $name = $op->getIconicName();
            } elseif ($prereq) {
                $required = (int) $this->game->getRulesFor($connector, "gw", 1);
                $achived = $this->game->evaluateExpression($prereq, $owner) >= $required;
                $name = $this->game->getRulesFor($connector, "name", "");
                if (!$name) {
                    $icon = $this->game->getRulesFor($prereq, "type", "?");
                    $name = "$required [$icon]";
                }
            }

            $res["jpos_$pos"] = [
                "q" => $achived ? Material::RET_OK : Material::ERR_PREREQ,
                "name" => $name,
                "r" => $prereq,
                "token_id" => $connector,
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

    public function getPrompt() {
        return clienttranslate("Select a journal position to move to via connection");
    }

    public function canSkip() {
        return true;
    }

    public function requireConfirmation() {
        return true;
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $markerId = "marker_$owner";

        // Get user selected position (e.g., "jpos_15")
        $selected = $this->getCheckedArg();
        $currentState = (int) $this->game->tokens->db->getTokenState($markerId);
        $newState = (int) getPart($selected, 1);
        $this->game->userAssert(
            clienttranslate("That space is already occupied"),
            !$this->game->isJournalSpaceBlocked($newState)
        );
        $connector = $this->getConnectorId($currentState, $newState);
        $r = $this->game->getRulesFor($connector, "r", "");
        if (str_starts_with($r, "Op")) {
            $this->queue(trim(substr($r, 2), "()"));
        }
        // Update marker state
        $this->dbSetTokenState($markerId, $newState, clienttranslate('${player_name} journals to position ${num}'), [
            "num" => $newState,
        ]);

        // Position Bonus
        $r = $this->game->getRulesFor($selected);
        $this->queue($r, $owner, ["jpos" => $selected, "reason" => $selected]);

        /** @var Op_spendInfBlack */
        $op = $this->instanciateOperation("spendInfBlack");
        if (!$op->isBlackInfluenceSpentThisTurn()) {
            $this->queue("spendInfBlack");
        }

        // Check if end game is triggered (terminal position with no connections)
        $conn = $this->game->getRulesFor("jpos_$newState", "conn", "");
        if ($conn === "") {
            $this->game->triggerEndGame($this->getPlayerId());
        }
    }
}
