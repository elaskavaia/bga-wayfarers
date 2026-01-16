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
use Bga\Games\wayfarers\OpCommon\Operation;

/**
 * Base class for upgrade tile operations
 * Handles caravan grid placement (6x3 grid)
 */
abstract class Op_upgBase extends Operation {
    const CARAVAN_WIDTH = 6;
    const CARAVAN_HEIGHT = 3;

    /**
     * Get the tile type (e.g., "blue", "black", "yellow", "green", "pink")
     * Must be implemented by subclasses
     */
    abstract function getTileType(): string;

    /**
     * Get the tile width for this upgrade type
     * Must be implemented by subclasses
     */
    abstract function getTileWidth(): int;

    /**
     * Get the tile height for this upgrade type
     * Must be implemented by subclasses
     */
    abstract function getTileHeight(): int;

    /**
     * Get the cost for this upgrade type
     * Default is 3, can be overridden by subclasses
     */
    function getCost(): int {
        return 3;
    }

    /**
     * Get the selected tile from step 1
     */
    function getSelectedTile(): ?string {
        return $this->getDataField("tile", null);
    }

    /**
     * Get all tiles currently placed in the caravan
     * Returns array with grid positions marked as occupied
     */
    function getCaravanOccupancy(): array {
        $owner = $this->getOwner();
        $tiles = $this->game->tokens->getTokensOfTypeInLocation("upg", "tableau_$owner");

        // Initialize empty grid
        $grid = [];
        for ($y = 0; $y < self::CARAVAN_HEIGHT; $y++) {
            for ($x = 0; $x < self::CARAVAN_WIDTH; $x++) {
                $grid[$y][$x] = false;
            }
        }

        // Mark occupied positions
        foreach ($tiles as $tileKey => $tileInfo) {
            $state = $tileInfo["state"];
            if ($state > 0) {
                // State encodes position: state = x + y * CARAVAN_WIDTH + 1
                $pos = $state - 1;
                $x = $pos % self::CARAVAN_WIDTH;
                $y = (int)floor($pos / self::CARAVAN_WIDTH);

                // Get tile dimensions from material
                $dimensions = $this->getTileDimensions($tileKey);

                // Mark all cells occupied by this tile
                for ($dy = 0; $dy < $dimensions['h']; $dy++) {
                    for ($dx = 0; $dx < $dimensions['w']; $dx++) {
                        if ($y + $dy < self::CARAVAN_HEIGHT && $x + $dx < self::CARAVAN_WIDTH) {
                            $grid[$y + $dy][$x + $dx] = true;
                        }
                    }
                }
            }
        }

        return $grid;
    }

    /**
     * Get tile dimensions from its key
     */
    function getTileDimensions(string $tileKey): array {
        // Get dimensions from material rules
        $w = $this->game->getRulesFor($tileKey, "w", 1);
        $h = $this->game->getRulesFor($tileKey, "h", 1);

        return ['w' => $w, 'h' => $h];
    }

    /**
     * Get valid placement positions for a tile of given dimensions
     */
    function getValidPositions(int $tileW, int $tileH): array {
        $grid = $this->getCaravanOccupancy();
        $validPositions = [];

        for ($y = 0; $y <= self::CARAVAN_HEIGHT - $tileH; $y++) {
            for ($x = 0; $x <= self::CARAVAN_WIDTH - $tileW; $x++) {
                // Check if all cells for this tile are free
                $canPlace = true;
                for ($dy = 0; $dy < $tileH; $dy++) {
                    for ($dx = 0; $dx < $tileW; $dx++) {
                        if ($grid[$y + $dy][$x + $dx]) {
                            $canPlace = false;
                            break 2;
                        }
                    }
                }

                if ($canPlace) {
                    // Encode position as single integer: x + y * CARAVAN_WIDTH + 1
                    $pos = $x + $y * self::CARAVAN_WIDTH + 1;
                    $validPositions["pos_$pos"] = [
                        "q" => Material::RET_OK,
                        "x" => $x,
                        "y" => $y
                    ];
                }
            }
        }

        return $validPositions;
    }

    function getPossibleMoves() {
        $selectedTile = $this->getSelectedTile();

        if ($selectedTile === null) {
            // Step 1: Select which tile to buy from mainarea
            $tileType = $this->getTileType();
            $tokens = $this->game->tokens->getTokensOfTypeInLocation("upg_$tileType", "mainarea");
            $res = [];
            $cost = $this->getCost();
            foreach (array_keys($tokens) as $card) {
                $res[$card] = ["q" => 0, "cost" => $cost];
            }
            return $res;
        } else {
            // Step 2: Select position on caravan grid
            return $this->getValidPositions($this->getTileWidth(), $this->getTileHeight());
        }
    }

    /** User does the action */
    function resolve(): void {
        $owner = $this->getOwner();
        $selectedTile = $this->getSelectedTile();

        if ($selectedTile === null) {
            // Step 1: Tile selected, pay cost and move to step 2
            $tile = $this->getCheckedArg();
            $cost = $this->getCost();
            $this->game->effect_incCount($owner, "coin", -$cost, $this->getOpId());

            // Queue step 2 with the selected tile
            $this->queue($this->getType(), $owner, ["tile" => $tile]);
            return;
        }

        // Step 2: Position selected, place tile in caravan
        $position = $this->getCheckedArg();

        // Extract position from "pos_X" format
        $posValue = (int)substr($position, 4);

        // Place tile in tableau with position encoded in state
        $this->game->tokens->dbSetTokenLocation(
            $selectedTile,
            "tableau_$owner",
            $posValue,
            clienttranslate('${player_name} places ${token_name} in caravan')
        );
    }

    public function getPrompt() {
        $selectedTile = $this->getSelectedTile();
        if ($selectedTile === null) {
            return clienttranslate("Select an upgrade tile to buy");
        }
        return clienttranslate("Select where to place the tile in your caravan");
    }
}
