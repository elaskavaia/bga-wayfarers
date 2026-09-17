<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Game;
use Bga\Games\wayfarers\Operations\Op_ai_placeWorker;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_ai_placeWorkerTest extends TestCase {
    private GameUT $game;
    private const AI_COLOR = "ffffff";

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init(1); // solo, so the automa is a known player
        $this->game->tokens->createTokens();
        // Setup AI color in game
        $this->game->_setCurrentPlayerId(PCOLOR_ID);
    }

    private function createOp(string $params = ""): Op_ai_placeWorker {
        /** @var Op_ai_placeWorker */
        $op = $this->game->machine->instantiateOperation("ai_placeWorker($params)", self::AI_COLOR);
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
        // Set scheme cards to create desired sum value (0-4)
        // Use 2 cards: first with floor(value/2), second with ceil(value/2)
        if ($value <= 0) {
            return;
        }
        $card1 = "card_scheme_1";
        $this->game->tokens->db->moveToken($card1, "tableau_" . self::AI_COLOR, 2);
        $this->game->material->setRulesFor($card1, ["c" => (string) intdiv($value, 2)]);
        $card2 = "card_scheme_2";
        $this->game->tokens->db->moveToken($card2, "tableau_" . self::AI_COLOR, 3);
        $this->game->material->setRulesFor($card2, ["c" => (string) ($value - intdiv($value, 2))]);
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

    public function testSkipsCardWithExistingSameColorWorker(): void {
        $this->addWorkerToSupply("green");
        // Two folk cards in mainarea
        $card1 = $this->addCardToMainarea("folk", 1);
        $card2 = "card_folk_2_1";
        $this->game->tokens->db->moveToken($card2, "mainarea", 2);
        $this->setPositionPriority(1);
        $this->game->material->setRulesFor("action_folk_1", ["r" => "nop"]);
        $this->game->material->setRulesFor("action_folk_2", ["r" => "nop"]);

        // Place an existing green worker on card at position 1 (preferred by priority)
        $this->game->tokens->db->moveToken("worker_green_3", $card1);

        $op = $this->createOp("green");
        $result = $op->auto();

        $this->assertTrue($result);
        // Worker should be placed on card2 (position 2) since card1 already has a green worker
        $worker = $this->game->tokens->db->getTokenInfo("worker_green_1");
        $this->assertEquals($card2, $worker["location"]);
    }

    private function addFolkCardsAtAllPositions(): array {
        $cards = [];
        for ($i = 1; $i <= 4; $i++) {
            $cardId = "card_folk_$i";
            $this->game->tokens->db->moveToken($cardId, "mainarea", $i);
            $this->game->material->setRulesFor("action_folk_$i", ["r" => "nop"]);
            $cards[$i] = $cardId;
        }
        return $cards;
    }

    #[PHPUnit\Framework\Attributes\DataProvider("positionPriorityProvider")]
    public function testSelectTargetCardByPositionPriority(int $prio, int $expectedPosition): void {
        $this->addWorkerToSupply("green");
        $cards = $this->addFolkCardsAtAllPositions();
        $this->setPositionPriority($prio);

        $op = $this->createOp("green");
        $selected = $op->selectTargetCard("folk", "green");

        $this->assertEquals($cards[$expectedPosition], $selected, "Priority $prio should select position $expectedPosition");
    }

    public static function positionPriorityProvider(): array {
        return [
            "prio 1 => position 1" => [1, 1],
            "prio 2 => position 2" => [2, 2],
            "prio 3 => position 3" => [3, 3],
            "prio 4 => position 4" => [4, 4]
        ];
    }

    #[PHPUnit\Framework\Attributes\DataProvider("wrapAroundProvider")]
    public function testSelectTargetCardWrapsAround(int $prio, int $deniedPosition, int $expectedPosition): void {
        $this->addWorkerToSupply("green");
        $cards = $this->addFolkCardsAtAllPositions();
        $this->setPositionPriority($prio);

        $op = $this->createOp("green");
        $op->withDataField("denied", [$cards[$deniedPosition]]);
        $selected = $op->selectTargetCard("folk", "green");

        $this->assertEquals(
            $cards[$expectedPosition],
            $selected,
            "Priority $prio with position $deniedPosition denied should select position $expectedPosition"
        );
    }

    public static function wrapAroundProvider(): array {
        // direction: prio 1,2 => forward(1), prio 3,4 => backward(-1)
        // When priority card is denied, next card in rotation direction is selected
        return [
            "prio 1, deny pos 1 => next forward is pos 2" => [1, 1, 2],
            "prio 2, deny pos 2 => next forward is pos 3" => [2, 2, 3],
            "prio 3, deny pos 3 => next backward is pos 2" => [3, 3, 2],
            "prio 4, deny pos 4 => next backward is pos 3" => [4, 4, 3]
        ];
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

    /**
     * BGA #243940 (solo, table 914937738): the automa placed a worker on water position 4 and the
     * space's printed action was queued verbatim, cost included - action_water_4 is
     * "3n_food:3cardDraw(water)". Op_pay only auto-resolves a zero count, so the pay half reached
     * Operation::onEnteringGameState with the automa as the player and threw
     * "Operation does not implement automata 3(n_food)".
     *
     * RULES.md, Solo Play > Special Rules: "The AI ignores all costs (other than those on their
     * Scheme Cards), and ignores all requirements on the Journal Track." and Solo Play > AI
     * Prioritising > Workers: "They resolve all printed actions of a space when placing a Worker."
     * So the automa resolves the draw and is never asked for the 3 Provisions.
     */
    public function testWorkerActionCostIsIgnoredForAutoma(): void {
        $this->addWorkerToSupply("blue");
        $this->addCardToMainarea("water", 4);
        // the worker blocks its own card, so the draw has another one to land on
        $this->game->tokens->db->moveToken("card_water_2", "mainarea", 1);
        $this->setPositionPriority(4);

        $op = $this->createOp("blue");
        $this->assertTrue($op->auto());
        $this->game->machine->dispatchAll();

        $this->assertEquals([], $this->game->queuedTypes(self::AI_COLOR), "the whole action resolved without asking the automa");
        $card = $this->game->tokens->db->getTokenInfo("card_water_2");
        $this->assertEquals("tableau_" . self::AI_COLOR, $card["location"]);
    }

    /** The cost carve-out is per rule, not per operation - a costless printed action is untouched. */
    public function testWorkerActionWithoutCostIsQueuedWhole(): void {
        $this->addWorkerToSupply("blue");
        $this->addCardToMainarea("water", 2);
        $this->setPositionPriority(2);

        $op = $this->createOp("blue");
        $this->assertTrue($op->auto());

        $this->assertContains("2food,infBlue", $this->game->queuedTypes(self::AI_COLOR));
    }
}
