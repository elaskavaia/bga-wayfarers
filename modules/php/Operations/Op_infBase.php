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

abstract class Op_infBase extends Operation {
    abstract function getGuild(): string;

    /**
     * Get player's influence tokens in their supply (tableau)
     */
    function getInfluenceInPlayerSupply(): array {
        $owner = $this->getOwner();
        return $this->game->tokens->getTokensOfTypeInLocation("influence", "tableau_$owner");
    }

    /**
     * Get player's influence tokens that can be moved (from other guilds or cards)
     */
    function getMovableInfluence(?string $targetGuild = null): array {
        $owner = $this->getOwner();
        $movable = [];

        $inf = $this->game->tokens->getTokensOfTypeInLocation("influence_{$owner}");
        foreach ($inf as $tokenId => $info) {
            $place = $info["location"];
            if ($place === $targetGuild) {
                continue;
            }
            $movable[$tokenId] = ["q" => Material::RET_OK, "place_from" => $place];
        }
        return $movable;
    }

    function getPossibleMoves() {
        if ($this->isAutomaPlayer()) {
            // Automa always has unlimited influence (created on demand in resolve)
            return ["confirm"];
        }
        $influence = $this->getInfluenceInPlayerSupply();

        if (count($influence) > 0) {
            // Has influence in supply - just confirm
            return ["confirm"];
        }

        // No influence in supply - check for movable influence
        $movable = $this->getMovableInfluence();
        return ["prompt" => clienttranslate("No influence left in supply. Select influence to move or Skip")] + $movable;
    }
    public function getUiArgs() {
        return ["buttons" => false];
    }

    function getPrompt() {
        return clienttranslate("Confirm to place influence");
    }

    function resolve(): void {
        $guild = $this->getGuild();
        $influenceKey = $this->getCheckedArg();

        if ($influenceKey == "confirm") {
            // Place from supply
            $influence = $this->getInfluenceInPlayerSupply();
            $influenceKey = array_key_first($influence);

            // Automa has unlimited influence - create more if needed
            if (!$influenceKey && $this->isAutomaPlayer()) {
                $owner = $this->getOwner();
                $influenceKey = $this->game->tokens->db->createTokenAutoInc("influence_$owner", "tableau_$owner", 0);
            }

            $this->game->tokens->dbSetTokenLocation(
                $influenceKey,
                $guild,
                0,
                clienttranslate('${player_name} places ${token_name} on ${place_name}'),
                [],
                $this->getPlayerId()
            );
        } else {
            // Move from another location
            $this->game->tokens->dbSetTokenLocation(
                $influenceKey,
                $guild,
                0,
                clienttranslate('${player_name} moves ${token_name} to ${place_name}'),
                [],
                $this->getPlayerId()
            );
        }
    }

    public function canSkip() {
        // Automa never skips - always has unlimited influence
        if ($this->isAutomaPlayer()) {
            return false;
        }

        $influence = $this->getInfluenceInPlayerSupply();

        if (count($influence) > 0) {
            // no point skipping
            return false;
        }
        return true;
    }
}
