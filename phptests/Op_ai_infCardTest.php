<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Operations\Op_ai_infCard;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_ai_infCardTest extends TestCase {
    private GameUT $game;
    private const AI_COLOR = "ffffff";
    private int $inf = 1;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init(1);
        $this->game->tokens->createTokens();
        $this->game->setupSolo();
        // Setup AI color in game
        $this->game->_setCurrentPlayerId(PCOLOR_ID);

        $this->inf = 0;
    }

    private function createOp(): Op_ai_infCard {
        /** @var Op_ai_infCard */
        $op = $this->game->machine->instanciateOperation("ai_infCard", self::AI_COLOR);
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

    private function setResourceTrackPosition(int $position): void {
        $trackerId = "tracker_res_" . self::AI_COLOR;
        $this->game->tokens->db->setTokenState($trackerId, $position);
    }

    private function setResourceTrackRules(int $position, string $color): void {
        $this->game->material->setRulesFor("spot_res_$position", ["t" => $color]);
    }

    private function setPositionPriority(int $value): void {
        for ($i = 0; $i < $value && $i < 2; $i++) {
            $card = "card_scheme_" . ($i + 1);
            $this->game->tokens->db->moveToken($card, "tableau_" . self::AI_COLOR, $i + 2);
            $this->game->material->setRulesFor($card, ["c" => "1"]);
        }
    }

    public function testInfluenceOnSpaceCardWhenBlackPriority(): void {
        $spaceCard = $this->addCardToMainarea("space", 1);
        $this->setResourceTrackPosition(0);
        $this->setResourceTrackRules(0, "black");
        $this->setPositionPriority(1);

        $op = $this->createOp();
        $result = $op->auto();

        $this->assertTrue($result);
        // Verify influence created and placed on space card
        $influences = $this->game->tokens->getTokensOfTypeInLocation("influence_" . self::AI_COLOR, $spaceCard);
        $this->assertCount(1, $influences);
    }

    public function testInfluenceOnWaterCardWhenBluePriority(): void {
        $waterCard = $this->addCardToMainarea("water", 2);
        $this->setResourceTrackPosition(1);
        $this->setResourceTrackRules(1, "blue");
        $this->setPositionPriority(2);

        $op = $this->createOp();
        $result = $op->auto();

        $this->assertTrue($result);
        $influences = $this->game->tokens->getTokensOfTypeInLocation("influence_" . self::AI_COLOR, $waterCard);
        $this->assertCount(1, $influences);
    }

    public function testSkipsCardWithExistingInfluence(): void {
        $waterCard1 = $this->addCardToMainarea("water", 1);
        $waterCard2 = $this->addCardToMainarea("water", 2);

        // Add influence to first water card
        $this->addInfluenceToCard($waterCard1);

        $this->setResourceTrackPosition(1);
        $this->setResourceTrackRules(1, "blue");
        $this->setPositionPriority(1);

        $op = $this->createOp();
        $result = $op->auto();

        $this->assertTrue($result);
        // Should place on second card (first has influence)
        $influences = $this->game->tokens->getTokensOfTypeInLocation($waterCard2);
        $this->assertCount(1, $influences);
    }

    public function testFallbackToNextColorWhenNoPriorityCards(): void {
        // No water cards (blue priority), but land cards available
        $landCard = $this->addCardToMainarea("land", 1);

        $this->setResourceTrackPosition(1); // Blue priority
        $this->setResourceTrackRules(1, "blue");
        $this->setPositionPriority(1);

        $op = $this->createOp();
        $result = $op->auto();

        $this->assertTrue($result);
        // Should fallback to next color (yellow → land)
        $influences = $this->game->tokens->getTokensOfTypeInLocation("influence_" . self::AI_COLOR, $landCard);
        $this->assertCount(1, $influences);
    }

    public function testIsVoidWhenNoAvailableCards(): void {
        // No cards in mainarea
        $op = $this->createOp();
        $isVoid = $op->isVoid();

        $this->assertTrue($isVoid);
    }

    public function testIsVoidWhenAllCardsHaveInfluence(): void {
        $card1 = $this->addCardToMainarea("water", 1);
        $card2 = $this->addCardToMainarea("land", 1);
        $card3 = $this->addCardToMainarea("folk", 1);

        // Add influence to all cards
        $this->addInfluenceToCard($card1);
        $this->addInfluenceToCard($card2);
        $this->addInfluenceToCard($card3);

        $op = $this->createOp();
        $isVoid = $op->isVoid();

        $this->assertTrue($isVoid);
    }

    public function testCardWithWorkerIsStillAvailable(): void {
        $waterCard = $this->addCardToMainarea("water", 1);

        // Place a worker on the card — should NOT block influence
        $this->game->tokens->db->moveToken("worker_blue_1", $waterCard);

        $this->setResourceTrackPosition(1);
        $this->setResourceTrackRules(1, "blue");
        $this->setPositionPriority(1);

        $op = $this->createOp();
        $this->assertFalse($op->isVoid());

        $result = $op->auto();
        $this->assertTrue($result);

        $influences = $this->game->tokens->getTokensOfTypeInLocation("influence_" . self::AI_COLOR, $waterCard);
        $this->assertCount(1, $influences);
    }

    public function testCardWithInfluenceAndWorkerIsOccupied(): void {
        $waterCard = $this->addCardToMainarea("water", 1);

        // Place both a worker and influence on the card
        $this->game->tokens->db->moveToken("worker_blue_1", $waterCard);
        $this->addInfluenceToCard($waterCard);

        $op = $this->createOp();
        $cards = $op->getCards("water");
        $this->assertEquals(\Bga\Games\wayfarers\Material::ERR_OCCUPIED, $cards["p1"]["q"]);
    }

    public function testIsVoidFalseWhenCardsAvailable(): void {
        $this->addCardToMainarea("water", 1);

        $op = $this->createOp();
        $isVoid = $op->isVoid();

        $this->assertFalse($isVoid);
    }

    public function testUnlimitedInfluenceCreation(): void {
        // Place multiple influences
        $card1 = $this->addCardToMainarea("water", 1);
        $card2 = $this->addCardToMainarea("land", 1);

        $this->setResourceTrackPosition(1);
        $this->setResourceTrackRules(1, "blue");
        $this->setPositionPriority(1);

        $op1 = $this->createOp();
        $op1->auto();

        $this->setResourceTrackPosition(2);
        $this->setResourceTrackRules(2, "yellow");

        $op2 = $this->createOp();
        $op2->auto();

        // Verify two different influence tokens created
        $inf1 = $this->game->tokens->getTokensOfTypeInLocation("influence_" . self::AI_COLOR, $card1);
        $inf2 = $this->game->tokens->getTokensOfTypeInLocation("influence_" . self::AI_COLOR, $card2);

        $this->assertCount(1, $inf1);
        $this->assertCount(1, $inf2);
    }

    public function testPositionalPrioritySelection(): void {
        $card1 = $this->addCardToMainarea("water", 1);
        $card2 = $this->addCardToMainarea("water", 2);
        $card3 = $this->addCardToMainarea("water", 3);

        $this->setResourceTrackPosition(1);
        $this->setResourceTrackRules(1, "blue");
        $this->setPositionPriority(2); // Should select position 2

        $op = $this->createOp();
        $result = $op->auto();

        $this->assertTrue($result);
        // Verify influence placed on card at position 2
        $influences = $this->game->tokens->getTokensOfTypeInLocation($card2);
        $this->assertCount(1, $influences);
    }
}
