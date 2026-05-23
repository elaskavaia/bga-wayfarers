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
use Bga\Games\wayfarers\OpCommon\Operation;

class Op_newDie extends Operation {
    public function auto(): bool {
        if ($this->getPlayerId() == Game::PLAYER_AUTOMA) {
            // AI  gets upgrade instead of new die
            $this->queue("ai_upgAny", $this->game->getAutomaColor());
            return true;
        }
        return parent::auto();
    }
    function getDie() {
        $owner = $this->getOwner();
        $die = $this->game->tokens->db->getTokensOfTypeInLocationSingleKey("dice_{$owner}", "supply");
        return $die;
    }

    function getPossibleMoves() {
        if (!$this->getDie()) {
            return ["err" => clienttranslate("No dice available in supply")];
        }
        return parent::getPossibleMoves();
    }

    function resolve(): void {
        $dieKey = $this->getDie();
        if (!$dieKey) {
            return;
        }
        $newValue = bga_rand(1, 6);
        $owner = $this->getOwner();
        $this->dbSetTokenLocation(
            $dieKey,
            "tableau_$owner",
            $newValue,
            clienttranslate('${player_name} gains a new die and rerolls to ${new_state}')
        );
        $this->game->customUndoSavepoint($this->getPlayerId(), 1);
    }
    public function canSkip() {
        if (!$this->getDie()) {
            return true;
        }
        return parent::canSkip();
    }

    public function requireConfirmation() {
        return true;
    }

    function getPrompt() {
        return clienttranslate("Confirm gain die and reroll, this cannot be undone");
    }
}
