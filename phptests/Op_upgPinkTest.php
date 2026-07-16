<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Material;
use Bga\Games\wayfarers\Operations\Op_upgPink;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_upgPinkTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init(1);
        $this->game->debug_setupGameTables();
    }

    private function createOp(): Op_upgPink {
        /** @var Op_upgPink */
        $op = $this->game->machine->instanciateOperation("upgPink", PCOLOR);
        return $op;
    }

    /**
     * Occupy every placeable caravan cell, leaving all pink tiles in the mainarea.
     * Caravan is 6x3; (0,0) camel and (5,0) telescope are reserved, so 16 cells remain:
     * 5 yellow tiles (2x1) and 6 green tiles (1x1). State encodes x + y * 6 + 1.
     */
    private function fillCaravan(): void {
        $yellowPositions = [2, 4, 7, 9, 11];
        foreach ($yellowPositions as $i => $pos) {
            $this->game->tokens->db->moveToken("upg_yellow_" . ($i + 1) . "_1", "tableau_" . PCOLOR, $pos);
        }
        $greenTiles = ["upg_green_31_1", "upg_green_31_2", "upg_green_32_1", "upg_green_32_2", "upg_green_33_1", "upg_green_33_2"];
        foreach ($greenTiles as $i => $tile) {
            $this->game->tokens->db->moveToken($tile, "tableau_" . PCOLOR, 13 + $i);
        }
    }

    public function testOffersTilesWhenCaravanHasRoom(): void {
        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        $this->assertArrayNotHasKey("q", $moves, "Empty caravan should offer tiles, not an error");
        $this->assertContains("upg_pink_42_1", $moves, "Pink tiles in mainarea should be selectable");
        $this->assertFalse($op->noValidTargets(), "Empty caravan should have valid targets");
    }

    /**
     * Regression: a full caravan must be reported at tile selection, not after the tile
     * is chosen and the payment already queued.
     */
    public function testRejectsSelectionWhenCaravanIsFull(): void {
        $this->fillCaravan();

        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        $this->assertEquals(Material::ERR_NONE_LEFT, $moves["q"], "Full caravan should report no valid targets");
        $this->assertEquals("Caravan is full", $moves["err"]);
        $this->assertTrue($op->noValidTargets(), "Full caravan should leave no valid targets");
        $this->assertTrue($op->canSkip(), "Operation must be skippable when the caravan is full");
    }
}
