<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class OperationTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init();
        $this->game->tokens->createTokens();
    }

    private function resolveName($name): string {
        if (is_array($name)) {
            return GameUT::format_string_recursive($name["log"], $name["args"]);
        }
        return (string) $name;
    }

    public function testOr_GetOpName_NoIconMarkup(): void {
        $op = $this->game->machine->instanciateOperation("coin/food", PCOLOR);
        $flat = $this->resolveName($op->getOpName());
        $this->assertStringNotContainsString("wicon", $flat, "OpName should not contain icon markup: $flat");
    }

    public function testOr_GetIconicName_HasIconMarkup(): void {
        $op = $this->game->machine->instanciateOperation("coin/food", PCOLOR);
        $iconicName = $op->getIconicName();
        $this->assertEquals("[wicon_coin] / [wicon_food]", $iconicName);
    }

    public function testSeq_GetOpName_NoIconMarkup(): void {
        $op = $this->game->machine->instanciateOperation("coin,food", PCOLOR);
        $flat = $this->resolveName($op->getOpName());
        $this->assertStringNotContainsString("wicon", $flat, "OpName should not contain icon markup: $flat");
    }

    public function testSeq_GetIconicName_HasIconMarkup(): void {
        $op = $this->game->machine->instanciateOperation("coin,food", PCOLOR);
        $iconicName = $op->getIconicName();
        $this->assertEquals("[wicon_coin] [wicon_food]", $iconicName);
    }

    public function testSimple_GetOpName_NoIconMarkup(): void {
        $op = $this->game->machine->instanciateOperation("coin", PCOLOR);
        $flat = $this->resolveName($op->getOpName());
        $this->assertStringNotContainsString("wicon", $flat, "Simple OpName should not contain icon markup: $flat");
    }

    public function testCounted_GetOpName(): void {
        $op = $this->game->machine->instanciateOperation("2coin", PCOLOR);
        $flat = $this->resolveName($op->getOpName());
        $this->assertEquals("Gain Silver x 2", $flat);
    }

    public function testSimple_GetIconicName_HasIconMarkup(): void {
        $op = $this->game->machine->instanciateOperation("coin", PCOLOR);
        $iconicName = $op->getIconicName();
        $this->assertIsString($iconicName);
        $this->assertStringContainsString("wicon", $iconicName, "Simple iconic name should contain icon markup");
    }

    public function testParamInfo_TooltipVsName(): void {
        $op = $this->game->machine->instanciateOperation("coin/food", PCOLOR);
        $moves = $op->getPossibleMoves();
        foreach ($moves as $id => $info) {
            $tooltip = $this->resolveName($info["tooltip"] ?? "");
            $this->assertStringNotContainsString("wicon", $tooltip, "Tooltip for $id should not contain icon markup: $tooltip");
            $name = $info["name"] ?? "";
            if (is_string($name) && $name !== "") {
                $this->assertStringContainsString("wicon", $name, "Button name for $id should contain icon markup");
            }
        }
    }
}
