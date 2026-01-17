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
        if ($targetGuild === null) {
            $targetGuild = $this->getGuild();
        }
        $movable = [];

        // Check other guilds
        foreach (["guild_black", "guild_yellow", "guild_blue"] as $guild) {
            if ($guild === $targetGuild) {
                continue;
            }
            $tokens = $this->game->tokens->getTokensOfTypeInLocation("influence_{$owner}", $guild);
            foreach ($tokens as $tokenId => $info) {
                $movable[$tokenId] = ["q" => Material::RET_OK, "from" => $guild];
            }
        }

        // Check cards in mainarea (influence placed on cards)
        $cardsWithInfluence = $this->game->tokens->getTokensOfTypeInLocation("influence_{$owner}", "card_%", true);
        foreach ($cardsWithInfluence as $tokenId => $info) {
            $movable[$tokenId] = ["q" => Material::RET_OK, "from" => $info["location"]];
        }

        return $movable;
    }

    function getPossibleMoves() {
        $influence = $this->getInfluenceInPlayerSupply();

        if (count($influence) > 0) {
            // Has influence in supply - just confirm
            return ["confirm"];
        }

        // No influence in supply - check for movable influence
        $movable = $this->getMovableInfluence();
        if (count($movable) == 0) {
            return ["q" => Material::ERR_NONE_LEFT];
        }

        return $movable;
    }

    function resolve(): void {
        $guild = $this->getGuild();
        $influence = $this->getInfluenceInPlayerSupply();

        if (count($influence) > 0) {
            // Place from supply
            $influenceKey = array_key_first($influence);
            $this->game->tokens->dbSetTokenLocation(
                $influenceKey,
                $guild,
                0,
                clienttranslate('${player_name} places ${token_name} on ${place_name}')
            );
        } else {
            // Move from another location
            $influenceKey = $this->getCheckedArg();
            $this->game->tokens->dbSetTokenLocation(
                $influenceKey,
                $guild,
                0,
                clienttranslate('${player_name} moves ${token_name} to ${place_name}')
            );
        }
    }

    public function canSkip() {
        $influence = $this->getInfluenceInPlayerSupply();

        if (count($influence) > 0) {
            // no point skipping
            return false;
        }
        return true;
    }

    function getPrompt() {
        $influence = $this->getInfluenceInPlayerSupply();

        if (count($influence) > 0) {
            return clienttranslate("Confirm to place influence");
        }
        return clienttranslate("No influence left in supply. Select influence to move or Skip");
    }
}
