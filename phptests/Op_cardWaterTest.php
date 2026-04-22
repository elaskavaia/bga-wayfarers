<?php

declare(strict_types=1);

use Tests\GameUT;
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
     * Place a water card directly in the player's tableau at the given slot
     * (mimicking Op_cardBase::placeCard which uses state = count + 2).
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
        $op = $this->game->machine->instanciateOperation("cardWater", $color, [
            "card" => "card_water_44",
            "params" => "free",
        ]);
        $this->game->fakeUserAction($op, "card_water_44");

        // Coin tracker must NOT have incremented synchronously
        $coinAfter = $this->game->tokens->getTrackerValue($color, "coin");
        $this->assertEquals($coinBefore, $coinAfter, "Coin link bonus must not fire before cardInteract resolves");

        // The queue must contain both cardInteract and a coin op, with cardInteract at a LOWER rank
        $ops = $this->game->machine->db->getOperations();
        $cardInteractRank = null;
        $coinRank = null;
        foreach ($ops as $row) {
            if ($row["type"] === "cardInteract" && $cardInteractRank === null) {
                $cardInteractRank = (int) $row["rank"];
            }
            if ($row["type"] === "coin" && $coinRank === null) {
                $coinRank = (int) $row["rank"];
            }
        }
        $this->assertNotNull($cardInteractRank, "cardInteract must be queued");
        $this->assertNotNull($coinRank, "coin link bonus must be queued");
        $this->assertLessThan($coinRank, $cardInteractRank, "cardInteract must run before coin link bonus");
    }

    /**
     * Food link bonus: prev card c2[2]='x' and placed card c1[2]='x'.
     * card_water_53 has c2=_xx_ — but c2[1]='x' too, so placing after it would also
     * trigger coin. Use card_water_60 c2=_xx_ for same reason? Same issue.
     * Find a pair that isolates food:
     *   prev c2[2]='x', c2[1]='_', c2[3]='_', c2[0]='_';
     *   placed c1[2]='x', c1[1]='_', c1[3]='_', c1[0]='_'
     * card_water_55 c2=__xx has c2[2]='x', c2[3]='x' (would also trigger infCard if c1[3]='x'),
     * paired with card_water_52 c1=x_x_ — c1[2]='x' ✓, c1[3]='_' ✓, c1[0]='x' but c2[0]='_' so no inf.
     * Result: food only.
     */
    public function testFoodLinkBonus_DoesNotFireBeforeCardInteract(): void {
        $color = PCOLOR;
        $this->placeWaterInTableau("card_water_55", 2);
        $this->putWaterInMainarea("card_water_52", 0);
        $this->putOpponentInfluenceOnCard("card_water_52");

        $foodBefore = $this->game->tokens->getTrackerValue($color, "food");

        /** @var \Bga\Games\wayfarers\Operations\Op_cardWater */
        $op = $this->game->machine->instanciateOperation("cardWater", $color, [
            "card" => "card_water_52",
            "params" => "free",
        ]);
        $this->game->fakeUserAction($op, "card_water_52");

        $foodAfter = $this->game->tokens->getTrackerValue($color, "food");
        $this->assertEquals($foodBefore, $foodAfter, "Food link bonus must not fire before cardInteract resolves");

        $ops = $this->game->machine->db->getOperations();
        $cardInteractRank = null;
        $foodRank = null;
        foreach ($ops as $row) {
            if ($row["type"] === "cardInteract" && $cardInteractRank === null) {
                $cardInteractRank = (int) $row["rank"];
            }
            if ($row["type"] === "food" && $foodRank === null) {
                $foodRank = (int) $row["rank"];
            }
        }
        $this->assertNotNull($cardInteractRank, "cardInteract must be queued");
        $this->assertNotNull($foodRank, "food link bonus must be queued");
        $this->assertLessThan($foodRank, $cardInteractRank, "cardInteract must run before food link bonus");
    }
}
