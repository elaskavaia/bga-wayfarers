<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * implementation : © Alena Laskavaia <laskava@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 */

declare(strict_types=1);

namespace Bga\Games\wayfarers\Operations;

use Bga\Games\wayfarers\Material;
use Bga\Games\wayfarers\OpCommon\AiOperation;

use function Bga\Games\wayfarers\custom_array_rotate;

/**
 * AI Upgrade Tile Acquisition (Any - which does not include Pink/Special)
 *
 * Uses resource track color priority to select tile color
 * Uses sum value for positional selection within that color
 * Places tiles in sequential caravan positions
 * 
 * 
 RULES:
 If their
colour priority would result in them not benefiting (for example, if
there were no remaining Upgrade Tiles of that colour), they move to
the next colour in clockwise order.

 When acquiring Upgrade Tiles, the AI places them onto their Board,
starting in the bottom-left corner of their Caravan. From here they
continue placing Upgrade Tiles, filling the bottom row from left to
right, then the middle row from right to left, and finally the top row
from left to right. Rotate rectangular Tiles as needed, in order to
keep them from breaking out of this winding path. If the AI gains
more Upgrade Tiles than they have room for, simply place any
further Tiles alongside their Board.
When placing an Upgrade Tile, immediately resolve the effects of
any icons covered in their Caravan.
When acquiring a Special (Pink) Upgrade Tile, the AI prioritises
using the reference at the bottom of their most recently revealed
Scheme Card. If that Upgrade Tile has already been acquired, they
instead acquire the next available Tile in clockwise order
 */
class Op_ai_upgAny extends AiOperation {
    // AI caravan dimensions
    const CARAVAN_COLS = 7;
    const CARAVAN_ROWS = 3;

    public function getPossibleMoves() {
        $prio = $this->getColorPriority();
        foreach ($prio as $color) {
            $tiles = $this->game->tokens->getTokensOfTypeInLocation("upg_{$color}", "mainarea");
            if (!empty($tiles)) {
                // return array with all positions 1-4 (4 may not exists) which matches rules p (position) of upgrade, null if absent
                $res = [];
                for ($p = 1; $p <= 4; $p++) {
                    $res["p$p"] = ["q" => Material::ERR_NONE_LEFT, "color" => $color, "p" => $p, "tile" => null];
                    foreach ($tiles as $tileId => $tileInfo) {
                        if ($this->game->getRulesFor($tileId, "p", 0) == $p) {
                            $res["p$p"]["q"] = Material::RET_OK;
                            $res["p$p"]["tile"] = $tileId;
                        }
                    }
                }
                return $res;
            }
        }
        return [];
    }

    /**
     * Get tile color priority based on resource track marker
     * Returns array of colors in priority order
     */
    function getColorPriority(): array {
        if ($this->getUpgradeColor()) {
            return [$this->getUpgradeColor()];
        }

        return parent::getColorPriority();
    }

    function getUpgradeColor() {
        return $this->getDataField("upgrade", null);
    }

    /**
     * Get next available caravan position for AI
     * AI caravan is 7 x 3 without any reserved slots
     * Winding path: bottom row L→R (15-21), middle row R→L (14-8), top row L→R (1-7)
     */
    function getNextCaravanPosition(int $tileW, int $tileH): ?int {
        $owner = $this->getOwner();
        $tiles = $this->game->tokens->getTokensOfTypeInLocation("upg", "tableau_$owner");

        // Track which positions are occupied (including full footprint of multi-cell tiles)
        $occupied = [];
        foreach ($tiles as $tileId => $tileInfo) {
            if ($tileInfo["state"] > 0) {
                $anchorPos = $tileInfo["state"];
                $w = (int) $this->game->getRulesFor($tileId, "w", 1);
                $h = (int) $this->game->getRulesFor($tileId, "h", 1);
                $ax = ($anchorPos - 1) % self::CARAVAN_COLS;
                $ay = (int) floor(($anchorPos - 1) / self::CARAVAN_COLS);
                for ($dy = 0; $dy < $h; $dy++) {
                    for ($dx = 0; $dx < $w; $dx++) {
                        $occupied[] = $ax + $dx + ($ay + $dy) * self::CARAVAN_COLS + 1;
                    }
                }
            }
        }

        // Winding path order: bottom row L→R, middle row R→L, top row L→R
        $windingPath = [15, 16, 17, 18, 19, 20, 21, 14, 13, 12, 11, 10, 9, 8, 1, 2, 3, 4, 5, 6, 7];

        // Try positions in winding path order
        foreach ($windingPath as $pos) {
            if (in_array($pos, $occupied)) {
                continue;
            }

            // Check if tile fits (considering width and height)
            $x = ($pos - 1) % self::CARAVAN_COLS;
            $y = (int) floor(($pos - 1) / self::CARAVAN_COLS);

            // Check bounds
            if ($x + $tileW > self::CARAVAN_COLS || $y + $tileH > self::CARAVAN_ROWS) {
                continue;
            }

            // Check for collisions with existing tiles
            $canPlace = true;
            for ($dy = 0; $dy < $tileH; $dy++) {
                for ($dx = 0; $dx < $tileW; $dx++) {
                    $checkPos = $x + $dx + ($y + $dy) * self::CARAVAN_COLS + 1;
                    if (in_array($checkPos, $occupied)) {
                        $canPlace = false;
                        break 2;
                    }
                }
            }

            if ($canPlace) {
                return $pos;
            }
        }

        return null; // No space available
    }

    /**
     * Select tile based on color priority and position priority
     */
    function selectTile(array $availableTiles): ?string {
        if (empty($availableTiles)) {
            return null; // no tiles left
        }

        $positionPriority = $this->getPositionPriority();
        $dir = $this->getNextPositionPriorityDirection($positionPriority);
        $sorted = custom_array_rotate($availableTiles, $positionPriority - 1, $dir);
        while (count($sorted) > 0) {
            $info = array_shift($sorted);
            if ($info["q"] == Material::RET_OK) {
                return $info["tile"];
            }
        }

        return null; // no tiles left
    }

    /**
     * Auto-resolve: AI acquires upgrade tile
     */
    public function auto(): bool {
        $owner = $this->getOwner();
        if ($this->getUpgradeColor() == "pink") {
            $this->queue("ai_upgPink");
            return true;
        }
        $availableTiles = $this->getPossibleMoves();

        $selectedTile = $this->selectTile($availableTiles);

        if (!$selectedTile) {
            $this->notifyMessage(clienttranslate('${player_name} cannot acquire any upgrade tiles'), []);
            return true; // Complete even if no tile available
        }

        // Get tile dimensions
        $tileW = (int) $this->game->getRulesFor($selectedTile, "w", 1);
        $tileH = (int) $this->game->getRulesFor($selectedTile, "h", 1);

        // Find next available position, rotating rectangular tiles if needed to fit winding path
        $position = $this->getNextCaravanPosition($tileW, $tileH);
        if ($position === null && $tileW !== $tileH) {
            //$position = $this->getNextCaravanPosition($tileH, $tileW);
            //TODO: we won't support rotating tiles for now
        }

        if ($position === null) {
            // No space in caravan - place alongside the board (state 0)
            $this->dbSetTokenLocation(
                $selectedTile,
                "tableau_$owner",
                0,
                clienttranslate('${player_name} acquires ${token_name} and places it alongside their board')
            );
            return true;
        }

        // Place tile in caravan amd notify
        $this->dbSetTokenLocation(
            $selectedTile,
            "tableau_$owner",
            $position,
            clienttranslate('${player_name} acquires ${token_name} and places it in caravan')
        );

        // TODO activate bonus placement

        // AI ignores all tile bonuses and icons
        // VP from tiles is scored at end of game in finalScoring()

        return true;
    }
}
