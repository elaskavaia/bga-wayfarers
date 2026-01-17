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

class Op_infCard extends Op_infBase {
    function getGuild(): string {
        // This returns the selected card (used as target location)
        return $this->getDataField("card", "");
    }
    public function getUiArgs() {
        return ["buttons" => false];
    }

    /**
     * Get cards in mainarea that don't have any player's influence yet
     */
    function getAvailableCards(): array {
        $res = [];

        // Get all cards in mainarea with their children (influence tokens)
        $cards = $this->game->tokens->getTokensOfTypeInLocationWithChildren("card", "mainarea");

        foreach ($cards as $cardId => $info) {
            // Card is available if it has no children (no influence on it)
            if (count($info["children"]) == 0) {
                $res[$cardId] = ["q" => Material::RET_OK];
            }
        }

        return $res;
    }

    /**
     * Get movable influence (from guilds or other cards, excluding target card)
     */
    function getMovableInfluenceForCard(string $targetCard): array {
        $movable = $this->getMovableInfluence("card_any");
        unset($movable[$targetCard]);
        return $movable;
    }

    function getPossibleMoves() {
        $selectedCard = $this->getGuild();
        $influence = $this->getInfluenceInPlayerSupply();

        if ($selectedCard === "") {
            // Step 1: Select which card to place influence on
            $availableCards = $this->getAvailableCards();

            // Check if player has any influence available
            $movableForAny = $this->getMovableInfluence("card_any");
            if (count($influence) == 0 && count($movableForAny) == 0) {
                return ["q" => Material::ERR_NONE_LEFT];
            }

            if (count($availableCards) == 0) {
                return ["q" => Material::ERR_NONE_LEFT];
            }

            return $availableCards;
        }

        // Step 2: Card selected, now place or select influence to move
        if (count($influence) > 0) {
            return ["confirm"];
        }

        // No influence in supply - show movable influence
        $movable = $this->getMovableInfluenceForCard($selectedCard);
        if (count($movable) == 0) {
            return ["q" => Material::ERR_NONE_LEFT];
        }

        return $movable;
    }

    function canSkip() {
        $influence = $this->getInfluenceInPlayerSupply();
        if (count($influence) > 0) {
            return false;
        }
        return true;
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $selectedCard = $this->getGuild();

        if ($selectedCard === "") {
            // Step 1: Store selected card and queue step 2
            $card = $this->getCheckedArg();
            $this->queue($this->getType(), $owner, ["card" => $card]);
            return;
        }

        // Step 2: Place or move influence
        $influence = $this->getInfluenceInPlayerSupply();

        if (count($influence) > 0) {
            // Place from supply
            $influenceKey = array_key_first($influence);
            $this->game->tokens->dbSetTokenLocation(
                $influenceKey,
                $selectedCard,
                0,
                clienttranslate('${player_name} places ${token_name} on ${place_name}')
            );
        } else {
            // Move from another location
            $influenceKey = $this->getCheckedArg();
            $this->game->tokens->dbSetTokenLocation(
                $influenceKey,
                $selectedCard,
                0,
                clienttranslate('${player_name} moves ${token_name} to ${place_name}')
            );
        }
    }

    function getPrompt() {
        $selectedCard = $this->getGuild();

        if ($selectedCard === "") {
            return clienttranslate("Select a card to place influence on");
        }

        $influence = $this->getInfluenceInPlayerSupply();
        if (count($influence) > 0) {
            return clienttranslate("Confirm to place influence on card");
        }
        return clienttranslate("No influence left in supply. Select influence to move or Skip");
    }
}
