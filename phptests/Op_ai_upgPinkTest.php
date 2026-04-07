<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Operations\Op_ai_upgPink;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_ai_upgPinkTest extends TestCase {
    private GameUT $game;
    private const AI_COLOR = "ffffff";

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init(1);
        $this->game->debug_setupGameTables();
    }

    private function createOp(): Op_ai_upgPink {
        /** @var Op_ai_upgPink */
        $op = $this->game->machine->instanciateOperation("ai_upgPink", self::AI_COLOR);
        return $op;
    }

    /**
     * Set the most recent scheme card to control pink tile priority.
     * The scheme card's "p" field determines which pink tile the AI prioritises.
     */
    private function setSchemeCard(int $schemeNum): void {
        $card = "card_scheme_$schemeNum";
        $this->game->tokens->db->moveToken($card, "tableau_" . self::AI_COLOR, 2);
    }

    /**
     * Test that AI selects the pink tile matching the scheme card's priority.
     * Scheme card 6 has p=1, pink tile upg_pink_42 has p=1 (Sea tag).
     */
    public function testSelectsTileMatchingSchemeCardPriority(): void {
        // Scheme card 6: p=1
        $this->setSchemeCard(6);

        $op = $this->createOp();
        $tile = $op->selectTile($op->getPossibleMoves());

        $this->assertEquals("upg_pink_42_1", $tile, "Should select pink tile with p=1 (Sea)");
    }

    /**
     * Test that AI selects pink tile with high priority (p=8, Observatory).
     * Scheme card 2 has p=8.
     */
    public function testSelectsTileWithHighPriority(): void {
        // Scheme card 2: p=8
        $this->setSchemeCard(2);

        $op = $this->createOp();
        $tile = $op->selectTile($op->getPossibleMoves());

        $this->assertEquals("upg_pink_44_1", $tile, "Should select pink tile with p=8 (Observatory)");
    }

    /**
     * Test that when the priority tile is already taken, AI picks next available clockwise.
     * Scheme card 2 has p=8 (Observatory). Remove that tile, should get p=9 (Comet).
     */
    public function testFallsBackToNextClockwiseWhenPriorityTaken(): void {
        $this->setSchemeCard(2);

        // Remove the priority tile (p=8)
        $this->game->tokens->db->moveToken("upg_pink_44_1", "tableau_" . self::AI_COLOR, 1);

        $op = $this->createOp();
        $tile = $op->selectTile($op->getPossibleMoves());

        $this->assertEquals("upg_pink_49_1", $tile, "Should fall back to next clockwise tile p=9 (Comet)");
    }

    /**
     * Test wrapping around: scheme card p=10, tile p=10 taken, should wrap to p=1.
     */
    public function testWrapsAroundWhenLastTileTaken(): void {
        // Scheme card 3: p=10
        $this->setSchemeCard(3);

        // Remove p=10 tile
        $this->game->tokens->db->moveToken("upg_pink_48_1", "tableau_" . self::AI_COLOR, 1);

        $op = $this->createOp();
        $tile = $op->selectTile($op->getPossibleMoves());

        $this->assertEquals("upg_pink_42_1", $tile, "Should wrap around to p=1 (Sea)");
    }

    /**
     * Test getPossibleMoves returns all 10 positions for pink tiles.
     */
    public function testGetPossibleMovesReturnsAllTenPositions(): void {
        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        $this->assertCount(10, $moves, "Should have 10 positions for pink tiles");
        $this->assertArrayHasKey("p1", $moves);
        $this->assertArrayHasKey("p10", $moves);
    }
}
