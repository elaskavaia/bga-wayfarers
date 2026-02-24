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

use Bga\Games\wayfarers\Material;
use Bga\Games\wayfarers\OpCommon\Operation;

use function Bga\Games\wayfarers\getPart;

class Op_journal extends Operation {
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

            $achived = false;
            $prereq = $this->game->getRulesFor($connector, "r", "");

            if (!$prereq) {
                $achived = false;
            } elseif (str_starts_with($prereq, "Op_")) {
                // Operations are costs (e.g., Op_n_infBlack, Op_n_infBlue,Op_n_infYellow)
                $ops = explode(",", $prereq);
                $achived = true;
                $icons = [];
                foreach ($ops as $op) {
                    $op = trim($op);
                    if (preg_match('/^Op_n_inf(\w+)$/', $op, $m)) {
                        $color = lcfirst($m[1]);
                        if ($this->game->countGuildInfluence("guild_$color", $owner) <= 0) {
                            $achived = false;
                        }
                        $icons[] = "[wicon_inf_{$color}_pay]";
                    } else {
                        $this->game->systemAssert("unsupported operation $op");
                    }
                }
                $name = implode("", $icons);
            } else {
                $required = (int) $this->game->getRulesFor($connector, "gw", 1);
                $count = $this->game->evaluateExpression($prereq, $owner);

                if ($count >= $required) {
                    $achived = true;
                }
                $givenName = $this->game->getRulesFor($connector, "name", "");
                if ($givenName) {
                    $name = $givenName;
                } else {
                    $icon = $this->game->getRulesFor($prereq, "type", "?");
                    $name = "$required [$icon]"; // extact icon from prereq tag
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
        if ($this->noValidTargets()) {
            return true;
        }
        return false;
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
        $connector = $this->getConnectorId($currentState, $newState);
        $r = $this->game->getRulesFor($connector, "r", "");
        if (str_starts_with($r, "Op_")) {
            foreach (explode(",", $r) as $op) {
                $this->queue(substr(trim($op), 3), $owner);
            }
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

    public function getIconicName() {
        return "[wicon_journal]";
    }
}
