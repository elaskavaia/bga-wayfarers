<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * wayfarers implementation : © Alena Laskavaia <laskava@gmail.com>
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

/**
 * When the AI wants to interact with a card that has the human player's influence,
 * the player chooses:
 *   - Allow: gain Silver or Provisions from the main supply
 *   - Deny: pay Silver or Provisions to make the AI skip to the next card
 *
 * Data fields:
 *   - "card"        — the card the AI wants to interact with
 *   - "caller"      — the AI operation type to re-queue on deny (e.g. "ai_cardLand", "ai_placeWorker(green)")
 *   - "caller_data" — data to pass back to the caller on deny (includes updated denied list)
 */
class Op_ai_cardInteractChoice extends Operation {
    function getPossibleMoves() {
        $owner = $this->getOwner();
        $res = [];

        // Allow options — player gains from main supply
        $res["allow_coin"] = ["q" => Material::RET_OK, "name" => clienttranslate("Allow - Gain [wicon_coin]")];
        $res["allow_food"] = ["q" => Material::RET_OK, "name" => clienttranslate("Allow - Gain [wicon_food]")];

        // Deny options — player pays to main supply
        $coinCount = $this->game->tokens->getTrackerValue($owner, "coin");
        $res["deny_coin"] = ["q" => Material::ERR_COST, "name" => clienttranslate("Deny - Pay [wicon_coin]")];
        if ($coinCount >= 1) {
            $res["deny_coin"]["q"] = Material::RET_OK;
        }

        $foodCount = $this->game->tokens->getTrackerValue($owner, "food");
        $res["deny_food"] = ["q" => Material::ERR_COST, "name" => clienttranslate("Deny - Pay [wicon_food]")];
        if ($foodCount >= 1) {
            $res["deny_food"]["q"] = Material::RET_OK;
        }

        return $res;
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $choice = $this->getCheckedArg();
        $card = $this->getDataField("card");
        $caller = $this->getDataField("caller");
        $callerData = $this->getDataField("caller_data", []);

        $parts = explode("_", $choice, 2);
        $action = $parts[0]; // "allow" or "deny"
        $resource = $parts[1]; // "coin" or "food"

        $aiOwner = $this->game->getAutomaColor();

        if ($action === "allow") {
            // Player gains 1 resource from main supply
            $this->game->effect_incCount($owner, $resource, 1, $this->getOpId());

            // Re-queue the caller with confirmed_card so it commits without re-checking influence
            $callerData["confirmed_card"] = $card;
            $this->queue($caller, $aiOwner, $callerData);
        } else {
            // Deny — player pays 1 resource to main supply
            $this->game->effect_incCount($owner, $resource, -1, $this->getOpId());

            // Add this card to the denied list and re-queue the caller to try next card
            $skip = $callerData["denied"] ?? [];
            $skip[] = $card;
            $callerData["denied"] = $skip;
            unset($callerData["confirmed_card"]);

            $this->queue($caller, $aiOwner, $callerData);
        }
    }

    public function getPrompt() {
        return clienttranslate('The AI wants to interact with a card ${token_name} you have influence on. Allow or Deny?');
    }

    public function getExtraArgs() {
        $card = $this->getDataField("card");

        return [
            "token_name" => $card,
        ];
    }

    public function getUiArgs() {
        $card = $this->getDataField("card");
        return [
            "imagebuttons" => true,
            "selected" => [$card],
        ];
    }
}
