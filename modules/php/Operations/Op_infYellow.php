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

class Op_infYellow extends Operation {
    function getPossibleMoves() {
        $owner = $this->getOwner();
        $influence = $this->game->tokens->getTokensOfTypeInLocation("influence", "tableau_$owner");
        
        if (count($influence) == 0) {
            return ["q" => Material::ERR_NONE_LEFT];
        }
        
        return ["confirm"];
    }

    function canSkip() {
        return true;
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $influence = $this->game->tokens->getTokensOfTypeInLocation("influence", "tableau_$owner");
        $influenceKey = array_key_first($influence);
        
        $this->game->tokens->dbSetTokenLocation(
            $influenceKey,
            "guild_yellow",
            0,
            clienttranslate('${player_name} places ${token_name} on Yellow Guild')
        );
    }
}
