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
 */
class Op_ai_upgAny extends AiOperation {
    // AI caravan dimensions
    const CARAVAN_COLS = 7;
    const CARAVAN_ROWS = 3;
    // State offset to mark tile as rotated 90° from native orientation
    // (state 1-21 = native orientation; state 101-121 = rotated)
    const ROTATED_STATE_OFFSET = 100;
    // Winding caravan path: bottom row L→R (15-21), middle row R→L (14-8), top row L→R (1-7)
    private const WINDING_PATH = [15, 16, 17, 18, 19, 20, 21, 14, 13, 12, 11, 10, 9, 8, 1, 2, 3, 4, 5, 6, 7];

    public function getPossibleMoves() {
        $prio = $this->getColorPriority();
        $pos = $this->getMaxPosition();
        foreach ($prio as $color) {
            $tiles = $this->game->tokens->getTokensOfTypeInLocation("upg_{$color}", "mainarea");
            if (!empty($tiles)) {
                $res = [];
                for ($p = 1; $p <= $pos; $p++) {
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

    function getMaxPosition(): int {
        return 4;
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

        // Get tile dimensions and pick caravan position + orientation
        $nativeW = (int) $this->game->getRulesFor($selectedTile, "w", 1);
        $nativeH = (int) $this->game->getRulesFor($selectedTile, "h", 1);
        [$position, $rotated] = $this->pickPlacement($nativeW, $nativeH);

        if ($position === null) {
            // No space in caravan - place alongside the board (state 0)
            $this->game->effect_gainTile(
                $owner,
                $selectedTile,
                0,
                clienttranslate('${player_name} acquires ${token_name} and places it alongside their board')
            );
            return true;
        }

        $stateValue = $rotated ? $position + self::ROTATED_STATE_OFFSET : $position;

        $this->game->effect_gainTile(
            $owner,
            $selectedTile,
            $stateValue,
            clienttranslate('${player_name} acquires ${token_name} and places it in caravan')
        );

        // Cover bonuses use the effective (post-rotation) footprint, not the native one
        [$effW, $effH] = $rotated ? [$nativeH, $nativeW] : [$nativeW, $nativeH];
        $boardNumber = $this->aiGetBoardNumber();
        [$x, $y] = $this->xyFromPos($position);
        for ($dy = 0; $dy < $effH; $dy++) {
            for ($dx = 0; $dx < $effW; $dx++) {
                $i = $x + $dx + ($y + $dy) * self::CARAVAN_COLS;
                $bonus = $this->game->getRulesFor("aibonus_{$boardNumber}_{$i}", "r", "");
                if ($bonus) {
                    $this->queue($bonus);
                }
            }
        }

        return true;
    }

    /**
     * Pick caravan position and orientation for a tile. Rectangular tiles
     * must orient horizontally to follow the winding row direction; vertical
     * only at row-end corners where horizontal cannot fit at the current
     * candidate. Returns [pos, rotated] where rotated=true means the tile is
     * drawn 90° from its native orientation. pos is null when nothing fits.
     */
    private function pickPlacement(int $nativeW, int $nativeH): array {
        $occupied = $this->computeOccupied();
        // Primary = horizontal (wide-over-tall); secondary = vertical.
        // For squares, primary is the only orientation and rotated stays false.
        $primaryW = max($nativeW, $nativeH);
        $primaryH = min($nativeW, $nativeH);
        $rotatedForPrimary = $nativeW < $nativeH;

        foreach (self::WINDING_PATH as $pos) {
            if (in_array($pos, $occupied)) {
                continue;
            }
            if ($this->fitsAt($pos, $primaryW, $primaryH, $occupied)) {
                return [$pos, $rotatedForPrimary];
            }
            if ($primaryW !== $primaryH && $this->fitsAt($pos, $primaryH, $primaryW, $occupied)) {
                return [$pos, !$rotatedForPrimary];
            }
        }
        return [null, false];
    }

    private function computeOccupied(): array {
        $owner = $this->getOwner();
        $tiles = $this->game->tokens->getTokensOfTypeInLocation("upg", "tableau_$owner");
        $occupied = [];
        foreach ($tiles as $tileId => $tileInfo) {
            // state 0 = alongside-board overflow; contributes no caravan footprint
            if ($tileInfo["state"] <= 0) {
                continue;
            }
            $rawState = (int) $tileInfo["state"];
            $rotated = $rawState > self::ROTATED_STATE_OFFSET;
            $anchorPos = $rotated ? $rawState - self::ROTATED_STATE_OFFSET : $rawState;
            $w = (int) $this->game->getRulesFor($tileId, "w", 1);
            $h = (int) $this->game->getRulesFor($tileId, "h", 1);
            if ($rotated) {
                [$w, $h] = [$h, $w];
            }
            [$ax, $ay] = $this->xyFromPos($anchorPos);
            for ($dy = 0; $dy < $h; $dy++) {
                for ($dx = 0; $dx < $w; $dx++) {
                    $occupied[] = $ax + $dx + ($ay + $dy) * self::CARAVAN_COLS + 1;
                }
            }
        }
        return $occupied;
    }

    private function fitsAt(int $pos, int $w, int $h, array $occupied): bool {
        [$x, $y] = $this->xyFromPos($pos);
        if ($x + $w > self::CARAVAN_COLS || $y + $h > self::CARAVAN_ROWS) {
            return false;
        }
        for ($dy = 0; $dy < $h; $dy++) {
            for ($dx = 0; $dx < $w; $dx++) {
                $checkPos = $x + $dx + ($y + $dy) * self::CARAVAN_COLS + 1;
                if (in_array($checkPos, $occupied)) {
                    return false;
                }
            }
        }
        return true;
    }

    private function xyFromPos(int $pos): array {
        return [($pos - 1) % self::CARAVAN_COLS, intdiv($pos - 1, self::CARAVAN_COLS)];
    }
}
