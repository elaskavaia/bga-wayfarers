<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Operations\Op_upgGreen;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

/**
 * Op_upgBase is abstract; Op_upgGreen is the cheapest concrete subclass (2 Silver) and inherits
 * getPossibleMoves unchanged, so it is what the shared behaviour is exercised through.
 */
final class Op_upgBaseTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init(2);
        $this->game->debug_setupGameTables();
    }

    /**
     * Step 1 lists tiles and resolve() then queues getPaymentOperation() unconditionally, so a player
     * who cannot pay commits and the machine parks on a payment with no target and no skip.
     * Unlike the worker and die slots the cost is not part of a rule string, so the paygain gate used
     * there does not apply here - this one needs Op_acquireBase::canAfford, as Op_cardBase does.
     */
    public function testTileIsNotOfferedWhenItCannotBePaidFor(): void {
        $color = PCOLOR;

        $this->setCoin($color, 2);
        $op = $this->game->machine->instantiateOperation("upgGreen", $color);
        $this->assertEquals("2n_coin", $op->getPaymentOperation(), "a green tile costs 2 Silver undiscounted");
        $this->assertNotEmpty($op->getArgs()["target"], "2 Silver covers it");

        $this->setCoin($color, 1);
        $this->assertEmpty($this->upgGreenTargets($color), "1 Silver does not cover it, so no tile is a legal choice");

        // Vetoing every entry must not trade one dead end for another
        $op = $this->game->machine->instantiateOperation("upgGreen", $color);
        $this->assertTrue($op->canSkip(), "an unaffordable upgrade is declined, not blocked on");
        $this->assertFalse($op->isVoid(), "and so it never parks in PlayerTurn");
    }

    private function setCoin(string $color, int $value): void {
        $this->game->tokens->db->setTokenState($this->game->tokens->getTrackerId($color, "coin"), $value);
    }

    /** Args are cached per instance, so each read needs a fresh operation. */
    private function upgGreenTargets(string $color): array {
        /** @var Op_upgGreen */
        $op = $this->game->machine->instantiateOperation("upgGreen", $color);
        return $op->getArgs()["target"];
    }
}
