<?php

declare(strict_types=1);

use Tests\GameUT;
use Bga\Games\wayfarers\OpCommon\Operation;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Op_cardWater, specifically the side-matching "link" bonuses.
 *
 * Bug: previously, coin/food link bonuses were applied synchronously via
 * effect_incCount() during resolve(), so they landed BEFORE the queued
 * cardInteract (payment to opponent). These tests ensure the link bonuses
 * are queued behind cardInteract.
 */
final class Op_cardWaterTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init();
        $this->game->tokens->createTokens();
    }

    /**
     * Place a water card in the player's tableau. `grantSideMatchingBonuses`
     * pairs the placed card with the card at $slot-1, so pass slot >= 2
     * to ensure there IS a previous card.
     */
    private function placeWaterInTableau(string $cardId, int $slot): void {
        $this->game->tokens->db->moveToken($cardId, "tableau_" . PCOLOR, $slot);
    }

    private function putWaterInMainarea(string $cardId, int $position = 0): void {
        $this->game->tokens->db->moveToken($cardId, "mainarea", $position);
    }

    private function putOpponentInfluenceOnCard(string $cardId): string {
        // BCOLOR is the opponent in the 2-player setup (see GameUT)
        $infId = "influence_" . BCOLOR . "_1";
        $this->game->tokens->db->moveToken($infId, $cardId);
        return $infId;
    }

    /** Rank of the queued `order` row offering the given bonus delegate, or null */
    private function getBonusOrderRank(string $bonusType): ?int {
        $row = $this->game->machine->findOperation("order", PCOLOR);
        if ($row === null) {
            return null;
        }
        $subTypes = array_column(Operation::decodeData($row["data"])["args"] ?? [], "type");
        return in_array($bonusType, $subTypes) ? (int) $row["rank"] : null;
    }

    /**
     * Coin link bonus: prev card c2[1]='x' and placed card c1[1]='x'.
     * card_water_41 has c2=bx_x (coin on right at pos 1).
     * card_water_44 has c1=_xx_ (coin on left at pos 1) — triggers coin link.
     */
    public function testCoinLinkBonus_DoesNotFireBeforeCardInteract(): void {
        $color = PCOLOR;
        $this->placeWaterInTableau("card_water_41", 2);
        $this->putWaterInMainarea("card_water_44", 0);
        $this->putOpponentInfluenceOnCard("card_water_44");

        $coinBefore = $this->game->tokens->getTrackerValue($color, "coin");

        /** @var \Bga\Games\wayfarers\Operations\Op_cardWater */
        $op = $this->game->machine->instantiateOperation("cardWater", $color, [
            "card" => "card_water_44",
            "params" => "free"
        ]);
        $this->game->fakeUserAction($op, "card_water_44");

        // Coin tracker must NOT have incremented synchronously
        $coinAfter = $this->game->tokens->getTrackerValue($color, "coin");
        $this->assertEquals($coinBefore, $coinAfter, "Coin link bonus must not fire before cardInteract resolves");

        // The queue must contain cardInteract and an order offering the coin bonus, cardInteract at a LOWER rank
        $cardInteractRank = null;
        foreach ($this->game->machine->db->getOperations() as $row) {
            if ($row["type"] === "cardInteract") {
                $cardInteractRank = (int) $row["rank"];
                break;
            }
        }
        $coinRank = $this->getBonusOrderRank("coin");
        $this->assertNotNull($cardInteractRank, "cardInteract must be queued");
        $this->assertNotNull($coinRank, "coin link bonus must be queued");
        $this->assertLessThan($coinRank, $cardInteractRank, "cardInteract must run before coin link bonus");
    }

    /**
     * Food link bonus: prev card_water_55 (c2=__xx) + placed card_water_52 (c1=x_x_)
     * triggers the food position (c2[2]=x, c1[2]=x) without triggering any other.
     */
    public function testFoodLinkBonus_DoesNotFireBeforeCardInteract(): void {
        $color = PCOLOR;
        $this->placeWaterInTableau("card_water_55", 2);
        $this->putWaterInMainarea("card_water_52", 0);
        $this->putOpponentInfluenceOnCard("card_water_52");

        $foodBefore = $this->game->tokens->getTrackerValue($color, "food");

        /** @var \Bga\Games\wayfarers\Operations\Op_cardWater */
        $op = $this->game->machine->instantiateOperation("cardWater", $color, [
            "card" => "card_water_52",
            "params" => "free"
        ]);
        $this->game->fakeUserAction($op, "card_water_52");

        $foodAfter = $this->game->tokens->getTrackerValue($color, "food");
        $this->assertEquals($foodBefore, $foodAfter, "Food link bonus must not fire before cardInteract resolves");

        $cardInteractRank = null;
        foreach ($this->game->machine->db->getOperations() as $row) {
            if ($row["type"] === "cardInteract") {
                $cardInteractRank = (int) $row["rank"];
                break;
            }
        }
        $foodRank = $this->getBonusOrderRank("food");
        $this->assertNotNull($cardInteractRank, "cardInteract must be queued");
        $this->assertNotNull($foodRank, "food link bonus must be queued");
        $this->assertLessThan($foodRank, $cardInteractRank, "cardInteract must run before food link bonus");
    }
}
