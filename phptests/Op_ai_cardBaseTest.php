<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Operations\Op_ai_cardBase;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_ai_cardBaseTest extends TestCase {
    private GameUT $game;
    private const AI_COLOR = "ffffff";

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init(1);
        $this->game->tokens->createTokens();
        $this->game->setupSolo();
        $this->game->_setCurrentPlayerId(PCOLOR_ID);
    }

    private function createOp(string $type = "ai_cardLand", array $data = []): Op_ai_cardBase {
        /** @var Op_ai_cardBase */
        $op = $this->game->machine->instanciateOperation($type, self::AI_COLOR, $data);
        return $op;
    }

    private function addCardToMainarea(string $cardType, int $position): string {
        $cardId = "card_{$cardType}_{$position}";
        $this->game->tokens->db->moveToken($cardId, "mainarea", $position);
        return $cardId;
    }

    private function setPositionPriority(int $value): void {
        for ($i = 0; $i < $value && $i < 2; $i++) {
            $card = "card_scheme_" . ($i + 1);
            $this->game->tokens->db->moveToken($card, "tableau_" . self::AI_COLOR, $i + 2);
            $this->game->material->setRulesFor($card, ["c" => "1"]);
        }
    }

    public function testSelectCardWithFullDisplay(): void {
        $this->addCardToMainarea("land", 1);
        $this->addCardToMainarea("land", 2);
        $this->addCardToMainarea("land", 3);
        $this->addCardToMainarea("land", 4);
        $this->setPositionPriority(2);

        $op = $this->createOp("ai_cardLand");
        $card = $op->selectCard();

        $this->assertEquals("card_land_2", $card);
    }

    public function testSelectCardWrapsWhenPriorityExceedsAvailable(): void {
        // Only 3 cards on display but priority is 4
        $this->addCardToMainarea("land", 1);
        $this->addCardToMainarea("land", 2);
        $this->addCardToMainarea("land", 3);

        // Set priority to 4 (sum of two scheme cards with silver value 2 each)
        $card1 = "card_scheme_1";
        $this->game->tokens->db->moveToken($card1, "tableau_" . self::AI_COLOR, 2);
        $this->game->material->setRulesFor($card1, ["c" => "2"]);
        $card2 = "card_scheme_2";
        $this->game->tokens->db->moveToken($card2, "tableau_" . self::AI_COLOR, 3);
        $this->game->material->setRulesFor($card2, ["c" => "2"]);

        $op = $this->createOp("ai_cardLand");
        // Should wrap around: (4-1) % 3 = 0, so pick first card
        $card = $op->selectCard();

        $this->assertEquals("card_land_1", $card);
    }

    public function testSelectCardWithDeniedCardsWraps(): void {
        // 4 cards, deny one, priority 4 -> only 3 available, should wrap
        $this->addCardToMainarea("land", 1);
        $this->addCardToMainarea("land", 2);
        $this->addCardToMainarea("land", 3);
        $this->addCardToMainarea("land", 4);

        $card1 = "card_scheme_1";
        $this->game->tokens->db->moveToken($card1, "tableau_" . self::AI_COLOR, 2);
        $this->game->material->setRulesFor($card1, ["c" => "2"]);
        $card2 = "card_scheme_2";
        $this->game->tokens->db->moveToken($card2, "tableau_" . self::AI_COLOR, 3);
        $this->game->material->setRulesFor($card2, ["c" => "2"]);

        $op = $this->createOp("ai_cardLand", ["denied" => ["card_land_2"]]);
        // After filtering denied: [card_land_1, card_land_3, card_land_4] (3 items)
        // Priority 4: (4-1) % 3 = 0 -> card_land_1
        $card = $op->selectCard();

        $this->assertEquals("card_land_1", $card);
    }

    public function testSelectCardReturnsNullWhenNoCardsAvailable(): void {
        // No land cards on display at all
        $this->setPositionPriority(1);

        $op = $this->createOp("ai_cardLand");
        $card = $op->selectCard();

        $this->assertNull($card);
    }

    public function testAutoSkipsWhenNoCardsAvailable(): void {
        // No land cards on display
        $this->setPositionPriority(1);

        $op = $this->createOp("ai_cardLand");
        $result = $op->auto();

        $this->assertTrue($result);
    }
}
