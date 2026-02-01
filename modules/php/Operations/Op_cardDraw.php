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
use Bga\Games\wayfarers\OpCommon\CountableOperation;

/**
 * Draw N cards from a deck, player picks one to add to tableau, rest go to discard/bottom of deck
 * Usage: 3cardDraw(land) or cardDraw(folk) (defaults to count 3)
 * The count prefix (e.g. 3cardDraw) determines how many cards to draw
 */
class Op_cardDraw extends CountableOperation {
    public function getArgType() {
        return CountableOperation::TTYPE_TOKEN;
    }

    function getDeckType(): string {
        return $this->getParam(0, "land");
    }

    function getDeck(): string {
        return "deck_" . $this->getDeckType();
    }

    function getHandLocation(): string {
        $owner = $this->getOwner();
        return "hand_$owner";
    }

    function isDrawn(): bool {
        return $this->getDataField("drawn", false);
    }

    function isDeckEmpty(): bool {
        $deck = $this->getDeck();
        $count = $this->game->tokens->db->countTokensInLocation($deck);
        return $count == 0;
    }

    function getPossibleMoves() {
        $handLocation = $this->getHandLocation();
        $deckType = $this->getDeckType();

        if (!$this->isDrawn()) {
            // First phase: check if deck has cards
            if ($this->isDeckEmpty()) {
                return ["q" => Material::ERR_NONE_LEFT];
            }
            return ["confirm"];
        }

        // Second phase: pick one card from hand
        $cards = $this->game->tokens->getTokensOfTypeInLocation("card_$deckType", $handLocation);

        $res = [];
        foreach ($cards as $card => $info) {
            $res[$card] = ["q" => 0];
        }
        return $res;
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $deckType = $this->getDeckType();
        $handLocation = $this->getHandLocation();

        if (!$this->isDrawn()) {
            // First phase: draw cards from deck to hand
            $deck = "deck_$deckType";
            $count = $this->getCount();

            $drawn = $this->game->tokens->db->pickTokensForLocation($count, $deck, $handLocation);

            if (count($drawn) > 0) {
                $this->game->notifyWithName(
                    "message",
                    clienttranslate('${player_name} draws ${count} cards'),
                    [
                        "count" => count($drawn),
                    ],
                    $this->getPlayerId()
                );
                $this->game->tokens->dbSetTokensLocation($drawn, $handLocation, 0, "*", ["_private" => true], $this->getPlayerId());
                // Continue to second phase
                $this->withDataField("drawn", true);
                $this->queue($this->getType(), $owner, $this->getData());
            }

            return;
        }

        // Second phase: player picked a card
        $cards = $this->game->tokens->getTokensOfTypeInLocation("card_$deckType", $handLocation);

        if (count($cards) == 0) {
            // Deck was empty, nothing to do
            return;
        }

        $selectedCard = $this->getCheckedArg();

        // Return remaining cards to bottom of deck
        $deck = $this->getDeck();
        foreach ($cards as $card => $info) {
            if ($card !== $selectedCard) {
                $extreme_pos = $this->game->tokens->db->getExtremePosition(false, $deck);
                $this->game->tokens->dbSetTokenLocation($card, $deck, $extreme_pos - 1, "");
            }
        }

        // Capitalize first letter: land -> Land, folk -> Folk, etc.
        $ccolor = ucfirst($deckType);
        $this->queue("card{$ccolor}", $owner, ["card" => $selectedCard], $this->getOpId());
    }

    public function getPrompt() {
        if (!$this->isDrawn()) {
            return clienttranslate('Confirm to draw ${count} cards');
        }
        return clienttranslate("Select a card to keep");
    }

    public function getUiArgs() {
        return ["buttons" => false];
    }

    public function requireConfirmation() {
        return true;
    }
}
