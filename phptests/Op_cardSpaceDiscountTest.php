<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Operations\Op_cardSpace;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

/**
 * Test coin discount from caravan tiles when purchasing space cards via Capital Observatory
 */
final class Op_cardSpaceDiscountTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init(2);
        $this->game->debug_setupGameTables();
    }

    /**
     * Scenario: Player places die value 6 on Capital Observatory (card_home_10).
     * Capital Observatory dr = "(cardSpace/upgBlack)" — an or choice.
     * Player has 2 coinDis tiles covering column 5 (die value 6).
     * Player has 1 coin. Space card at position 1 costs 3.
     * With 2 coin discount, effective cost = 1, so player should be able to afford it.
     */
    public function testCoinDiscountAppliedWhenBuyingSpaceCard(): void {
        $color = PCOLOR;

        // Place a land card in player's tableau so there's a valid position for space card
        $this->game->tokens->db->moveToken("card_land_15", "tableau_$color", 2);

        // Give player 1 coin
        $this->game->tokens->db->setTokenState("tracker_coin_$color", 1);

        // Place two coinDis upgrade tiles covering column 5 (die value 6)
        // upg_yellow_5: 2x1, r=vp, r2=coinDis. Place at column 4 (state=5), right column (5) has coinDis
        $this->game->tokens->db->moveToken("upg_yellow_5_1", "tableau_$color", 5);
        // upg_blue_12: 2x1, r=diceMinus, r2=coinDis. Place at column 4 (state=5+6=11), right column (5) has coinDis
        $this->game->tokens->db->moveToken("upg_blue_12_1", "tableau_$color", 5 + 6);

        // Verify the caravan assets include 2 coinDis for die value 6
        $assets = $this->game->getCaravanAssetsForDie(6, $color);
        $this->assertEquals(2, $assets["coinDis"] ?? 0, "Should have 2 coinDis for die value 6");

        // Get a die and set its value to 6
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_$color");
        $dieKey = array_key_first($dice);
        $this->game->tokens->db->moveToken($dieKey, "card_home_10_$color", 6);

        // Instantiate cardSpace with die data (as placeDie would do)
        /** @var Op_cardSpace */
        $op = $this->game->machine->instanciateOperation("cardSpace", $color, ["die" => $dieKey]);

        // Check discount is correctly calculated
        $discount = $op->getCoinDiscount();
        $this->assertEquals(2, $discount, "Coin discount should be 2 from two coinDis tiles");

        // Get a space card from mainarea to check payment
        $spaceCards = $this->game->tokens->getTokensOfTypeInLocation("card_space", "mainarea");
        $spaceCardKey = array_key_first($spaceCards);
        $this->assertNotNull($spaceCardKey, "Should have space cards in mainarea after setup");
        // Check payment operation reflects the discount
        $cost = $op->getCost($spaceCardKey);
        $payop = $op->getPaymentOperation($spaceCardKey);
        $expectedPay = max(0, $cost - 2);
        $this->assertEquals("{$expectedPay}n_coin", $payop, "Payment should be cost($cost) - 2 discount");

        // Check player can afford the card (1 coin available, cost - 2 discount)
        $moves = $op->getPossibleMoves();
        $this->assertArrayHasKey($spaceCardKey, $moves, "Space card should be in possible moves");
        if ($expectedPay <= 1) {
            $this->assertTrue($moves[$spaceCardKey]["can"], "Player should afford space card with discount (pay $expectedPay, have 1 coin)");
        }
    }

    /**
     * Same scenario but cardSpace is reached through the or expression "(cardSpace/upgBlack)"
     * as it would be from Capital Observatory's dr field.
     * The die data must propagate through the or operation to the cardSpace delegate.
     */
    public function testCoinDiscountThroughOrExpression(): void {
        $color = PCOLOR;

        // Place a land card in player's tableau so there's a valid position for space card
        $this->game->tokens->db->moveToken("card_land_15", "tableau_$color", 2);

        // Give player 1 coin
        $this->game->tokens->db->setTokenState("tracker_coin_$color", 1);

        // Place two coinDis tiles covering column 5 (die value 6)
        $this->game->tokens->db->moveToken("upg_yellow_5_1", "tableau_$color", 5);
        $this->game->tokens->db->moveToken("upg_blue_12_1", "tableau_$color", 5 + 6);

        // Get a die and set its value to 6
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_$color");
        $dieKey = array_key_first($dice);
        $this->game->tokens->db->moveToken($dieKey, "card_home_10_$color", 6);

        // Instantiate through the or expression, same as Capital Observatory dr
        $op = $this->game->machine->instanciateOperation("(cardSpace/upgBlack)", $color, ["die" => $dieKey]);

        // Get possible moves — cardSpace (choice_0) should show the card as affordable
        $moves = $op->getPossibleMoves();
        $this->assertArrayHasKey("choice_0", $moves, "Should have cardSpace as choice_0");

        // The cardSpace delegate should not be void/errored
        $this->assertEquals(
            0,
            $moves["choice_0"]["q"],
            "cardSpace should not have error code, got: " . ($moves["choice_0"]["err"] ?? "none")
        );
    }

    /**
     * Without discount, player with 1 coin cannot afford a 3-cost space card.
     */
    public function testNoDiscountCannotAfford(): void {
        $color = PCOLOR;

        // Place a land card in player's tableau
        $this->game->tokens->db->moveToken("card_land_15", "tableau_$color", 2);

        // Give player 1 coin (not enough for cost 3+)
        $this->game->tokens->db->setTokenState("tracker_coin_$color", 1);

        // Get a die and set its value to 6 — but NO coinDis tiles
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_$color");
        $dieKey = array_key_first($dice);
        $this->game->tokens->db->moveToken($dieKey, "card_home_10_$color", 6);

        /** @var Op_cardSpace */
        $op = $this->game->machine->instanciateOperation("cardSpace", $color, ["die" => $dieKey]);

        $discount = $op->getCoinDiscount();
        $this->assertEquals(0, $discount, "No discount tiles means 0 discount");

        // All space cards in mainarea should be unaffordable (cheapest is 3, player has 1 coin, no discount)
        $moves = $op->getPossibleMoves();
        $spaceCards = $this->game->tokens->getTokensOfTypeInLocation("card_space", "mainarea");
        foreach ($spaceCards as $key => $info) {
            $this->assertArrayHasKey($key, $moves);
            $this->assertFalse($moves[$key]["can"], "Player should NOT afford $key (cost " . $op->getCost($key) . ") with 1 coin and no discount");
        }
    }
}
