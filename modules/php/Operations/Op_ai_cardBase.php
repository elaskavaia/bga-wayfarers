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

use Bga\Games\wayfarers\OpCommon\AiOperation;

use function Bga\Games\wayfarers\getPart;

class Op_ai_cardBase extends AiOperation {
    public function getPossibleMoves() {
        $cardType = $this->getCardType();
        $tokens = $this->game->tokens->getTokensOfTypeInLocationWithChildren("card_$cardType", "mainarea", null, "token_state");
        $moves = array_keys($tokens);

        // Filter out denied (denied) cards
        $skip = $this->getDataField("denied", []);
        if (!empty($skip)) {
            $moves = array_values(array_diff($moves, $skip));
        }

        return $moves;
    }

    function getCardType() {
        $t = $this->getType();
        $t = str_replace("ai_card", "", $t);
        return lcfirst($t);
    }

    /**
     * Select card by priority, filtering out denied cards.
     * If all cards were denied (cannot fully deny a type), resets denied list and wraps around.
     */
    function selectCard(): string {
        $moves = $this->getPossibleMoves();
        $cardType = $this->getCardType();

        if (empty($moves)) {
            // All cards were denied — cannot fully deny a type, reset denied list
            $this->withDataField("denied", []);
            $moves = $this->getPossibleMoves();
        }

        if ($cardType == "insp") {
            $prio = $this->getResourceMarkerRules("c");
            $this->notifyMessage(
                clienttranslate('${player_name} acquires card at position ${priority} based on inspiration priority of resource track'),
                ["priority" => $prio]
            );
        } else {
            $prio = $this->getPositionPriority();
            $this->notifyMessage(clienttranslate('${player_name} acquires card at position ${priority} based on silver values'), [
                "priority" => $prio,
            ]);
        }

        $card = $moves[$prio - 1] ?? "";
        $this->game->systemAssert("Missing card on main display $prio", $card);
        return $card;
    }

    /**
     * Commit card acquisition: handle influence interaction and move card to tableau.
     */
    function commitCard(string $card): void {
        $owner = $this->getOwner();
        $this->queue("ai_cardInteract", $owner, ["card" => $card]);
        // arrange cards with state of -2
        $this->dbSetTokenLocation($card, "tableau_$owner", -2);
    }

    // Acquire Card (use sum value for position priority)
    public function auto(): bool {
        $owner = $this->getOwner();

        // If a card was already confirmed (player allowed interaction), commit directly
        $confirmedCard = $this->getDataField("confirmed_card");
        if ($confirmedCard) {
            $this->commitCard($confirmedCard);
            return true;
        }

        $card = $this->selectCard();

        // Check for opponent influence before committing
        $inf = $this->game->tokens->getTokensOfTypeInLocation("influence", $card);
        $infKey = array_key_first($inf);
        if ($infKey) {
            $infOwner = getPart($infKey, 1);
            if ($infOwner !== $owner) {
                // Opponent influence found — ask player to allow or deny
                $this->queue("ai_cardInteractChoice", $infOwner, [
                    "card" => $card,
                    "caller" => $this->getTypeFullExpr(),
                    "caller_data" => [
                        "denied" => $this->getDataField("denied", []),
                        "buy" => true,
                        "confirmed_card" => $card,
                    ],
                ]);
                return true;
            }
        }

        // No opponent influence — commit immediately
        $this->commitCard($card);
        return true;
    }
}
