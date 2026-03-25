<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Operations\Op_order;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_orderTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init();
        $this->game->tokens->createTokens();
    }

    public function testAutoResolves_AllTrivial(): void {
        // coin and food are both trivial
        $op = $this->game->machine->instanciateOperation("coin+food", PCOLOR);
        $this->assertInstanceOf(Op_order::class, $op);

        $result = $op->auto();
        $this->assertTrue($result, "Op_order should auto-resolve when all delegates are trivial");

        // Both should be queued
        $ops = $this->game->machine->db->getOperations();
        $opTypes = array_map(fn($o) => $o["type"], array_values($ops));
        $this->assertContains("coin", $opTypes);
        $this->assertContains("food", $opTypes);
    }

    public function testAutoDoesNotResolve_MixedTrivialAndNonTrivial(): void {
        // cardFolk (non-trivial) + coin (trivial) — user should choose order for all
        $op = $this->game->machine->instanciateOperation("cardFolk+coin", PCOLOR);
        $result = $op->auto();
        $this->assertFalse($result, "Op_order should not auto-resolve when mix of trivial and non-trivial delegates");
    }

    public function testAutoDoesNotResolve_MultipleNonTrivial(): void {
        // infMove and cardFolk are both non-trivial
        $op = $this->game->machine->instanciateOperation("infMove+cardFolk", PCOLOR);
        $result = $op->auto();
        $this->assertFalse($result, "Op_order should not auto-resolve when 2+ non-trivial delegates remain");
    }

    public function testAutoResolves_ZeroDelegates(): void {
        // Edge case: order with no delegates
        /** @var Op_order */
        $op = $this->game->machine->instanciateOperation("order", PCOLOR);
        $result = $op->auto();
        $this->assertTrue($result, "Op_order with no delegates should auto-resolve");
    }

    public function testAutoDoesNotResolve_MixedMultiple(): void {
        // coin (trivial) + infMove (non-trivial) + food (trivial) + cardFolk (non-trivial)
        // All delegates should be kept for user ordering
        $op = $this->game->machine->instanciateOperation("coin+infMove+food+cardFolk", PCOLOR);
        $result = $op->auto();
        $this->assertFalse($result, "Op_order should not auto-resolve with mixed trivial/non-trivial delegates");
        $this->assertEquals("coin+infMove+food+cardFolk", $op->getTypeFullExpr(), "All delegates should remain");
    }

    public function testAutoResolves_SingleSeqDelegate(): void {
        // Simulate what Op_rest does: order with a single seq delegate (coin,food)
        /** @var Op_order */
        $op = $this->game->machine->instanciateOperation("order", PCOLOR);
        $op->withDelegate($this->game->machine->instanciateOperation("coin,food", PCOLOR));
        $op->saveToDb(1, true);

        // Dispatch and check that coin and food each resolve exactly once
        $coinBefore = $this->game->tokens->getTrackerValue(PCOLOR, "coin");
        $foodBefore = $this->game->tokens->getTrackerValue(PCOLOR, "food");
        $this->game->machine->dispatchAll();
        $coinAfter = $this->game->tokens->getTrackerValue(PCOLOR, "coin");
        $foodAfter = $this->game->tokens->getTrackerValue(PCOLOR, "food");

        $this->assertEquals($coinBefore + 1, $coinAfter, "Coin should be gained exactly once");
        $this->assertEquals($foodBefore + 1, $foodAfter, "Food should be gained exactly once");

        // No operations should remain
        $ops = $this->game->machine->db->getOperations();
        $this->assertEmpty($ops, "All operations should be resolved");
    }

    public function testIsTrivial_AllTrivialDelegates(): void {
        $op = $this->game->machine->instanciateOperation("coin+food", PCOLOR);
        $this->assertTrue($op->isTrivial(), "Op_order with all trivial delegates should be trivial");
    }

    public function testIsTrivial_NoDelegates(): void {
        /** @var Op_order */
        $op = $this->game->machine->instanciateOperation("order", PCOLOR);
        $this->assertTrue($op->isTrivial(), "Op_order with no delegates should be trivial");
    }

    public function testIsTrivial_OneNonTrivial(): void {
        $op = $this->game->machine->instanciateOperation("coin+cardFolk", PCOLOR);
        $this->assertFalse($op->isTrivial(), "Op_order with a non-trivial delegate should not be trivial");
    }

    public function testIsTrivial_AllNonTrivial(): void {
        $op = $this->game->machine->instanciateOperation("infMove+cardFolk", PCOLOR);
        $this->assertFalse($op->isTrivial(), "Op_order with all non-trivial delegates should not be trivial");
    }

    public function testCountIsOne(): void {
        $op = $this->game->machine->instanciateOperation("coin+food", PCOLOR);
        $this->assertEquals(1, $op->getCount(), "Op_order count should be 1");
        $this->assertEquals(1, $op->getMinCount(), "Op_order minCount should be 1");
    }

    public function testGetIconicName_CoinFood(): void {
        $op = $this->game->machine->instanciateOperation("coin+food", PCOLOR);
        $iconicName = $op->getIconicName();
        $this->assertEquals("[wicon_coin] [wicon_food]", $iconicName);
    }

    public function testGetOpName_NoIconMarkup(): void {
        $op = $this->game->machine->instanciateOperation("coin+food", PCOLOR);
        $opName = $op->getOpName();
        $flat = is_array($opName) ? GameUT::format_string_recursive($opName["log"], $opName["args"]) : $opName;
        $this->assertEquals("Gain Silver + Gain Provisions", $flat);
    }

    public function testGetIconicName_DiffersFromOpName(): void {
        $op = $this->game->machine->instanciateOperation("infMove+cardFolk", PCOLOR);
        $opName = $op->getOpName();
        $flatOpName = is_array($opName) ? GameUT::format_string_recursive($opName["log"], $opName["args"]) : $opName;
        $iconicName = $op->getIconicName();
        $this->assertNotEquals($flatOpName, $iconicName, "Iconic name and OpName should differ for composite operations");
    }
}
