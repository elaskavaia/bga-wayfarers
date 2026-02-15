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

use Bga\GameFramework\NotificationMessage;
use Bga\GameFramework\UserException;
use Bga\Games\wayfarers\Material;
use BgaUserException;
use Dom\Node;

use function Bga\Games\wayfarers\getPart;

/**
 * Base class for upgrade tile operations
 * Handles caravan grid placement (6x3 grid)
 */
abstract class Op_upgBase extends Op_acquireBase {
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

    public function getUiArgs() {
        $selectedTile = $this->getSelectedTile();
        if ($selectedTile === null) {
            return ["replicate" => true];
        }
        return ["buttons" => false];
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
        // reserverd
        $grid[0][0] = true; // camel
        $grid[0][5] = true; // telescope
        // Mark occupied positions
        foreach ($tiles as $tileKey => $tileInfo) {
            $state = $tileInfo["state"];
            if ($state <= 0) {
                continue;
            }
            // State encodes position: state = x + y * CARAVAN_WIDTH + 1
            $pos = $state - 1;
            $x = $pos % self::CARAVAN_WIDTH;
            $y = (int) floor($pos / self::CARAVAN_WIDTH);

            // Get tile dimensions from material
            $dimensions = $this->getTileDimensions($tileKey);

            // Mark all cells occupied by this tile
            for ($dy = 0; $dy < $dimensions["h"]; $dy++) {
                for ($dx = 0; $dx < $dimensions["w"]; $dx++) {
                    if ($y + $dy < self::CARAVAN_HEIGHT && $x + $dx < self::CARAVAN_WIDTH) {
                        $grid[$y + $dy][$x + $dx] = true;
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

        return ["w" => $w, "h" => $h];
    }

    /**
     * Get valid placement positions for a tile of given dimensions
     */
    function getValidPositions(int $tileW, int $tileH): array {
        $grid = $this->getCaravanOccupancy();
        $validPositions = [];
        $owner = $this->getOwner();

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
                    $validPositions["ccell_{$pos}_{$owner}"] = [
                        "q" => Material::RET_OK,
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

            $seenTypes = [];
            foreach (array_keys($tokens) as $card) {
                // Tile IDs are like upg_blue_1_1, upg_blue_1_2 - first 3 segments identify unique type
                $parts = explode("_", $card);
                $uniqueType = $parts[0] . "_" . $parts[1] . "_" . $parts[2];

                if (!isset($seenTypes[$uniqueType])) {
                    $seenTypes[$uniqueType] = true;
                    $res[$card] = ["q" => 0];
                }
            }
            return $res;
        } else {
            // Step 2: Select position on caravan grid
            return $this->getValidPositions($this->getTileWidth(), $this->getTileHeight());
        }
    }

    public function getExtraArgs() {
        $payop = $this->getPaymentOperation($this->getSelectedTile());

        if ($payop) {
            $op = $this->game->machine->instanciateOperation($payop, $this->getOwner());
            return ["payop" => $payop, "payop_name" => $op->getOpName()] + parent::getExtraArgs();
        }
        return ["payop" => "?", "payop_name" => "?"] + parent::getExtraArgs();
    }

    /**
     * Check if this tile type is double-sided (yellow and blue are double-sided)
     */
    function isDoubleSided(): bool {
        return false;
    }

    /**
     * Get the reverse side tile key for a double-sided tile
     * Odd numbers pair with next even number (1<->2, 3<->4, etc.)
     */
    function getReverseSideTileKey(string $tileKey): string {
        // Tile key format: upg_color_num_copy (e.g., upg_blue_1_1)
        $parts = explode("_", $tileKey);
        $num = (int) $parts[2];

        // Odd pairs with next even, even pairs with previous odd
        if ($num % 2 === 1) {
            $reverseNum = $num + 1;
        } else {
            $reverseNum = $num - 1;
        }

        $parts[2] = (string) $reverseNum;
        return implode("_", $parts);
    }

    function getPaymentOperation(?string $card = null): string {
        $c = max(0, 3 - $this->getCoinDiscount());
        if ($c <= 0) {
            return "nop";
        }
        return "{$c}n_coin";
    }

    /** User does the action */
    function resolve(): void {
        $owner = $this->getOwner();
        $selectedTile = $this->getSelectedTile();

        if ($selectedTile === null) {
            // Step 1: Tile selected, pay cost and move to step 2
            $tile = $this->getCheckedArg();
            if (!$this->isFree()) {
                $op = $this->getPaymentOperation($tile);
                if ($op) {
                    $this->queue($op, $owner, [], $tile);
                }
            }

            // Queue step 2 with the selected tile
            $this->queue($this->getType(), $owner, ["tile" => $tile], $tile);
            return;
        }

        // Step 2: Position selected, place tile in caravan
        $position = $this->getCheckedArg();

        // Extract position from "ccell_X_owner" format
        $posValue = (int) getPart($position, 1);

        // Place tile in tableau with position encoded in state
        $this->dbSetTokenLocation(
            $selectedTile,
            "tableau_$owner",
            $posValue,
            clienttranslate('${player_name} places ${token_name} in caravan')
        );

        // For double-sided tiles (yellow/blue), remove the reverse side from mainarea
        if ($this->isDoubleSided()) {
            $reverseTile = $this->getReverseSideTileKey($selectedTile);
            $this->game->tokens->db->moveToken($reverseTile, "limbo", 0);
        }

        // Check if any Vista cards are triggered by this upgrade tile
        $this->queueVistaTriggers($selectedTile);

        // Collect bonuses from all cells covered by this tile
        $dimensions = $this->getTileDimensions($selectedTile);
        $pos = $posValue - 1;
        $x = $pos % self::CARAVAN_WIDTH;
        $y = (int) floor($pos / self::CARAVAN_WIDTH);

        for ($dy = 0; $dy < $dimensions["h"]; $dy++) {
            for ($dx = 0; $dx < $dimensions["w"]; $dx++) {
                $cellPos = ($x + $dx) + ($y + $dy) * self::CARAVAN_WIDTH + 1;
                $bonus = $this->getBonus($cellPos);
                if ($bonus) {
                    $this->queue($bonus, $owner, [], "caravanBonus");
                }
            }
        }
    }

    public function getBonus(int $posValue) {
        $i = $posValue - 1;
        $owner = $this->getOwner();
        $boardNum = $this->game->tokens->db->getTokenState("pboard_$owner", 1);
        $bonus = $this->game->material->getRulesFor("pbonus_{$boardNum}_{$i}", "r", "");
        return $bonus ?: "";
    }

    public function getPrompt() {
        $selectedTile = $this->getSelectedTile();
        $payop_name = $this->getArgs()["payop_name"];
        if ($selectedTile === null) {
            if ($this->isFree()) {
                return clienttranslate("Select an Upgrade Tile free of charge");
            }
            return new NotificationMessage(clienttranslate('Select an Upgrade Tile to buy, will cost ${cost}'), [
                "cost" => $payop_name,
            ]);
        }
        return clienttranslate("Select where to place the tile in your caravan");
    }
}
