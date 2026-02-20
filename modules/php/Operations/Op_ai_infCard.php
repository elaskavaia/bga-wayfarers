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
use Bga\Games\wayfarers\OpCommon\AiOperation;

use function Bga\Games\wayfarers\custom_array_rotate;

/**
 * AI Influence on Card
 *
 * RULES:
 * Use resource track color priority for card type selection
 * Use sum value for positional selection
 */
class Op_ai_infCard extends AiOperation {
    /**
     * Get mainarea cards of the given type
     */
    function getCards(string $cardType): array {
        $cards = $this->game->tokens->getTokensOfTypeInLocationWithChildren("card_$cardType", "mainarea", null, "token_state");

        $res = [];
        foreach ($cards as $cardId => $info) {
            // Card is available if it has no children (no influence on it)
            $state = $info["state"];
            $argkey = "p$state";
            if (count($info["children"]) == 0) {
                $res[$argkey] = $info + ["q" => Material::RET_OK];
            } else {
                $res[$argkey] = $info + ["q" => Material::ERR_OCCUPIED];
            }
        }

        return $res;
    }

    /**
     * Select which card to place influence on using color and positional priority
     */
    function selectTargetCard(): ?array {
        // Get color priority from resource track
        $colorPriority = $this->getColorPriority();
        // Use sum value for positional selection
        $prio = $this->getPositionPriority();
        $dir = $this->getNextPositionPriorityDirection($prio);

        // Try each color in priority order
        foreach ($colorPriority as $color) {
            $cardType = self::COLOR_CARD_TYPE[$color] ?? null;
            $cards = $this->getCards($cardType);
            $sorted = custom_array_rotate($cards, $prio - 1, $dir);

            foreach ($sorted as $state => $info) {
                if ($info["q"] == Material::RET_OK) {
                    return ["card" => $info["key"], "card_type_name" => $cardType, "pos" => $prio];
                }
            }
        }

        return null;
    }

    public function isVoid(): bool {
        // Check if there are any available cards to influence
        return $this->selectTargetCard() === null;
    }

    /**
     * Auto-resolve: AI places influence on a card
     */
    public function auto(): bool {
        $owner = $this->getOwner();
        $targetCardInfo = $this->selectTargetCard();

        if ($targetCardInfo === null) {
            $this->notifyMessage(clienttranslate('${player_name} cannot place influence on any card'));
            return true;
        }

        // Get or create influence token (AI has unlimited influence)
        $influence = $this->game->tokens->getTokensOfTypeInLocation("influence_$owner", "tableau_$owner");
        $influenceKey = array_key_first($influence);

        if (!$influenceKey) {
            $influenceKey = $this->game->tokens->db->createTokenAutoInc("influence_$owner", "tableau_$owner", 0);
        }

        // Place influence on the selected card
        $this->dbSetTokenLocation(
            $influenceKey,
            $targetCardInfo["card"],
            0,
            clienttranslate('${player_name} places ${token_name} on ${place_name} (${card_type_name} at ${pos})'),
            $targetCardInfo
        );

        return true;
    }
}
