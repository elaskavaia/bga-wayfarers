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
use Bga\Games\wayfarers\OpCommon\Operation;

abstract class Op_cardBase extends Op_acquireBase {
    public function auto(): bool {
        if ($this->getPlayerId() == Game::PLAYER_AUTOMA) {
            $this->queue("ai_" . $this->getType(), $this->game->getAutomaColor());
            return true;
        }
        return parent::auto();
    }

    public function getCard() {
        return $this->getDataField("card", null);
    }
    function getCardType() {
        return "home";
    }
    /**
     * Check if pigeon is available as a leftover asset from die placement
     */
    function hasPigeonLeftover(): bool {
        $die = $this->getDie();
        if (!$die) {
            return false;
        }

        $owner = $this->getOwner();
        $dieValue = (int) $this->game->tokens->db->getTokenState($die);

        // Get caravan assets for this die value
        $caravanAssets = $this->game->getCaravanAssetsForDie($dieValue, $owner);

        // Get the card where the die was placed (from reason)
        $placedCard = $this->getReason();
        if (!$placedCard) {
            return false;
        }

        // Add assets from tucked folk card (e.g. Messenger provides pigeon)
        $folkCard = $this->getTuckedFolk($placedCard);
        if ($folkCard !== null) {
            $folkRule = $this->game->getRulesFor($folkCard, "da", "");
            $this->game->updateMatchingAssetsFromRule($folkRule, $caravanAssets);
        }

        // Get asset requirements for that card
        $requirements = $this->game->getRulesFor($placedCard, "d", "");

        // Calculate leftover assets after meeting requirements
        $missing = $this->game->getMissingAssetRequirements($requirements . ",pigeon", $caravanAssets);

        // If pigeon is not missing its available
        return array_search("pigeon", $missing) === false;
    }

    function getPossibleMoves() {
        $cardSelected = $this->getCard();
        if ($cardSelected) {
            return [$cardSelected];
        }
        $res = [];
        $cardType = $this->getCardType();

        $tokens = $this->game->tokens->getTokensOfTypeInLocationWithChildren("card_$cardType", "mainarea", null, "token_state");

        foreach ($tokens as $card => $info) {
            $payop = $this->getPaymentOperation($card);
            if ($this->isFree()) {
                $payop = "";
            }

            $can = $this->canAfford($payop);
            $res[$card] = ["q" => $can ? 0 : Material::ERR_COST, "can" => $can, "pay" => $payop];
        }

        return $res;
    }

    function placeCard($card) {
        $owner = $this->getOwner();
        $cardType = $this->getCardType();
        $tokens = $this->game->tokens->getTokensOfTypeInLocation("card_$cardType", "tableau_$owner");
        $this->dbSetTokenLocation($card, "tableau_$owner", count($tokens) + 2);
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $card = $this->getCard() ?: $this->getCheckedArg();

        // Check if player chose the deck
        if (str_starts_with($card, "deck_")) {
            $owner = $this->getOwner();
            // If pigeon is a leftover asset
            if (!$this->hasPigeonLeftover()) {
                $this->queue("n_food", $owner, ["reason" => "Op_cardDraw"]);
            }
            $this->queue("3cardDraw({$this->getCardType()})");
            return;
        }
        // Handle payment
        if (!$this->isFree()) {
            $payop = $this->getPaymentOperation($card);
            if ($payop) {
                $this->queue($payop);
            }
        }
        // Handle influence interaction if there's influence on the card
        $this->queue("cardInteract", $owner, ["card" => $card]);

        // Immediate bonus
        $r = $this->game->getRulesFor($card, "r");
        if ($r) {
            $this->queue($r, $owner, []);
        }
        // Check if any Vista cards are triggered by this card
        $this->queueVistaTriggers($card);
        $this->placeCard($card);
        return;
    }

    public function getPrompt() {
        return clienttranslate("Select a Card to buy");
    }

    public function getUiArgs() {
        return ["buttons" => false];
    }
    public function requireConfirmation() {
        // When card is pre-selected from a draw, no confirmation needed
        if ($this->getCard()) {
            return false;
        }
        return true;
    }
}
