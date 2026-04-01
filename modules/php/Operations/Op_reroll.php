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

class Op_reroll extends Operation {
    public function auto(): bool {
        if ($this->getPlayerId() == Game::PLAYER_AUTOMA) {
            // AI picks a worker instead of re-roll
            $this->queue("pickWorker", $this->game->getAutomaColor());
            return true;
        }
        return parent::auto();
    }
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
        $dieKey = $this->getDie();
        if ($dieKey) {
            if ($this->isConfirmed()) {
                return [$dieKey];
            }
            $res[$dieKey] = ["q" => Material::RET_OK, "name" => clienttranslate("Reroll"), "buttons" => true];
            return $res;
        }

        $dice = $this->getAllDice();
        return array_keys($dice);
    }

    public function getSkipName() {
        return clienttranslate("Keep as is");
    }

    public function getUiArgs() {
        return ["buttons" => false];
    }

    function resolve(): void {
        // Use die from data if set, otherwise get from user selection
        $dieKey = $this->getDie();
        if (!$dieKey) {
            $dieKey = $this->getCheckedArg();
        }

        $newValue = bga_rand(1, 6);
        $owner = $this->getOwner();
        $this->dbSetTokenLocation(
            $dieKey,
            "tableau_$owner",
            $newValue,
            clienttranslate('${player_name} rerolls ${token_name} to ${new_state}')
        );
        $this->game->customUndoSavepoint($this->getPlayerId(), 1);
    }
    public function canSkip() {
        if ($this->isConfirmed()) {
            return false;
        }
        return true;
    }

    function getDie() {
        return $this->getDataField("target", null);
    }

    function isConfirmed() {
        return $this->getDataField("confirmed", null);
    }

    function getDieValue(): int {
        if (!$this->getDie()) {
            return 0;
        }
        return (int) $this->game->tokens->db->getTokenState($this->getDie());
    }

    function getPrompt() {
        $dieKey = $this->getDie();
        if ($dieKey) {
            return clienttranslate('Confirm to reroll ${token_div}');
        }
        return clienttranslate("Select a die to reroll");
    }

    public function getExtraArgs() {
        $dieValue = $this->getDieValue();
        return parent::getExtraArgs() + ["token_div" => "wicon_die_$dieValue"];
    }
}
