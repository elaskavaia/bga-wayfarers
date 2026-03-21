<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Material;
use Bga\Games\wayfarers\Operations\Op_diceMod;
use Bga\Games\wayfarers\Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_diceModTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init();
        $this->game->tokens->createTokens();
    }

    private function createOp(?array $data = null): Op_diceMod {
        /** @var Op_diceMod */
        $op = $this->game->machine->instanciateOperation("diceMod", PCOLOR, $data);
        return $op;
    }

    private function setAllDice(int $value): void {
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_" . PCOLOR);
        foreach (array_keys($dice) as $dieKey) {
            $this->game->tokens->db->setTokenState($dieKey, $value);
        }
    }

    public function testGetPossibleMoves_AllDiceAtValue3(): void {
        $this->setAllDice(3);
        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        // Dice are grouped by value, so only 1 unique value (3) → 1 up + 1 down
        $this->assertNotEmpty($moves);
        $upCount = 0;
        $downCount = 0;
        foreach ($moves as $key => $info) {
            $this->assertEquals(Material::RET_OK, $info["q"]);
            $this->assertArrayHasKey("token_id", $info);
            $this->assertEquals(3, $info["from"]);
            if (str_ends_with($key, "_up")) {
                $this->assertEquals(4, $info["to"]);
                $upCount++;
            } else {
                $this->assertEquals(2, $info["to"]);
                $downCount++;
            }
        }
        $this->assertEquals(1, $upCount);
        $this->assertEquals(1, $downCount);
    }

    public function testGetPossibleMoves_DieAtValue1_OnlyUp(): void {
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_" . PCOLOR);
        $dieKey = array_key_first($dice);
        $this->game->tokens->db->setTokenState($dieKey, 1);

        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        // Die at 1 can only go up
        $this->assertArrayHasKey("{$dieKey}_up", $moves);
        $this->assertArrayNotHasKey("{$dieKey}_down", $moves);
        $this->assertEquals(2, $moves["{$dieKey}_up"]["to"]);
    }

    public function testGetPossibleMoves_DieAtValue6_OnlyDown(): void {
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_" . PCOLOR);
        $dieKey = array_key_first($dice);
        $this->game->tokens->db->setTokenState($dieKey, 6);

        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        $this->assertArrayHasKey("{$dieKey}_down", $moves);
        $this->assertArrayNotHasKey("{$dieKey}_up", $moves);
        $this->assertEquals(5, $moves["{$dieKey}_down"]["to"]);
    }

    public function testResolve_Up(): void {
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_" . PCOLOR);
        $dieKey = array_key_first($dice);
        $this->game->tokens->db->setTokenState($dieKey, 3);

        $op = $this->createOp();
        $op->action_resolve(["target" => "{$dieKey}_up"]);

        $newState = (int) $this->game->tokens->db->getTokenState($dieKey);
        $this->assertEquals(4, $newState);
    }

    public function testResolve_Down(): void {
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_" . PCOLOR);
        $dieKey = array_key_first($dice);
        $this->game->tokens->db->setTokenState($dieKey, 3);

        $op = $this->createOp();
        $op->action_resolve(["target" => "{$dieKey}_down"]);

        $newState = (int) $this->game->tokens->db->getTokenState($dieKey);
        $this->assertEquals(2, $newState);
    }

    public function testCanSkip(): void {
        $op = $this->createOp();
        $this->assertTrue($op->canSkip());
    }

    public function test2diceMod_countable(): void {
        $op = $this->game->machine->instanciateOperation("2diceMod", PCOLOR);
        $this->assertInstanceOf(Op_diceMod::class, $op);
    }

    public function test2diceMod_ShouldResolveTwice(): void {
        $this->setAllDice(3);

        $this->game->machine->queue("2diceMod", PCOLOR);

        // Dispatch: seq should expand and put diceMod on stack
        $top = $this->dispatchOneStep();
        $this->assertNotNull($top, "Should have an operation on stack after dispatch");
        $this->assertEquals("diceMod", $top->getType(), "First op should be diceMod");

        // Resolve the first diceMod (move die_1 up: 3→4)
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_" . PCOLOR);
        $dieKey = array_key_first($dice);
        $this->game->fakeUserAction($top, "{$dieKey}_up");

        $newState = (int) $this->game->tokens->db->getTokenState($dieKey);
        $this->assertEquals(4, $newState, "First diceMod should change die from 3 to 4");

        // Dispatch again: second diceMod should appear
        $top = $this->dispatchOneStep();
        // BUG: Op_diceMod doesn't extend CountableOperation, so count=2 set by seq is ignored.
        // The second diceMod never appears — the operation resolves only once.
        $this->assertNotNull($top, "Second diceMod should be on stack (count=2 means do it twice)");
        $this->assertEquals("diceMod", $top->getType(), "Second op should also be diceMod");
    }

    private function dispatchOneStep() {
        $this->game->machine->dispatchOne();
        return $this->game->machine->createTopOperationFromDbForOwner(null);
    }
}
