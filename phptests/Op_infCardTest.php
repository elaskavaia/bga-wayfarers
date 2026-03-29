<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Material;
use Bga\Games\wayfarers\Operations\Op_infCard;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_infCardTest extends TestCase {
    private GameUT $game;
    private int $inf = 0;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init(2);
        $this->game->tokens->createTokens();
        $this->game->_setCurrentPlayerId(PCOLOR_ID);
        $this->inf = 0;
    }

    private function createOp(): Op_infCard {
        /** @var Op_infCard */
        $op = $this->game->machine->instanciateOperation("infCard", PCOLOR);
        return $op;
    }

    private function addCardToMainarea(string $cardType, int $position): string {
        $cardId = "card_{$cardType}_" . $position;
        $this->game->tokens->db->moveToken($cardId, "mainarea", $position);
        return $cardId;
    }

    private function addInfluenceToCard(string $cardId, string $owner = "ff0000"): void {
        $this->inf += 1;
        $infId = "influence_{$owner}_{$this->inf}";
        $this->game->tokens->db->moveToken($infId, $cardId);
    }

    public function testCardWithWorkerIsAvailable(): void {
        $landCard = $this->addCardToMainarea("land", 1);

        // Place a worker on the card — should NOT block influence
        $this->game->tokens->db->moveToken("worker_yellow_1", $landCard);

        $op = $this->createOp();
        $available = $op->getAvailableCards();

        $this->assertArrayHasKey($landCard, $available);
        $this->assertEquals(Material::RET_OK, $available[$landCard]["q"]);
    }

    public function testCardWithInfluenceIsNotAvailable(): void {
        $landCard = $this->addCardToMainarea("land", 1);
        $this->addInfluenceToCard($landCard);

        $op = $this->createOp();
        $available = $op->getAvailableCards();

        $this->assertArrayNotHasKey($landCard, $available);
    }

    public function testCardWithWorkerAndInfluenceIsNotAvailable(): void {
        $landCard = $this->addCardToMainarea("land", 1);
        $this->game->tokens->db->moveToken("worker_green_1", $landCard);
        $this->addInfluenceToCard($landCard);

        $op = $this->createOp();
        $available = $op->getAvailableCards();

        $this->assertArrayNotHasKey($landCard, $available);
    }

    public function testEmptyCardIsAvailable(): void {
        $folkCard = $this->addCardToMainarea("folk", 1);

        $op = $this->createOp();
        $available = $op->getAvailableCards();

        $this->assertArrayHasKey($folkCard, $available);
        $this->assertEquals(Material::RET_OK, $available[$folkCard]["q"]);
    }

    public function testCanSkipWhenNoCardsAvailable(): void {
        // No cards in mainarea
        $op = $this->createOp();
        $this->assertTrue($op->canSkip());
    }

    public function testCannotSkipWhenCardsAvailable(): void {
        $this->addCardToMainarea("water", 1);

        $op = $this->createOp();
        $this->assertFalse($op->canSkip());
    }
}
