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
 * Ask player to confirm spending blue influence to gain a virtual ship for die placement.
 * Can only be used once per turn.
 */
class Op_spendInfBlue extends Operation {
    /**
     * Check if blue influence has already been spent this turn
     */
    function isBlueInfluenceSpentThisTurn(): bool {
        $owner = $this->getOwner();
        $flagToken = "used_inf_blue_$owner";
        return $this->game->tokens->db->getTokenState($flagToken) > 0;
    }

    /**
     * Check if player has blue influence available
     */
    function hasBlueInfluence(): bool {
        $owner = $this->getOwner();
        return $this->game->countGuildInfluence("guild_blue", $owner) > 0;
    }

    public function getPossibleMoves() {
        if (!$this->hasBlueInfluence()) {
            return ["err" => clienttranslate("No influence in Blue guild")];
        }
        if ($this->isBlueInfluenceSpentThisTurn()) {
            return ["err" => clienttranslate("This can only be done once per turn")];
        }
        return parent::getPossibleMoves();
    }

    public function canSkip() {
        return true;
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $selected = $this->getCheckedArg();

        // Mark that blue influence was spent this turn
        $flagToken = "used_inf_blue_$owner";
        $this->game->tokens->dbSetTokenState($flagToken, 1, clienttranslate('${player_name} spends blue influence to gain a ship'));

        // Spend 1 blue influence
        $this->queue("n_infBlue");
    }

    public function getPrompt() {
        return clienttranslate("Spend blue influence to gain a ship for this die placement (once per turn)");
    }
    public function getIconicName() {
        return "[wicon_inf_blue_pay]: [wicon_ship]";
    }
}
