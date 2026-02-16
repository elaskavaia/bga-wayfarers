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

class Op_diceMod extends Operation {
    public function auto(): bool {
        if ($this->getPlayerId() == Game::PLAYER_AUTOMA) {
            // AI  gets infCard instead of dice mod
            $this->queue("infCard", $this->game->getAutomaColor());
            return true;
        }
        return parent::auto();
    }
    function getAllDice(): array {
        $owner = $this->getOwner();
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_$owner");

        // Group by value to avoid duplicates
        $res = [];
        foreach ($dice as $key => $die) {
            $value = $die["state"];
            if (!isset($res[$value])) {
                $res[$value] = $die;
            }
        }
        return $res;
    }

    function getPossibleMoves() {
        $dice = $this->getAllDice();
        $res = [];

        foreach ($dice as $value => $die) {
            $value = (int) $value;
            $valuePlus = $value + 1;
            $valueMinus = $value - 1;
            $key = $die["key"];
            if ($value > 1 && $value < 6) {
                $res["{$key}_up"] = [
                    "q" => Material::RET_OK,

                    "token_id" => $key,
                    "from" => $value,
                    "to" => $valuePlus,
                ];
                $res["{$key}_down"] = [
                    "q" => Material::RET_OK,

                    "token_id" => $key,
                    "from" => $value,
                    "to" => $valueMinus,
                ];
            } elseif ($value == 1) {
                $res["{$key}_up"] = [
                    "q" => Material::RET_OK,
                    "token_id" => $key,

                    "from" => $value,
                    "to" => $valuePlus,
                ];
            } elseif ($value == 6) {
                $res["{$key}_down"] = [
                    "q" => Material::RET_OK,

                    "from" => $value,
                    "to" => $valueMinus,
                    "token_id" => $key,
                ];
            }
        }

        return $res;
    }

    public function getUiArgs() {
        return ["imagebuttons" => true];
    }

    function canSkip() {
        return true;
    }

    function resolve(): void {
        $choice = $this->getCheckedArg();
        $parts = explode("_", $choice);
        $direction = array_pop($parts);
        $dieKey = implode("_", $parts);

        $currentValue = (int) $this->game->tokens->db->getTokenState($dieKey);
        $newValue = $direction == "up" ? $currentValue + 1 : $currentValue - 1;

        $this->dbSetTokenState($dieKey, $newValue, clienttranslate('${player_name} sets ${token_name} to ${new_state}'));
    }

    function getPrompt() {
        return clienttranslate("Select a die to modify by +1 or -1");
    }

    public function getIconicName() {
        return "[wicon_dice_mod]";
    }
}
