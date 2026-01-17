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
    function getPlayerInfluenceToMove(): array {
        $owner = $this->getOwner();
        $influence = [];

        // Check guilds
        foreach (["guild_black", "guild_yellow", "guild_blue"] as $guild) {
            $tokens = $this->game->tokens->getTokensOfTypeInLocation("influence_{$owner}", $guild);
            foreach ($tokens as $tokenId => $info) {
                $influence[$tokenId] = ["q" => Material::RET_OK, "from" => $guild];
            }
        }

        // Check cards
        $cardsWithInfluence = $this->game->tokens->getTokensOfTypeInLocation("influence_{$owner}", "card_%", true);
        foreach ($cardsWithInfluence as $tokenId => $info) {
            $influence[$tokenId] = ["q" => Material::RET_OK, "from" => $info["location"]];
        }

        return $influence;
    }

    /**
     * Get possible destinations (guilds and available cards)
     */
    function getPossibleDestinations(string $sourceLocation): array {
        $owner = $this->getOwner();
        $res = [];

        // Add guilds (except source if it's a guild)
        foreach (["guild_black", "guild_yellow", "guild_blue"] as $guild) {
            if ($guild !== $sourceLocation) {
                $res[$guild] = ["q" => Material::RET_OK, "name" => $this->game->getTokenName($guild)];
            }
        }

        // Add cards that don't have player's influence yet
        $cards = $this->game->tokens->getTokensOfTypeInLocation("card", "mainarea");
        $cardsWithMyInfluence = $this->game->tokens->getTokensOfTypeInLocation("influence_{$owner}", "card_%", true);
        $occupiedCards = [];
        foreach ($cardsWithMyInfluence as $info) {
            $occupiedCards[$info["location"]] = true;
        }

        foreach ($cards as $cardId => $info) {
            // Can move to card if it doesn't have my influence (unless moving from that same card)
            if (!isset($occupiedCards[$cardId]) || $cardId === $sourceLocation) {
                if ($cardId !== $sourceLocation) {
                    $res[$cardId] = ["q" => Material::RET_OK];
                }
            }
        }

        return $res;
    }

    function getPossibleMoves() {
        $selectedInfluence = $this->getDataField("influence", null);

        if ($selectedInfluence === null) {
            // Step 1: Select influence to move
            $influence = $this->getPlayerInfluenceToMove();

            if (count($influence) == 0) {
                return ["q" => Material::ERR_NONE_LEFT];
            }

            return $influence;
        } else {
            // Step 2: Select destination
            $sourceLocation = $this->getDataField("from", "");
            return $this->getPossibleDestinations($sourceLocation);
        }
    }

    function canSkip() {
        return true;
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $selectedInfluence = $this->getDataField("influence", null);

        if ($selectedInfluence === null) {
            // Step 1: Store selected influence and its source, queue step 2
            $influenceKey = $this->getCheckedArg();
            $allInfluence = $this->getPlayerInfluenceToMove();
            $from = $allInfluence[$influenceKey]["from"] ?? "";
            $this->queue($this->getType(), $owner, ["influence" => $influenceKey, "from" => $from]);
            return;
        }

        // Step 2: Move influence to destination
        $to = $this->getCheckedArg();

        $this->game->tokens->dbSetTokenLocation(
            $selectedInfluence,
            $to,
            0,
            clienttranslate('${player_name} moves ${token_name} to ${place_name}')
        );
    }

    function getPrompt() {
        $selectedInfluence = $this->getDataField("influence", null);
        if ($selectedInfluence === null) {
            return clienttranslate("Select influence to move");
        }
        return clienttranslate("Select destination for influence");
    }
}
