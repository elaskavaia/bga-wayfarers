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

use Bga\Games\wayfarers\Game;
use Bga\Games\wayfarers\Material;

class Op_infCard extends Op_infBase {
    public function auto(): bool {
        if ($this->getPlayerId() == Game::PLAYER_AUTOMA) {
            $this->queue("ai_" . $this->getType(), $this->game->getAutomaColor());
            return true;
        }
        return parent::auto();
    }
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
            // Card is available if it has no influence on it (workers don't block influence)
            $hasInfluence = false;
            foreach ($info["children"] as $childKey => $child) {
                if (str_starts_with($childKey, "influence")) {
                    $hasInfluence = true;
                    break;
                }
            }
            if (!$hasInfluence) {
                $res[$cardId] = ["q" => Material::RET_OK];
            }
        }

        return $res;
    }

    function getPossibleMoves() {
        $selectedCard = $this->getGuild();

        if ($selectedCard === "") {
            // Step 1: Select which card to place influence on
            $availableCards = $this->getAvailableCards();

            if (count($availableCards) == 0) {
                return ["q" => Material::ERR_NONE_LEFT];
            }

            return ["prompt" => clienttranslate("Select a card to place influence on")] + $availableCards;
        }

        // Step 2: Card selected, now place or select influence to move
        return parent::getPossibleMoves();
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
        parent::resolve();
    }
}
