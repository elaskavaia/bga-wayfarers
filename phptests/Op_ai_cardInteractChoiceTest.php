<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Material;
use Bga\Games\wayfarers\OpCommon\Operation;
use Bga\Games\wayfarers\Operations\Op_ai_cardInteractChoice;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_ai_cardInteractChoiceTest extends TestCase {
    private GameUT $game;
    private const AI_COLOR = "ffffff";

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init();
        $this->game->tokens->createTokens();
        $this->game->_setCurrentPlayerId(PCOLOR_ID);
    }

    private function createOp(array $data = []): Op_ai_cardInteractChoice {
        /** @var Op_ai_cardInteractChoice */
        $op = $this->game->machine->instanciateOperation("ai_cardInteractChoice", PCOLOR, $data);
        return $op;
    }

    private function setPlayerResources(int $coin, int $food): void {
        $this->game->tokens->db->setTokenState("tracker_coin_" . PCOLOR, $coin);
        $this->game->tokens->db->setTokenState("tracker_food_" . PCOLOR, $food);
    }

    private function addCardToMainarea(string $cardType, int $position): string {
        $cardId = "card_{$cardType}_1";
        $this->game->tokens->db->moveToken($cardId, "mainarea", $position);
        return $cardId;
    }

    private function addInfluenceOnCard(string $card, string $color = PCOLOR): string {
        $infId = "influence_{$color}_1";
        $this->game->tokens->db->moveToken($infId, $card);
        return $infId;
    }

    public function testGetPossibleMoves_AllAvailable(): void {
        $this->setPlayerResources(2, 2);

        $op = $this->createOp(["card" => "card_land_1", "caller" => "ai_cardLand", "caller_data" => []]);
        $moves = $op->getPossibleMoves();

        $this->assertEquals(Material::RET_OK, $moves["allow_coin"]["q"]);
        $this->assertEquals(Material::RET_OK, $moves["allow_food"]["q"]);
        $this->assertEquals(Material::RET_OK, $moves["deny_coin"]["q"]);
        $this->assertEquals(Material::RET_OK, $moves["deny_food"]["q"]);
    }

    public function testGetPossibleMoves_DenyDisabledWhenBroke(): void {
        $this->setPlayerResources(0, 0);

        $op = $this->createOp(["card" => "card_land_1", "caller" => "ai_cardLand", "caller_data" => []]);
        $moves = $op->getPossibleMoves();

        // Allow is always available (gains from supply)
        $this->assertEquals(Material::RET_OK, $moves["allow_coin"]["q"]);
        $this->assertEquals(Material::RET_OK, $moves["allow_food"]["q"]);
        // Deny requires payment
        $this->assertEquals(Material::ERR_COST, $moves["deny_coin"]["q"]);
        $this->assertEquals(Material::ERR_COST, $moves["deny_food"]["q"]);
    }

    public function testGetPossibleMoves_DenyPartiallyDisabled(): void {
        $this->setPlayerResources(1, 0);

        $op = $this->createOp(["card" => "card_land_1", "caller" => "ai_cardLand", "caller_data" => []]);
        $moves = $op->getPossibleMoves();

        $this->assertEquals(Material::RET_OK, $moves["deny_coin"]["q"]);
        $this->assertEquals(Material::ERR_COST, $moves["deny_food"]["q"]);
    }

    public function testAllowCoin(): void {
        $this->setPlayerResources(2, 2);

        $op = $this->createOp([
            "card" => "card_land_1",
            "caller" => "ai_cardLand",
            "caller_data" => ["buy" => true],
        ]);
        $this->game->fakeUserAction($op, "allow_coin");

        // Player should have gained 1 coin
        $coin = $this->game->tokens->getTrackerValue(PCOLOR, "coin");
        $this->assertEquals(3, $coin);
    }

    public function testAllowFood(): void {
        $this->setPlayerResources(2, 2);

        $op = $this->createOp([
            "card" => "card_land_1",
            "caller" => "ai_cardLand",
            "caller_data" => ["buy" => true],
        ]);
        $this->game->fakeUserAction($op, "allow_food");

        // Player should have gained 1 food
        $food = $this->game->tokens->getTrackerValue(PCOLOR, "food");
        $this->assertEquals(3, $food);
    }

    public function testDenyCoin(): void {
        $this->setPlayerResources(2, 2);

        $op = $this->createOp([
            "card" => "card_land_1",
            "caller" => "ai_cardLand",
            "caller_data" => ["denied" => []],
        ]);
        $this->game->fakeUserAction($op, "deny_coin");

        // Player should have paid 1 coin
        $coin = $this->game->tokens->getTrackerValue(PCOLOR, "coin");
        $this->assertEquals(1, $coin);
    }

    public function testDenyFood(): void {
        $this->setPlayerResources(2, 2);

        $op = $this->createOp([
            "card" => "card_land_1",
            "caller" => "ai_cardLand",
            "caller_data" => ["denied" => []],
        ]);
        $this->game->fakeUserAction($op, "deny_food");

        // Player should have paid 1 food
        $food = $this->game->tokens->getTrackerValue(PCOLOR, "food");
        $this->assertEquals(1, $food);
    }

    public function testDenyAddsCardToDeniedList(): void {
        $this->setPlayerResources(2, 2);

        $op = $this->createOp([
            "card" => "card_land_1",
            "caller" => "ai_cardLand",
            "caller_data" => ["denied" => []],
        ]);
        $op->saveToDb();
        $this->game->fakeUserAction($op, "deny_coin");

        // Check that a new ai_cardLand was queued with card_land_1 in the denied list
        $topOp = $this->game->machine->createTopOperationFromDbForOwner(null);
        $this->assertNotNull($topOp);
        $this->assertEquals("ai_cardLand", $topOp->getType());
        $denied = $topOp->getDataField("denied", []);
        $this->assertContains("card_land_1", $denied);
    }

    public function testAllowQueuesCallerWithConfirmedCard(): void {
        $this->setPlayerResources(2, 2);

        $op = $this->createOp([
            "card" => "card_land_1",
            "caller" => "ai_cardLand",
            "caller_data" => ["buy" => true],
        ]);
        $op->saveToDb();
        $this->game->fakeUserAction($op, "allow_coin");

        // Check that a new ai_cardLand was queued with confirmed_card
        $topOp = $this->game->machine->createTopOperationFromDbForOwner(null);
        $this->assertNotNull($topOp);
        $this->assertEquals("ai_cardLand", $topOp->getType());
        $this->assertEquals("card_land_1", $topOp->getDataField("confirmed_card"));
    }
}
