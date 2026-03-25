<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Material;
use Bga\Games\wayfarers\Operations\Op_reroll;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_rerollTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init();
        $this->game->tokens->createTokens();
    }

    private function createOp(?array $data = null): Op_reroll {
        /** @var Op_reroll */
        $op = $this->game->machine->instanciateOperation("reroll", PCOLOR, $data);
        return $op;
    }

    public function testGetPossibleMoves_NoDieSet(): void {
        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        // Should return array of die keys (all dice in player's tableau)
        $this->assertIsArray($moves);
        $this->assertNotEmpty($moves);
        foreach ($moves as $key) {
            $this->assertStringStartsWith("dice_", $key);
        }
    }

    public function testGetPossibleMoves_WithDieSet(): void {
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_" . PCOLOR);
        $dieKey = array_key_first($dice);

        $op = $this->createOp(["die" => $dieKey]);
        $moves = $op->getPossibleMoves();

        // Should return button-style array with die key
        $this->assertArrayHasKey($dieKey, $moves);
        $this->assertEquals(Material::RET_OK, $moves[$dieKey]["q"]);
    }

    public function testGetPossibleMoves_WithDieConfirmed(): void {
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_" . PCOLOR);
        $dieKey = array_key_first($dice);

        $op = $this->createOp(["die" => $dieKey, "confirmed" => true]);
        $moves = $op->getPossibleMoves();

        // Should return simple array with just the die key (auto-select)
        $this->assertEquals([$dieKey], $moves);
    }

    public function testCanSkip_Default(): void {
        $op = $this->createOp();
        $this->assertTrue($op->canSkip());
    }

    public function testCanSkip_WithDie(): void {
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_" . PCOLOR);
        $dieKey = array_key_first($dice);

        $op = $this->createOp(["die" => $dieKey]);
        $this->assertTrue($op->canSkip());
    }

    public function testCanSkip_Confirmed(): void {
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_" . PCOLOR);
        $dieKey = array_key_first($dice);

        $op = $this->createOp(["die" => $dieKey, "confirmed" => true]);
        $this->assertFalse($op->canSkip());
    }

    public function testResolve_MovesDieToTableau(): void {
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_" . PCOLOR);
        $dieKey = array_key_first($dice);

        // Move die to a card (simulating placed die)
        $this->game->tokens->db->moveToken($dieKey, "card_land_1", 3);

        $op = $this->createOp(["die" => $dieKey]);
        $op->resolve();

        // Die should be back in tableau
        $info = $this->game->tokens->db->getTokenInfo($dieKey);
        $this->assertEquals("tableau_" . PCOLOR, $info["location"]);
    }

    public function testGetAllDice_IncludesPlacedDice(): void {
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_" . PCOLOR);
        $dieKeys = array_keys($dice);

        // Place one die on a card in tableau
        $this->game->tokens->db->moveToken($dieKeys[0], "card_land_1");
        $this->game->tokens->db->moveToken("card_land_1", "tableau_" . PCOLOR, 2);

        $op = $this->createOp();
        $allDice = $op->getAllDice();

        // Should include both supply dice and placed dice
        $this->assertArrayHasKey($dieKeys[0], $allDice, "Placed die should be included");
        $this->assertArrayHasKey($dieKeys[1], $allDice, "Supply die should be included");
    }

    public function testGetPrompt_NoDie(): void {
        $op = $this->createOp();
        $prompt = $op->getPrompt();
        $this->assertStringContainsString("Select", $prompt);
    }

    public function testGetPrompt_WithDie(): void {
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_" . PCOLOR);
        $dieKey = array_key_first($dice);

        $op = $this->createOp(["die" => $dieKey]);
        $prompt = $op->getPrompt();
        $this->assertStringContainsString("Confirm", $prompt);
    }
}
