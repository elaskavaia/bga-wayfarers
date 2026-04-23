<?php

declare(strict_types=1);

use Tests\GameUT;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end test for card_home_2 (Capital City) die rule:
 *   2n_food:coin,cardLand
 *
 * The rule gains a Silver BEFORE acquiring a Land card, so when the land
 * card has opponent influence on it the player can spend that freshly-gained
 * coin to pay the opponent (instead of being forced to pay food they may
 * not have).
 */
final class Op_homeCapitalCityTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init();
        $this->game->tokens->createTokens();
    }

    /**
     * Full dispatch: player has 2 food, 0 coin. After paying 2 food and
     * gaining 1 coin, the only affordable way to pay the opponent's
     * influence on the land card is with the new coin. Player is prompted
     * to select the land card, then cardInteract auto-resolves with coin.
     */
    public function testCapitalCityRule_CoinGainedBeforeLandAllowsPayingOpponent(): void {
        $player = PCOLOR;
        $opp = BCOLOR;

        // Setup: player has exactly 2 food, 0 coin. Opponent has 0 of each.
        $this->game->tokens->db->setTokenState("tracker_food_$player", 2);
        $this->game->tokens->db->setTokenState("tracker_coin_$player", 0);
        $this->game->tokens->db->setTokenState("tracker_food_$opp", 0);
        $this->game->tokens->db->setTokenState("tracker_coin_$opp", 0);

        // card_land_10 has no immediate bonus (r is empty) — clean acquisition.
        $this->game->tokens->db->moveToken("card_land_10", "mainarea", 0);
        $this->game->tokens->db->moveToken("influence_{$opp}_1", "card_land_10");

        // Queue the dr rule exactly as card_home_2's die-placement action would.
        $dr = $this->game->getRulesFor("card_home_2", "dr");
        $this->game->machine->queue($dr, $player);

        // n_food and coin are trivial — dispatchAll will pause at cardLand
        // waiting for card selection.
        $this->game->machine->dispatchAll();

        // At this point, food is paid (0) and coin is gained (1).
        $this->assertEquals(0, $this->game->tokens->getTrackerValue($player, "food"),
            "Food should already have been paid before cardLand prompt");
        $this->assertEquals(1, $this->game->tokens->getTrackerValue($player, "coin"),
            "Coin should have been gained BEFORE cardLand prompt");

        $top = $this->game->machine->createTopOperationFromDbForOwner(null);
        $this->assertNotNull($top);
        $this->assertEquals("cardLand", $top->getType(), "Should be paused at cardLand selection");

        // Select the land card
        $this->game->fakeUserAction($top, "card_land_10");
        $this->game->machine->dispatchAll();

        // cardInteract should have auto-resolved using the only affordable option (coin)
        $this->assertEquals(0, $this->game->tokens->getTrackerValue($player, "coin"),
            "Player gained 1 coin then spent it paying opponent");
        $this->assertEquals(1, $this->game->tokens->getTrackerValue($opp, "coin"),
            "Opponent should have received 1 coin for influence");

        // Land card should be in player's tableau
        $loc = $this->game->tokens->db->getTokenLocation("card_land_10");
        $this->assertEquals("tableau_$player", $loc, "Land card should be acquired");

        // Influence should be returned to opponent's tableau
        $infLoc = $this->game->tokens->db->getTokenLocation("influence_{$opp}_1");
        $this->assertEquals("tableau_$opp", $infLoc, "Influence should return to opponent");

        // No lingering operations
        $ops = $this->game->machine->db->getOperations();
        $this->assertEmpty($ops, "All operations should have resolved");
    }

    /**
     * When the player CAN still afford food at the cardInteract prompt,
     * verify they get to choose coin explicitly. Starts with 3 food, 0 coin:
     * pay 2 food → 1 food left; gain 1 coin → 1 coin. Both options affordable,
     * player picks coin.
     */
    public function testCapitalCityRule_PlayerChoosesCoinExplicitly(): void {
        $player = PCOLOR;
        $opp = BCOLOR;

        $this->game->tokens->db->setTokenState("tracker_food_$player", 3);
        $this->game->tokens->db->setTokenState("tracker_coin_$player", 0);
        $this->game->tokens->db->setTokenState("tracker_food_$opp", 0);
        $this->game->tokens->db->setTokenState("tracker_coin_$opp", 0);

        $this->game->tokens->db->moveToken("card_land_10", "mainarea", 0);
        $this->game->tokens->db->moveToken("influence_{$opp}_1", "card_land_10");

        // Queue the dr rule exactly as card_home_2's die-placement action would.
        $dr = $this->game->getRulesFor("card_home_2", "dr");
        $this->game->machine->queue($dr, $player);

        // Pause at cardLand selection
        $this->game->machine->dispatchAll();
        $top = $this->game->machine->createTopOperationFromDbForOwner(null);
        $this->assertNotNull($top);
        $this->assertEquals("cardLand", $top->getType());
        $this->game->fakeUserAction($top, "card_land_10");

        // Now pause at cardInteract prompt (both options affordable → no auto-resolve)
        $this->game->machine->dispatchAll();
        $top = $this->game->machine->createTopOperationFromDbForOwner(null);
        $this->assertNotNull($top, "Should be paused at cardInteract");
        $this->assertEquals("cardInteract", $top->getType());

        // Player should have 1 food and 1 coin at this point
        $this->assertEquals(1, $this->game->tokens->getTrackerValue($player, "food"));
        $this->assertEquals(1, $this->game->tokens->getTrackerValue($player, "coin"));

        // Player chooses to pay with coin
        $this->game->fakeUserAction($top, "tracker_coin_$player");
        $this->game->machine->dispatchAll();

        $this->assertEquals(0, $this->game->tokens->getTrackerValue($player, "coin"),
            "Player should have spent the coin");
        $this->assertEquals(1, $this->game->tokens->getTrackerValue($player, "food"),
            "Food should be untouched after the pay step");
        $this->assertEquals(1, $this->game->tokens->getTrackerValue($opp, "coin"),
            "Opponent should have received 1 coin");

        $ops = $this->game->machine->db->getOperations();
        $this->assertEmpty($ops, "All operations should have resolved");
    }
}
