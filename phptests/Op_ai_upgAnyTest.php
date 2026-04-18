<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Operations\Op_ai_upgAny;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_ai_upgAnyTest extends TestCase {
    private GameUT $game;
    private int $fillerCounter;
    private const AI_COLOR = "ffffff";
    private const ROT = 100;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init(1);
        $this->game->debug_setupGameTables();
        $this->fillerCounter = 0;
    }

    private function createOp(string $color): Op_ai_upgAny {
        /** @var Op_ai_upgAny */
        $op = $this->game->machine->instanciateOperation("ai_upgAny", self::AI_COLOR);
        $op->withDataField("upgrade", $color);
        return $op;
    }

    /**
     * Fill a list of caravan positions with distinct 1×1 green tiles.
     * Uses a persistent counter so multiple calls don't reuse the same token ids.
     * Green tiles have 4 types (31-34) × 2 instances = 8 unique fillers available.
     */
    private function fillCells(array $positions): void {
        foreach ($positions as $pos) {
            $n = $this->fillerCounter++;
            $tileNum = 31 + ($n % 4);
            $instance = 1 + intdiv($n, 4);
            $tileId = "upg_green_{$tileNum}_{$instance}";
            $this->game->tokens->db->moveToken($tileId, "tableau_" . self::AI_COLOR, $pos);
        }
    }

    /**
     * Clear all upgrade tiles of a color from mainarea so getColorPriority
     * picks the color we want to test with.
     */
    private function clearColorFromMainarea(string $color): void {
        $tiles = $this->game->tokens->getTokensOfTypeInLocation("upg_$color", "mainarea");
        foreach ($tiles as $tileId => $info) {
            $this->game->tokens->db->moveToken($tileId, "limbo", 0);
        }
    }

    public function testNormalPlacementWhenSpaceAvailable(): void {
        $op = $this->createOp("yellow");
        $result = $op->auto();

        $this->assertTrue($result);
        // First yellow tile (p=1, num=1 or 2 depending on selectTile order) should
        // land at winding-path position 15 (bottom-left, native horizontal).
        $tiles = $this->game->tokens->getTokensOfTypeInLocation("upg_yellow", "tableau_" . self::AI_COLOR);
        $this->assertCount(1, $tiles, "Exactly one yellow tile should be placed");
        $info = reset($tiles);
        $this->assertEquals(15, (int) $info["state"], "Yellow 2×1 placed at pos 15 in native horizontal orientation");
    }

    public function testRotatesYellowAtBottomRightCorner(): void {
        // Fill positions 15-20 with 1×1 fillers; only 21 (bottom-right) is free.
        // A 2×1 horizontal at 21 would need column 8 (doesn't exist).
        // A 1×2 vertical at 21 would extend DOWN out of bounds.
        // Correct wrap: anchor at 14 (middle-row, col 6) so vertical extends DOWN into 21.
        // Expected: state = 14 + 100 (rotated from native horizontal to vertical) = 114
        $this->fillCells([15, 16, 17, 18, 19, 20]);

        $op = $this->createOp("yellow");
        $op->auto();

        $tiles = $this->game->tokens->getTokensOfTypeInLocation("upg_yellow", "tableau_" . self::AI_COLOR);
        $this->assertCount(1, $tiles);
        $info = reset($tiles);
        $this->assertEquals(
            14 + self::ROT,
            (int) $info["state"],
            "Yellow 2×1 must rotate to vertical anchored at 14, wrapping into 21 (state = 114)"
        );
    }

    public function testFallsAlongsideBoardWhenCaravanFull(): void {
        // Fill all 21 caravan positions with 1×1 fillers. Available 1×1 tokens:
        // 8 greens + 10 pinks = 18. Need 3 more — shrink 3 blue tiles to 1×1 via setRulesFor.
        $pinks = $this->game->tokens->getTokensOfTypeInLocation("upg_pink", "mainarea");
        $pinkIds = array_keys($pinks);

        $this->fillCells(range(1, 8)); // 8 greens at positions 1-8
        for ($i = 0; $i < 10; $i++) { // 10 pinks at positions 9-18
            $this->game->tokens->db->moveToken($pinkIds[$i], "tableau_" . self::AI_COLOR, 9 + $i);
        }
        // Shrink 3 blue tiles to 1×1 and place at positions 19-21
        for ($i = 0; $i < 3; $i++) {
            $blueId = "upg_blue_" . (7 + $i) . "_1";
            $this->game->material->setRulesFor($blueId, ["w" => "1", "h" => "1"]);
            $this->game->tokens->db->moveToken($blueId, "tableau_" . self::AI_COLOR, 19 + $i);
        }

        $op = $this->createOp("yellow");
        $op->auto();

        $yellows = $this->game->tokens->getTokensOfTypeInLocation("upg_yellow", "tableau_" . self::AI_COLOR);
        $this->assertCount(1, $yellows);
        $info = reset($yellows);
        $this->assertEquals(0, (int) $info["state"], "No room for either orientation — alongside-board (state 0)");
    }

    public function testRotatedTileBlocksOccupancyCorrectly(): void {
        // Pre-place a rotated yellow at state 114 (anchor 14, rotated → footprint = 14 + 21).
        // This is the realistic shape produced by the corner-wrap rule.
        $this->game->tokens->db->moveToken("upg_yellow_1_1", "tableau_" . self::AI_COLOR, 14 + self::ROT);
        // Fill bottom-row cells 15-20 (so the only bottom-row free cell, 21, is already
        // covered by the rotated tile's footprint above).
        $this->fillCells([15, 16, 17, 18, 19, 20]);
        // Fill middle row from 13 down to 8 (cell 14 is occupied by the rotated tile).
        $this->fillCells([13, 12, 11, 10, 9, 8]);

        // Now the only free cells are top row (1-7). A 2×1 horizontal must fit at pos 1.
        $op = $this->createOp("yellow");
        $op->auto();

        $tiles = $this->game->tokens->getTokensOfTypeInLocation("upg_yellow", "tableau_" . self::AI_COLOR);
        $placed = null;
        foreach ($tiles as $id => $info) {
            if ($id !== "upg_yellow_1_1") {
                $placed = $info;
                break;
            }
        }
        $this->assertNotNull($placed, "A second yellow tile should have been placed");
        $this->assertEquals(1, (int) $placed["state"], "Second yellow should land at top-row pos 1, native horizontal");
    }

    public function testBlackTileRotatesToHorizontalByDefault(): void {
        $op = $this->createOp("black");
        $op->auto();

        $tiles = $this->game->tokens->getTokensOfTypeInLocation("upg_black", "tableau_" . self::AI_COLOR);
        $this->assertCount(1, $tiles);
        $info = reset($tiles);
        // Native black is 1×2 vertical; in caravan rows it must rotate to 2×1 horizontal.
        // First winding slot is pos 15. Rotated → state = 115.
        $this->assertEquals(
            15 + self::ROT,
            (int) $info["state"],
            "Black 1×2 must rotate to horizontal in caravan rows (state = 115)"
        );
    }

    public function testBlackTileStaysVerticalAtRowEndCorner(): void {
        // Fill bottom row positions 15-20; only 21 is free.
        // Horizontal 2×1 at 21 → needs column 8 (out of bounds).
        // Vertical 1×2 at 21 → extends DOWN out of bounds.
        // So the next winding-path slot to try is 14: vertical 1×2 anchored at 14
        // extends DOWN into 21 — fits, and is the natural corner-wrap.
        // Black is natively vertical, so anchored-at-14 vertical = NOT rotated → state = 14
        $this->fillCells([15, 16, 17, 18, 19, 20]);

        $op = $this->createOp("black");
        $op->auto();

        $tiles = $this->game->tokens->getTokensOfTypeInLocation("upg_black", "tableau_" . self::AI_COLOR);
        $this->assertCount(1, $tiles);
        $info = reset($tiles);
        $this->assertEquals(
            14,
            (int) $info["state"],
            "Black at corner-wrap anchors at 14 native vertical (state = 14, NOT rotated)"
        );
    }
}
