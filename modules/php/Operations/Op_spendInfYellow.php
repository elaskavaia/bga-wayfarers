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

/**
 * Ask player to confirm spending yellow influence to gain 2 diceMod operations.
 * Can only be used once per turn.
 */
class Op_spendInfYellow extends Operation {
    /**
     * Check if yellow influence has already been spent this turn
     */
    function isYellowInfluenceSpentThisTurn(): bool {
        $owner = $this->getOwner();
        $flagToken = "used_inf_yellow_$owner";
        return $this->game->tokens->db->getTokenState($flagToken) > 0;
    }

    /**
     * Check if player has yellow influence available
     */
    function hasYellowInfluence(): bool {
        $owner = $this->getOwner();
        return $this->game->countGuildInfluence("guild_yellow", $owner) > 0;
    }

    public function getPossibleMoves() {
        if (!$this->hasYellowInfluence()) {
            return ["err" => clienttranslate("No influence in Yellow guild")];
        }
        if ($this->isYellowInfluenceSpentThisTurn()) {
            return ["err" => clienttranslate("This can only be done once per turn")];
        }
        return parent::getPossibleMoves();
    }

    public function canSkip() {
        return true;
    }

    function resolve(): void {
        $owner = $this->getOwner();

        // Mark that yellow influence was spent this turn
        $flagToken = "used_inf_yellow_$owner";
        $this->game->tokens->dbSetTokenState($flagToken, 1, clienttranslate('${player_name} spends yellow influence to modify dice'));

        // Spend 1 yellow influence
        $this->queue("n_infYellow");

        // Queue 2 diceMod operations
        $this->queue("diceMod");
        $this->queue("diceMod");
    }

    public function getPrompt() {
        return clienttranslate("Spend yellow influence to modify dice twice +/- 1 (once per turn)");
    }

    public function getIconicName() {
        return "[wicon_inf_yellow_pay]: [wicon_dice_mod][wicon_dice_mod]";
    }
}
