<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * implementation : © Alena Laskavaia <laskava@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

declare(strict_types=1);

namespace Bga\Games\wayfarers\Operations;

use Bga\Games\wayfarers\OpCommon\Operation;
use Bga\Games\wayfarers\Material;
use Override;

/**
 * Turn end confirmation operation.
 * Requires confirmation based on player preference MA_PREF_CONFIRM_TURN.
 */
class Op_turnconf extends Operation {
    #[Override]
    public function auto(): bool {
        $owner = $this->getOwner();
        $playerId = $this->getPlayerId();
        $this->game->notify->all("journalTagCounts", "", [
            "player_id" => $playerId,
            "color" => $owner,
            "counts" => $this->game->getJournalTagCounts($owner),
        ]);
        return parent::auto();
    }
    function requireConfirmation() {
        if ($this->getDataField("noconfirm", false)) {
            return false;
        }
        $player_id = $this->getPlayerId();
        $pref = $this->game->getUserPreference($player_id, Material::MA_PREF_CONFIRM_TURN);
        return (bool) $pref;
    }

    function getPrompt() {
        return clienttranslate("You may confirm or Undo your turn");
    }

    public function getSubTitle() {
        return clienttranslate("You can disable this confirmation in preferences");
    }

    function resolve(): void {
        // nothing
    }

    #[Override]
    public function getExtraArgs() {
        return parent::getExtraArgs() + [
            "pref_id" => Material::MA_PREF_CONFIRM_TURN,
        ];
    }
}
