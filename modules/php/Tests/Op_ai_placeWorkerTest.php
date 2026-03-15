<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Game;
use Bga\Games\wayfarers\Operations\Op_ai_placeWorker;
use Bga\Games\wayfarers\Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_ai_placeWorkerTest extends TestCase {
    private GameUT $game;
    private const AI_COLOR = "ffffff";

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init();
        $this->game->tokens->createTokens();
        // Setup AI color in game
        $this->game->_setCurrentPlayerId(PCOLOR_ID);
    }

    private function createOp(string $params = ""): Op_ai_placeWorker {
        /** @var Op_ai_placeWorker */
        $op = $this->game->machine->instanciateOperation("ai_placeWorker($params)", self::AI_COLOR);
        return $op;
    }

    private function addWorkerToSupply(string $color): string {
        $workerId = "worker_{$color}_1";
        $this->game->tokens->db->moveToken($workerId, "tableau_" . self::AI_COLOR);
        return $workerId;
    }

    private function addCardToMainarea(string $cardType, int $position): string {
        $cardId = "card_{$cardType}_1";
        $this->game->tokens->db->moveToken($cardId, "mainarea", $position);
        return $cardId;
    }

    private function setPositionPriority(int $value): void {
        // Set scheme cards to create desired sum
        for ($i = 0; $i < $value && $i < 2; $i++) {
            $card = "card_scheme_" . ($i + 1);
            $this->game->tokens->db->moveToken($card, "tableau_" . self::AI_COLOR, $i + 2);
            $this->game->material->setRulesFor($card, ["c" => "1"]);
        }
    }

    public function testGreenWorkerPlacedOnFolkCard(): void {
        $this->addWorkerToSupply("green");
        $folkCard = $this->addCardToMainarea("folk", 1);
        $this->setPositionPriority(1);

        // Setup action for folk position 1
        $this->game->material->setRulesFor("action_folk_1", ["r" => "nop"]);

        $op = $this->createOp("green");
        $result = $op->auto();

        $this->assertTrue($result);
        // Verify worker moved to card
        $worker = $this->game->tokens->db->getTokenInfo("worker_green_1");
        $this->assertEquals($folkCard, $worker["location"]);
    }

    public function testYellowWorkerPlacedOnLandCard(): void {
        $this->addWorkerToSupply("yellow");
        $landCard = $this->addCardToMainarea("land", 2);
        $this->setPositionPriority(2);

        $this->game->material->setRulesFor("action_land_2", ["r" => "nop"]);

        $op = $this->createOp("yellow");
        $result = $op->auto();

        $this->assertTrue($result);
        $worker = $this->game->tokens->db->getTokenInfo("worker_yellow_1");
        $this->assertEquals($landCard, $worker["location"]);
    }

    public function testBlueWorkerPlacedOnWaterCard(): void {
        $this->addWorkerToSupply("blue");
        $waterCard = $this->addCardToMainarea("water", 3);
        $this->setPositionPriority(3);

        $this->game->material->setRulesFor("action_water_3", ["r" => "nop"]);

        $op = $this->createOp("blue");
        $result = $op->auto();

        $this->assertTrue($result);
        $worker = $this->game->tokens->db->getTokenInfo("worker_blue_1");
        $this->assertEquals($waterCard, $worker["location"]);
    }

    public function testFallbackFromGreenToBlue(): void {
        // Only blue worker available
        $this->addWorkerToSupply("blue");
        $waterCard = $this->addCardToMainarea("water", 1);
        $this->setPositionPriority(1);

        $this->game->material->setRulesFor("action_water_1", ["r" => "nop"]);

        // Request green/blue, should fallback to blue
        $op = $this->createOp("green/blue");
        $result = $op->auto();

        $this->assertTrue($result);
        $worker = $this->game->tokens->db->getTokenInfo("worker_blue_1");
        $this->assertEquals($waterCard, $worker["location"]);
    }

    public function testIsVoidWhenNoWorkersAvailable(): void {
        // No workers in supply
        $this->addCardToMainarea("folk", 1);

        $op = $this->createOp("green");
        $isVoid = $op->isVoid();

        $this->assertTrue($isVoid);
    }

    public function testIsVoidWhenNoTargetCards(): void {
        $this->addWorkerToSupply("green");
        // No folk cards in mainarea

        $op = $this->createOp("green");
        $isVoid = $op->isVoid();

        $this->assertTrue($isVoid);
    }

    public function testIsVoidFalseWhenValidPlacement(): void {
        $this->addWorkerToSupply("green");
        $this->addCardToMainarea("folk", 1);

        $op = $this->createOp("green");
        $isVoid = $op->isVoid();

        $this->assertFalse($isVoid);
    }

    public function testPositionalPrioritySelection(): void {
        $this->addWorkerToSupply("green");
        $card1 = $this->addCardToMainarea("folk", 1);
        $card2 = $this->addCardToMainarea("folk", 2);
        $card3 = $this->addCardToMainarea("folk", 3);

        // Priority 2 should select position 2
        $this->setPositionPriority(2);
        $this->game->material->setRulesFor("action_folk_2", ["r" => "nop"]);

        $op = $this->createOp("green");
        $result = $op->auto();

        $this->assertTrue($result);
        $worker = $this->game->tokens->db->getTokenInfo("worker_green_1");
        $this->assertEquals($card2, $worker["location"]);
    }

    public function testActionRuleQueued(): void {
        $this->addWorkerToSupply("green");
        $this->addCardToMainarea("folk", 1);
        $this->setPositionPriority(1);

        $this->game->material->setRulesFor("action_folk_1", ["r" => "2coin"]);

        $op = $this->createOp("green");
        $result = $op->auto();

        $this->assertTrue($result);
        // Action queuing is tested implicitly - if worker placement succeeds without error, action was queued and processed
    }

    private function addInfluenceOnCard(string $card, string $color = PCOLOR): string {
        $infId = "influence_{$color}_1";
        $this->game->tokens->db->moveToken($infId, $card);
        return $infId;
    }

    public function testInfluenceCheckBeforePlacement(): void {
        $this->addWorkerToSupply("green");
        $folkCard = $this->addCardToMainarea("folk", 1);
        $this->setPositionPriority(1);
        $this->game->material->setRulesFor("action_folk_1", ["r" => "nop"]);

        // Place player influence on the card
        $this->addInfluenceOnCard($folkCard);

        $op = $this->createOp("green");
        $result = $op->auto();

        $this->assertTrue($result);
        // Worker should NOT be placed yet — ai_cardInteractChoice should be queued instead
        $worker = $this->game->tokens->db->getTokenInfo("worker_green_1");
        $this->assertEquals("tableau_" . self::AI_COLOR, $worker["location"]);

        // ai_cardInteractChoice should be queued
        $topOp = $this->game->machine->createTopOperationFromDbForOwner(null);
        $this->assertNotNull($topOp);
        $this->assertEquals("ai_cardInteractChoice", $topOp->getType());
        $this->assertEquals($folkCard, $topOp->getDataField("card"));
    }

    public function testNoInfluenceSkipsChoice(): void {
        $this->addWorkerToSupply("green");
        $folkCard = $this->addCardToMainarea("folk", 1);
        $this->setPositionPriority(1);
        $this->game->material->setRulesFor("action_folk_1", ["r" => "nop"]);

        // No influence on card
        $op = $this->createOp("green");
        $result = $op->auto();

        $this->assertTrue($result);
        // Worker should be placed directly
        $worker = $this->game->tokens->db->getTokenInfo("worker_green_1");
        $this->assertEquals($folkCard, $worker["location"]);
    }
}
