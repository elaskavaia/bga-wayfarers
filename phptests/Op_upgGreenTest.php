<?php

declare(strict_types=1);

use Bga\GameFramework\NotificationMessage;
use Bga\Games\wayfarers\Operations\Op_upgGreen;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the fix for BGA #229172 ("Error message due to discounting to 0").
 *
 * Capital Reserve (card ref 13, action "(upgGreen/2coin)") buys a green Upgrade Tile
 * that costs 2 coins. When caravan coin discounts bring the cost to 0, Op_upgGreen
 * returns "0n_coin" as the payment op. Op_pay::getIconicName() used to emit a literal
 * '${count} x [wicon_coin]' for any count outside 1-3; Op_upgBase::getPrompt() folds that
 * into the message arg "cost" without supplying a matching "count" key, so the nested
 * placeholder never resolved and the client reported:
 *   "Invalid or missing substitution argument for log message: ... could not find key 'count'".
 * The count is now interpolated directly, as Op_gain::getIconicName() already did.
 */
final class Op_upgGreenTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init(2);
        $this->game->debug_setupGameTables();
    }

    /**
     * Arrange 2 coinDis tiles covering column 5 (die value 6), so a green tile
     * (base cost 2) is discounted to 0.
     */
    private function createDiscountedUpgGreen(): Op_upgGreen {
        $color = PCOLOR;

        // Two coinDis upgrade tiles covering column 5 (die value 6), same as the space-discount test.
        $this->game->tokens->db->moveToken("upg_yellow_5_1", "tableau_$color", 5);
        $this->game->tokens->db->moveToken("upg_blue_12_1", "tableau_$color", 5 + 6);

        $assets = $this->game->getCaravanAssetsForDie(6, $color);
        $this->assertEquals(2, $assets["coinDis"] ?? 0, "Should have 2 coinDis for die value 6");

        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_$color");
        $dieKey = array_key_first($dice);
        $this->game->tokens->db->setTokenState($dieKey, 6);

        /** @var Op_upgGreen */
        $op = $this->game->machine->instanciateOperation("upgGreen", $color, ["die" => $dieKey]);
        return $op;
    }

    public function testDiscountToZeroPromptHasNoUnresolvedPlaceholder(): void {
        $op = $this->createDiscountedUpgGreen();

        $this->assertEquals(2, $op->getCoinDiscount(), "Green cost 2 fully discounted by 2 coinDis tiles");
        $this->assertEquals("0n_coin", $op->getPaymentOperation(), "Discount-to-0 yields a zero-count pay op");

        $payopName = $op->getExtraArgs()["payop_name"];
        $this->assertStringNotContainsString('${', $payopName, "payop_name must not carry an unresolved placeholder");
        $this->assertStringContainsString("0 x [wicon_coin]", $payopName, "Zero cost renders the count directly");

        $prompt = $op->getPrompt();
        $this->assertInstanceOf(NotificationMessage::class, $prompt, "Buy prompt is a NotificationMessage");
        $this->assertArrayHasKey("cost", $prompt->args);
        $this->assertStringNotContainsString('${', $prompt->args["cost"], "The 'cost' arg must be fully resolved");
    }

    /** Counts outside the 1-3 iconic range took the same broken placeholder path. */
    public function testPayIconicNameResolvesCountForAllAmounts(): void {
        foreach ([0, 4, 5] as $count) {
            $name = $this->game->machine->instanciateOperation("{$count}n_coin", PCOLOR)->getIconicName();
            $this->assertEquals("$count x [wicon_coin]", $name, "Pay $count coin renders its count");
        }
        foreach ([1, 2, 3] as $count) {
            $name = $this->game->machine->instanciateOperation("{$count}n_coin", PCOLOR)->getIconicName();
            $this->assertEquals(str_repeat("[wicon_coin]", $count), $name, "Pay $count coin renders repeated icons");
        }
    }

    /**
     * Blue/yellow/black tiles pay via "{c}n_coin/2n_infX". Op_or joins sub names into one
     * string and cannot carry per-sub args, so a placeholder there would be unresolvable.
     */
    public function testOrPaymentIconicNameResolvesCount(): void {
        $op = $this->game->machine->instanciateOperation("0n_coin/2n_infBlue", PCOLOR);
        $this->assertStringNotContainsString('${', $op->getIconicName(), "Joined pay name has no unresolved placeholder");
        $this->assertStringContainsString("0 x [wicon_coin]", $op->getIconicName(), "Zero coin side renders its count");
    }
}
