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

use Bga\Games\wayfarers\OpCommon\Operation;

/**
 * Ask player to confirm spending black influence to move an extra space.
 * Can only be used once per turn.
 */
class Op_spendInfBlack extends Operation {
    /**
     * Check if black influence has already been spent this turn
     */
    function isBlackInfluenceSpentThisTurn(): bool {
        $owner = $this->getOwner();
        $flagToken = "used_inf_black_$owner";
        return $this->game->tokens->db->getTokenState($flagToken) > 0;
    }

    /**
     * Check if player has black influence available
     */
    function hasBlackInfluence(): bool {
        $owner = $this->getOwner();
        return $this->game->countGuildInfluence("guild_black", $owner) > 0;
    }

    public function getPossibleMoves() {
        if (!$this->hasBlackInfluence()) {
            return ["err" => clienttranslate("No influence in Black guild")];
        }
        if ($this->isBlackInfluenceSpentThisTurn()) {
            return ["err" => clienttranslate("This can only be done once per turn")];
        }
        $op = $this->instanciateOperation("journal");
        if ($op->noValidTargets()) {
            return ["err" => clienttranslate("No valid targets for Journal")];
        }
        return parent::getPossibleMoves();
    }

    public function canSkip() {
        return true;
    }

    function resolve(): void {
        $owner = $this->getOwner();

        // Mark that black influence was spent this turn
        $flagToken = "used_inf_black_$owner";
        $this->game->tokens->dbSetTokenState($flagToken, 1, clienttranslate('${player_name} spends black influence to do extra Journal'));

        // Spend 1 black influence
        $this->queue("n_infBlack");

        $this->queue("journal");
    }

    public function getPrompt() {
        return clienttranslate("Spend black influence for extra Journal step (once per turn)");
    }

    public function getIconicName() {
        return "[wicon_inf_black_pay]: [wicon_journal]";
    }
}
