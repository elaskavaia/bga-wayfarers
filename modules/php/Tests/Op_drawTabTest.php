<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Operations\Op_drawTab;
use Bga\Games\wayfarers\Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_drawTabTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->setPlayersNumber(1);
        $this->game->init();
    }

    private function createOp(?string $card = null): Op_drawTab {
        $data = $card !== null ? ["card" => $card] : null;
        /** @var Op_drawTab */
        $op = $this->game->machine->instanciateOperation("drawTab", PCOLOR, $data);
        return $op;
    }

    private function createOpAutoma(?string $card = null): Op_drawTab {
        $data = $card !== null ? ["card" => $card] : null;
        /** @var Op_drawTab */
        $op = $this->game->machine->instanciateOperation("drawTab", ACOLOR, $data);
        return $op;
    }
    public function testRequireConfirmationForHumanPlayer(): void {
        $op = $this->createOp("card_land_1");

        // Human players require confirmation
        $this->assertTrue($op->requireConfirmation());
    }

    public function testNoConfirmationForAutomaPlayer(): void {
        // Set up automa player
        $op = $this->createOpAutoma("card_land_1");

        // Automa requires no confirmation
        $this->assertFalse($op->requireConfirmation());
    }

    public function testGetCard(): void {
        $op = $this->createOp("card_land_5");

        $this->assertEquals("card_land_5", $op->getCard());
    }

    public function testGetCardDefaultValue(): void {
        $op = $this->createOp();

        $this->assertEquals("card_xxx", $op->getCard());
    }

    public function testGetPrompt(): void {
        $op = $this->createOp("card_land_1");

        $prompt = $op->getPrompt();

        $this->assertIsString($prompt);
        $this->assertStringContainsString("draw", strtolower($prompt));
    }
}
