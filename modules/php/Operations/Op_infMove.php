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

class Op_infMove extends Operation {
    /**
     * Get all player's influence that can be moved (on guilds or cards)
     */
    function getGuildFrom(): array {
        $owner = $this->getOwner();
        $influence = [];

        // Check guilds
        foreach (["guild_black", "guild_yellow", "guild_blue"] as $guild) {
            $tokens = $this->game->tokens->getTokensOfTypeInLocation("influence_{$owner}", $guild);
            foreach ($tokens as $tokenId => $info) {
                $influence[$guild] = ["q" => Material::RET_OK, "from" => $guild, "token_id" => $tokenId];
                break; // Only need first token from each guild
            }
        }

        return $influence;
    }

    /**
     * Get possible destinations (guilds and available cards)
     */
    function getPossibleDestinations(string $sourceLocation): array {
        $res = [];

        // Add guilds (except source if it's a guild)
        foreach (["guild_black", "guild_yellow", "guild_blue"] as $guild) {
            if ($guild !== $sourceLocation) {
                $res[$guild] = ["q" => Material::RET_OK, "name" => $this->game->getTokenName($guild)];
            }
        }

        return $res;
    }

    function getPossibleMoves() {
        $selectedGuild = $this->getDataField("guild", null);

        if ($selectedGuild === null) {
            // Step 1: Select influence to move
            $influence = $this->getGuildFrom();

            if (count($influence) == 0) {
                return ["q" => Material::ERR_NONE_LEFT];
            }

            return $influence + ["prompt" => clienttranslate("Select Guild to move from")];
        }
        // Step 2: Select destination

        return $this->getPossibleDestinations($selectedGuild);
    }

    function canSkip() {
        return true;
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $selectedGuild = $this->getDataField("guild", null);

        if ($selectedGuild === null) {
            // Step 1: Store selected guild, queue step 2
            $selectedGuild = $this->getCheckedArg();
            $this->queue($this->getType(), $owner, ["guild" => $selectedGuild]);
            return;
        }

        // Step 2: Move influence to destination
        $to = $this->getCheckedArg();
        $selectedInfluence = $this->game->tokens->db->getTokensOfTypeInLocationSingleKey("influence_{$owner}", $selectedGuild);

        $this->dbSetTokenLocation(
            $selectedInfluence,
            $to,
            0,
            clienttranslate('${player_name} moves ${token_name} to ${place_name}')
        );
    }

    function getPrompt() {
        $selectedGuild = $this->getDataField("guild", null);
        if ($selectedGuild === null) {
            return clienttranslate("Select Guild to move from");
        }
        return clienttranslate("Select destination Guild");
    }

    public function getIconicName() {
        return "[wicon_inf_move]";
    }
}
