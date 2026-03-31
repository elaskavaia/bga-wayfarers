<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Operations\Op_or;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_orTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init();
        $this->game->tokens->createTokens();
    }

    public function testAutoResolves_SingleNonVoidTrivial(): void {
        // coin/food — both trivial, both non-void, but isTrivial is false (2 non-void)
        // so auto should NOT resolve
        $op = $this->game->machine->instanciateOperation("coin/food", PCOLOR);
        $this->assertInstanceOf(Op_or::class, $op);

        $result = $op->auto();
        $this->assertFalse($result, "Op_or should not auto-resolve when 2+ non-void delegates");
    }

    public function testAutoResolves_AllVoidExceptOneTrivial(): void {
        // If only one delegate is non-void and it's trivial, isTrivial returns true
        // We simulate by using an "or" with one option that's a simple gain
        /** @var Op_or */
        $op = $this->game->machine->instanciateOperation("or", PCOLOR);
        $sub = $this->game->machine->instanciateOperation("coin", PCOLOR);
        $op->withDelegate($sub);
        $op->saveToDb(1, true);

        $this->assertTrue($op->isTrivial(), "Op_or with single trivial delegate should be trivial");

        $coinBefore = $this->game->tokens->getTrackerValue(PCOLOR, "coin");
        $this->game->machine->dispatchAll();
        $coinAfter = $this->game->tokens->getTrackerValue(PCOLOR, "coin");

        $this->assertEquals($coinBefore + 1, $coinAfter, "Coin should be gained exactly once");

        $ops = $this->game->machine->db->getOperations();
        $this->assertEmpty($ops, "All operations should be resolved");
    }

    public function testAutoResolves_ZeroDelegates(): void {
        /** @var Op_or */
        $op = $this->game->machine->instanciateOperation("or", PCOLOR);
        $this->assertTrue($op->canSkip(), "Op_or with no delegates should be skippable");
    }

    public function testIsTrivial_TwoNonVoidDelegates(): void {
        $op = $this->game->machine->instanciateOperation("coin/food", PCOLOR);
        $this->assertFalse($op->isTrivial(), "Op_or with 2 non-void delegates should not be trivial");
    }

    public function testIsTrivial_SingleNonVoidTrivialDelegate(): void {
        /** @var Op_or */
        $op = $this->game->machine->instanciateOperation("or", PCOLOR);
        $op->withDelegate($this->game->machine->instanciateOperation("coin", PCOLOR));
        $this->assertTrue($op->isTrivial(), "Op_or with single trivial delegate should be trivial");
    }

    public function testIsTrivial_SingleNonTrivialDelegate(): void {
        /** @var Op_or */
        $op = $this->game->machine->instanciateOperation("or", PCOLOR);
        $op->withDelegate($this->game->machine->instanciateOperation("cardFolk", PCOLOR));
        $this->assertFalse($op->isTrivial(), "Op_or with single non-trivial delegate should not be trivial");
    }

    public function testIsTrivial_NoDelegates(): void {
        /** @var Op_or */
        $op = $this->game->machine->instanciateOperation("or", PCOLOR);
        $this->assertTrue($op->isTrivial(), "Op_or with no delegates should be trivial");
    }

    public function testGetOperator(): void {
        $op = $this->game->machine->instanciateOperation("coin/food", PCOLOR);
        $this->assertInstanceOf(Op_or::class, $op);
        $this->assertEquals("/", $op->getOperator());
    }

    public function testGetIconicName_InfBlueFood(): void {
        $op = $this->game->machine->instanciateOperation("infBlue/food", PCOLOR);
        $iconicName = $op->getIconicName();
        $this->assertEquals("[wicon_inf_blue] / [wicon_food]", $iconicName);
    }

    public function testGetOpName_NoIconMarkup(): void {
        $op = $this->game->machine->instanciateOperation("coin/food", PCOLOR);
        $opName = $op->getOpName();
        $flat = is_array($opName) ? GameUT::format_string_recursive($opName["log"], $opName["args"]) : $opName;
        $this->assertEquals("Gain Silver / Gain Provisions", $flat);
    }

    public function testGetTypeFullExpr(): void {
        $op = $this->game->machine->instanciateOperation("coin/food", PCOLOR);
        $this->assertEquals("coin/food", $op->getTypeFullExpr());
    }

    public function testGetTypeFullExpr_Three(): void {
        $op = $this->game->machine->instanciateOperation("coin/food/coin", PCOLOR);
        $this->assertEquals("coin/food/coin", $op->getTypeFullExpr());
    }

    public function testCountIsOne(): void {
        $op = $this->game->machine->instanciateOperation("coin/food", PCOLOR);
        $this->assertEquals(1, $op->getCount(), "Op_or default count should be 1");
        $this->assertEquals(1, $op->getMinCount(), "Op_or default minCount should be 1");
    }

    public function testResolve_ChooseFirst(): void {
        /** @var Op_or */
        $op = $this->game->machine->instanciateOperation("coin/food", PCOLOR);
        $op->saveToDb(1, true);

        $coinBefore = $this->game->tokens->getTrackerValue(PCOLOR, "coin");
        $foodBefore = $this->game->tokens->getTrackerValue(PCOLOR, "food");

        $op->action_resolve(["target" => "choice_0"]);
        $this->game->machine->dispatchAll();

        $coinAfter = $this->game->tokens->getTrackerValue(PCOLOR, "coin");
        $foodAfter = $this->game->tokens->getTrackerValue(PCOLOR, "food");

        $this->assertEquals($coinBefore + 1, $coinAfter, "Coin should be gained");
        $this->assertEquals($foodBefore, $foodAfter, "Food should not be gained");
    }

    public function testResolve_ChooseSecond(): void {
        /** @var Op_or */
        $op = $this->game->machine->instanciateOperation("coin/food", PCOLOR);
        $op->saveToDb(1, true);

        $coinBefore = $this->game->tokens->getTrackerValue(PCOLOR, "coin");
        $foodBefore = $this->game->tokens->getTrackerValue(PCOLOR, "food");

        $op->action_resolve(["target" => "choice_1"]);
        $this->game->machine->dispatchAll();

        $coinAfter = $this->game->tokens->getTrackerValue(PCOLOR, "coin");
        $foodAfter = $this->game->tokens->getTrackerValue(PCOLOR, "food");

        $this->assertEquals($coinBefore, $coinAfter, "Coin should not be gained");
        $this->assertEquals($foodBefore + 1, $foodAfter, "Food should be gained");
    }

    public function testGetPossibleMoves_TwoOptions(): void {
        $op = $this->game->machine->instanciateOperation("coin/food", PCOLOR);
        $moves = $op->getPossibleMoves();
        $this->assertArrayHasKey("choice_0", $moves);
        $this->assertArrayHasKey("choice_1", $moves);
        $this->assertCount(2, $moves);
    }
}
