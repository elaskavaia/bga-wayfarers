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

class Op_reroll extends Operation {
    function getAllDice(): array {
        $owner = $this->getOwner();
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_$owner");

        // Also get dice placed on cards in player's tableau
        $cards = $this->game->tokens->getTokensOfTypeInLocationWithChildren("card", "tableau_$owner");
        foreach ($cards as $card => $info) {
            if (isset($info["children"])) {
                foreach ($info["children"] as $childKey => $childInfo) {
                    if (str_starts_with($childKey, "dice_")) {
                        $dice[$childKey] = $childInfo;
                    }
                }
            }
        }
        return $dice;
    }

    function getPossibleMoves() {
        $dice = $this->getAllDice();
        $res = [];
        foreach ($dice as $key => $die) {
            $res[$key] = ["q" => Material::RET_OK];
        }
        return $res;
    }

    function resolve(): void {
        $dieKey = $this->getCheckedArg();
        $newValue = bga_rand(1, 6);
        $owner = $this->getOwner();
        $this->game->tokens->dbSetTokenLocation(
            $dieKey,
            "tableau_$owner",
            $newValue,
            clienttranslate('${player_name} rerolls ${token_name} to ${new_state}')
        );
    }
    public function canSkip() {
        return true;
    }

    function getPrompt() {
        return clienttranslate("Select a die to reroll");
    }
}
