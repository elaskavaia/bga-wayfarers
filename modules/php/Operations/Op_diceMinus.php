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

class Op_diceMinus extends Operation {
    function getAllDice(): array {
        $owner = $this->getOwner();
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_$owner");

        $cards = $this->game->tokens->getTokensOfTypeInLocationWithChildren("card", "tableau_$owner");
        foreach ($cards as $card => $info) {
            foreach ($info["children"] as $childKey => $childInfo) {
                if (str_starts_with($childKey, "dice_")) {
                    $dice[$childKey] = $childInfo;
                }
            }
        }

        $res = [];
        foreach ($dice as $key => $die) {
            $value = $die["state"];
            if ($value > 1 && !isset($res[$value])) {
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
            $key = $die["key"];
            $res[$key] = ["q" => Material::RET_OK, "name" => "$value - 1"];
        }

        return $res;
    }

    function canSkip() {
        return true;
    }

    function resolve(): void {
        $dieKey = $this->getCheckedArg();
        $currentValue = (int) $this->game->tokens->db->getTokenState($dieKey);
        $newValue = $currentValue - 1;

        $this->game->tokens->dbSetTokenState($dieKey, $newValue, clienttranslate('${player_name} sets ${token_name} to ${new_state}'));
    }

    function getPrompt() {
        return clienttranslate("Select a die to decrease by -1");
    }
}
