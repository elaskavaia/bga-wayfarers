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

class Op_newDie extends Operation {
    function getAllDice(): array {
        $owner = $this->getOwner();
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice_{$owner}", "supply");
        return $dice;
    }

    function getPossibleMoves() {
        $dice = $this->getAllDice();
        $res = [];
        foreach ($dice as $key => $die) {
            $res[$key] = ["q" => Material::RET_OK];
            break; // only one
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
            clienttranslate('${player_name} gains a new die and rerolls to ${new_state}')
        );
        $this->game->customUndoSavepoint($this->getPlayerId(), 1);
    }
    public function canSkip() {
        if (count($this->getAllDice()) == 0) {
            return true;
        }
        return false;
    }

    public function requireConfirmation() {
        return true;
    }

    function getPrompt() {
        return clienttranslate("Confirm gain die and reroll, this cannot be undone");
    }
}
