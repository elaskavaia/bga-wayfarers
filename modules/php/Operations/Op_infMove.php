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
    function getPlayerInfluenceOnGuilds(): array {
        $owner = $this->getOwner();
        $influence = [];
        
        foreach (["guild_black", "guild_yellow", "guild_blue"] as $guild) {
            $tokens = $this->game->tokens->getTokensOfTypeInLocation("influence_{$owner}", $guild);
            if (count($tokens) > 0) {
                $influence[$guild] = array_key_first($tokens);
            }
        }
        
        return $influence;
    }

    function getPossibleMoves() {
        $selectedFrom = $this->getDataField("from", null);
        
        if ($selectedFrom === null) {
            // Step 1: Select source guild
            $influence = $this->getPlayerInfluenceOnGuilds();
            
            if (count($influence) == 0) {
                return ["q" => Material::ERR_NONE_LEFT];
            }
            
            $res = [];
            foreach ($influence as $guild => $token) {
                $res[$guild] = ["q" => Material::RET_OK, "name" => $this->game->getTokenName($guild)];
            }
            return $res;
        } else {
            // Step 2: Select destination guild
            $res = [];
            foreach (["guild_black", "guild_yellow", "guild_blue"] as $guild) {
                if ($guild !== $selectedFrom) {
                    $res[$guild] = ["q" => Material::RET_OK, "name" => $this->game->getTokenName($guild)];
                }
            }
            return $res;
        }
    }

    function canSkip() {
        return true;
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $selectedFrom = $this->getDataField("from", null);
        
        if ($selectedFrom === null) {
            // Step 1: Store source guild and queue step 2
            $from = $this->getCheckedArg();
            $this->queue($this->getType(), $owner, ["from" => $from]);
            return;
        }
        
        // Step 2: Move influence from source to destination
        $to = $this->getCheckedArg();
        $influence = $this->getPlayerInfluenceOnGuilds();
        $influenceKey = $influence[$selectedFrom];
        
        $this->game->tokens->dbSetTokenLocation(
            $influenceKey,
            $to,
            0,
            clienttranslate('${player_name} moves ${token_name} from ${place_from} to ${place_name}')
        );
    }

    function getPrompt() {
        $selectedFrom = $this->getDataField("from", null);
        if ($selectedFrom === null) {
            return clienttranslate("Select a guild to move influence from");
        }
        return clienttranslate("Select a guild to move influence to");
    }
}
