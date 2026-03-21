<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Operations\Op_order;
use Bga\Games\wayfarers\Tests\GameUT;
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

    public function testAutoResolves_SingleDelegate(): void {
        // Single non-trivial delegate — no choice needed
        $op = $this->game->machine->instanciateOperation("cardFolk+coin", PCOLOR);
        // coin is trivial, cardFolk is not — after stripping trivial, only 1 remains
        $result = $op->auto();
        $this->assertTrue($result, "Op_order should auto-resolve when only 1 non-trivial delegate remains");

        $ops = $this->game->machine->db->getOperations();
        $opTypes = array_map(fn($o) => $o["type"], array_values($ops));
        $this->assertContains("coin", $opTypes, "Trivial delegate should be queued");
        $this->assertContains("cardFolk", $opTypes, "Remaining delegate should be queued");
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

    public function testAutoStripsTrivial_KeepsNonTrivial(): void {
        // coin (trivial) + infMove (non-trivial) + food (trivial) + cardFolk (non-trivial)
        $op = $this->game->machine->instanciateOperation("coin+infMove+food+cardFolk", PCOLOR);
        $result = $op->auto();
        $this->assertFalse($result, "Should not auto-resolve with 2 non-trivial delegates");

        // Trivial ones should be queued
        $ops = $this->game->machine->db->getOperations();
        $opTypes = array_map(fn($o) => $o["type"], array_values($ops));
        $this->assertContains("coin", $opTypes, "Trivial coin should be queued");
        $this->assertContains("food", $opTypes, "Trivial food should be queued");
    }
}
