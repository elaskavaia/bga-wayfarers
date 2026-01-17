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

class Op_infAny extends Op_infBase {
    function getPossibleMoves() {
        $owner = $this->getOwner();
        $influence = $this->game->tokens->getTokensOfTypeInLocation("influence", "tableau_$owner");

        if (count($influence) == 0) {
            return ["q" => Material::ERR_NONE_LEFT];
        }

        return [
            "guild_black" => ["q" => Material::RET_OK, "name" => $this->game->getTokenName("guild_black")],
            "guild_yellow" => ["q" => Material::RET_OK, "name" => $this->game->getTokenName("guild_yellow")],
            "guild_blue" => ["q" => Material::RET_OK, "name" => $this->game->getTokenName("guild_blue")],
        ];
    }

    function getGuild(): string {
        return $this->getDataField("guild", "guild_any");
    }

    function canSkip() {
        return true;
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $guild = $this->getCheckedArg();
        $influence = $this->game->tokens->getTokensOfTypeInLocation("influence", "tableau_$owner");
        $influenceKey = array_key_first($influence);

        $this->game->tokens->dbSetTokenLocation(
            $influenceKey,
            $guild,
            0,
            clienttranslate('${player_name} places ${token_name} on ${place_name}')
        );
    }

    function getPrompt() {
        return clienttranslate("Select a guild to place influence on");
    }
}
