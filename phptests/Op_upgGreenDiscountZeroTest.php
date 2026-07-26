<?php

declare(strict_types=1);

use Bga\GameFramework\NotificationMessage;
use Bga\Games\wayfarers\Operations\Op_upgGreen;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

/**
 * Reproduces BGA #229172 ("Error message due to discounting to 0").
 *
 * Capital Reserve (card ref 13, action "(upgGreen/2coin)") buys a green Upgrade Tile
 * that costs 2 coins. When caravan coin discounts bring the cost to 0, Op_upgGreen
 * returns "0n_coin" as the payment op. Op_pay::getIconicName() for count 0 falls
 * through to the literal '${count} x [wicon_coin]', which Op_upgBase::getPrompt() then
 * substitutes into the message arg "cost" WITHOUT supplying a matching "count" key.
 * The nested '${count}' placeholder is never resolved, producing the client error:
 *   "Invalid or missing substitution argument for log message: ... could not find key 'count'".
 */
final class Op_upgGreenDiscountZeroTest extends TestCase {
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

    // Documents buggy behavior for BGA #229172; flip this assertion once the fix lands
    // (the ${count} should resolve or the free/0 path should not include it).
    public function testDiscountToZeroPromptHasUnresolvedCountPlaceholder(): void {
        $op = $this->createDiscountedUpgGreen();

        $this->assertEquals(2, $op->getCoinDiscount(), "Green cost 2 fully discounted by 2 coinDis tiles");
        $this->assertEquals("0n_coin", $op->getPaymentOperation(), "Discount-to-0 yields a zero-count pay op");

        $payopName = $op->getExtraArgs()["payop_name"];
        $this->assertStringContainsString('${count}', $payopName, "payop_name carries a nested \${count} placeholder");

        $prompt = $op->getPrompt();
        $this->assertInstanceOf(NotificationMessage::class, $prompt, "Buy prompt is a NotificationMessage");

        // BUG: cost value contains ${count}, but args only supply "cost" (no "count").
        $this->assertArrayHasKey("cost", $prompt->args);
        $this->assertArrayNotHasKey("count", $prompt->args, "No 'count' key is provided to resolve the nested placeholder");
        $this->assertStringContainsString('${count}', $prompt->args["cost"], "The 'cost' arg still holds an unresolved \${count}");
    }
}
