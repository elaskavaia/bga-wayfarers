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

use function Bga\Games\wayfarers\getPart;

/**
 * Handle influence interaction when interacting with a card that has opponent's influence on it.
 * Data field "card" must be set to the card being interacted with.
 * Data field "buy" (default true) controls whether influence is returned to opponent's tableau.
 * If the card has no influence, this operation does nothing.
 * Otherwise, the acting player must pay food or coin to the influence owner.
 */
class Op_cardInteract extends Operation {
    public function getCard(): ?string {
        return $this->getDataField("card", null);
    }

    public function isBeingBought(): bool {
        return $this->getDataField("buy", true);
    }

    /**
     * Get opponent's influence on the card (if any).
     * Returns null if no influence or if the influence belongs to the acting player.
     */
    public function getInfluenceOnCard(): ?string {
        $card = $this->getCard();
        if (!$card) {
            return null;
        }
        $children = $this->game->tokens->getTokensOfTypeInLocation("influence", $card);
        $infKey = array_key_first($children);
        if (!$infKey) {
            return null;
        }

        return $infKey;
    }

    public function isOwnInfluence(string $infKey) {
        // Only return opponent's influence, not player's own, influence_ff0000_10
        $infOwner = getPart($infKey, 1);
        if ($infOwner == $this->getOwner()) {
            return true;
        }
        return false;
    }

    function getPossibleMoves() {
        $inf = $this->getInfluenceOnCard();
        if (!$inf || $this->isOwnInfluence($inf)) {
            return ["confirm"];
        }

        $owner = $this->getOwner();
        $res = [];

        // Check if player can afford food
        $foodCount = $this->game->tokens->db->getTokenState("tracker_food_$owner");
        $res["tracker_food_$owner"] = ["q" => Material::ERR_COST, "name" => "[wicon_food]"];
        if ($foodCount >= 1) {
            $res["tracker_food_$owner"]["q"] = Material::RET_OK;
        }

        // Check if player can afford coin
        $coinCount = $this->game->tokens->db->getTokenState("tracker_coin_$owner");
        $res["tracker_coin_$owner"] = ["q" => Material::ERR_COST, "name" => "[wicon_coin]"];
        if ($coinCount >= 1) {
            $res["tracker_coin_$owner"]["q"] = Material::RET_OK;
        }

        return $res;
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $card = $this->getCard();
        $inf = $this->getInfluenceOnCard();

        if ($inf) {
            $opp = getPart($inf, 1);
            if (!$this->isOwnInfluence($inf)) {
                $choice = $this->getCheckedArg();
                $resourceType = getPart($choice, 1);

                // Pay from acting player
                $this->game->effect_incCount($owner, $resourceType, -1, $this->getOpId());

                // Give to opponent
                $this->game->effect_incCount($opp, $resourceType, 1, $this->getOpId());
            }

            // Return the influence token to the player's tableau (unless buy is false)
            if ($this->isBeingBought()) {
                $this->game->tokens->dbSetTokenLocation($inf, "tableau_$opp", 0);
            }
        }

        // If card is being bought, move any workers on it to the buyer's tableau
        if ($this->isBeingBought() && $card) {
            $workers = $this->game->tokens->getTokensOfTypeInLocation("worker", $card);
            foreach (array_keys($workers) as $workerKey) {
                $this->game->tokens->dbSetTokenLocation(
                    $workerKey,
                    "tableau_$owner",
                    0,
                    clienttranslate('${player_name} gains ${token_name}'),
                    [],
                    $this->getPlayerId()
                );
            }
        }
    }

    public function getPrompt() {
        return clienttranslate('${You} must pay the opponent to interact with a card, choose one');
    }
}
