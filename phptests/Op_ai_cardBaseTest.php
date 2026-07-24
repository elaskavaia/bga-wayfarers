<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Operations\Op_ai_cardBase;
use Bga\Games\wayfarers\Operations\Op_turn;
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

    /**
     * RULES: cannot acquire a card holding a worker placed this turn.
     * Worker state=1 marks "placed this turn". getPossibleMoves must filter such cards out.
     */
    public function testGetPossibleMovesExcludesCardWithThisTurnWorker(): void {
        $this->addCardToMainarea("land", 1);
        $this->addCardToMainarea("land", 2);
        // Place a this-turn worker (state=1) on card_land_1
        $this->game->tokens->db->moveToken("worker_yellow_1", "card_land_1", 1);
        // And a prior-turn worker (state=0) on card_land_2 — should NOT be filtered
        $this->game->tokens->db->moveToken("worker_yellow_2", "card_land_2", 0);

        $op = $this->createOp("ai_cardLand");
        $moves = $op->getPossibleMoves();

        $this->assertNotContains("card_land_1", $moves, "Card with this-turn worker must be filtered");
        $this->assertContains("card_land_2", $moves, "Card with prior-turn worker must still be available");
    }

    public function testSelectCardSkipsCardWithThisTurnWorker(): void {
        $this->addCardToMainarea("land", 1);
        $this->addCardToMainarea("land", 2);
        $this->addCardToMainarea("land", 3);
        $this->game->tokens->db->moveToken("worker_yellow_1", "card_land_1", 1);
        $this->setPositionPriority(1);

        $op = $this->createOp("ai_cardLand");
        $card = $op->selectCard();

        // card_land_1 filtered; remaining [card_land_2, card_land_3]; priority 1 -> index 0
        $this->assertEquals("card_land_2", $card);
    }

    /**
     * Regression test for the scenario reported in BGA #233581 (solo vs AI "Aida"):
     * "I placed a green worker on the acquire a townsfolk card, as a result the AI acquired the card."
     *
     * This documents CORRECT, rules-conformant behavior - the report was a misunderstanding,
     * not a bug. Placing a worker on a card takes that card's action; it does not reserve or
     * buy the card. Workers are a public resource (RULES.md:60), and whoever acquires a card
     * collects the workers on it (RULES.md:183, and :389 for the AI "just as you would").
     * The only protection is within-turn: a player cannot place and then acquire a card holding
     * a worker they placed that same turn (RULES.md:252) - which is what the state=1
     * "placed-this-turn" marker enforces in Op_ai_cardBase::getPossibleMoves.
     *
     * Sequence:
     *  1. On the human's turn a green worker is placed on a mainarea card (marker state=1).
     *     While that marker is set, the card is not an acquisition target (within-turn guard).
     *  2. The turn passes to the AI. Op_turn::auto clears the per-turn marker (state 1 -> 0),
     *     because the place-and-acquire restriction only applies within the placing turn.
     *  3. The card is now a normal public card. The AI acquires it and, per the rules, collects
     *     the public worker sitting on it. Nothing the human owned was taken.
     */
    public function testAiAcquiresPublicCardAndCollectsWorkerOnNextTurn(): void {
        // Townsfolk card in mainarea at position 1
        $card = "card_folk_1";
        $this->game->tokens->db->moveToken($card, "mainarea", 1);

        // Human places a green worker on that card this turn (state=1 = placed-this-turn marker)
        $this->game->tokens->db->moveToken("worker_green_1", $card, 1);

        // The AI has its own influence on the card (the reporter's guess for the cause)
        $this->game->tokens->db->moveToken("influence_" . self::AI_COLOR . "_3", $card);

        // Give the AI position priority 1 so it targets the mainarea slot 1 card
        $scheme = "card_scheme_1";
        $this->game->tokens->db->moveToken($scheme, "tableau_" . self::AI_COLOR, 2);
        $this->game->material->setRulesFor($scheme, ["c" => "1"]);

        // Within the placing turn the card is protected: you cannot acquire a card holding a
        // worker you placed this turn (RULES.md:252).
        /** @var Op_ai_cardBase $probe */
        $probe = $this->game->machine->instanciateOperation("ai_cardFolk", self::AI_COLOR);
        $this->assertNotContains($card, $probe->getPossibleMoves(), "Within its turn, a placed-this-turn worker protects its card");

        // 1) Turn passes to the AI -> Op_turn::auto clears the per-turn marker. This is correct:
        //    the place-and-acquire restriction only applies within the placing turn.
        /** @var Op_turn $turn */
        $turn = $this->game->machine->instanciateOperation("turn", self::AI_COLOR);
        $turn->auto();

        $workerState = (int) $this->game->tokens->db->getTokenState("worker_green_1");
        $this->assertEquals(0, $workerState, "Per-turn worker marker clears when the turn passes");

        // 2) The card is now a normal public card; the AI acquires it. AI owns the influence so it
        //    commits directly and queues ai_cardInteract.
        /** @var Op_ai_cardBase $acquire */
        $acquire = $this->game->machine->instanciateOperation("ai_cardFolk", self::AI_COLOR);
        $acquire->auto();

        // 3) Resolve the queued ai_cardInteract, which moves influence and the public worker to the buyer.
        $interact = $this->game->machine->createTopOperationFromDbForOwner(null);
        $this->assertNotNull($interact);
        $this->assertEquals("ai_cardInteract", $interact->getType());
        $interact->auto();

        // Correct per rules: the card was public (never reserved by the human), so the AI may acquire it,
        $cardOwner = $this->game->tokens->db->getTokenInfo($card)["location"];
        $this->assertEquals("tableau_" . self::AI_COLOR, $cardOwner, "AI legitimately acquires the public card");

        // and it collects the public worker on it (RULES.md:183 / :389), just as any player would.
        $workerOwner = $this->game->tokens->db->getTokenInfo("worker_green_1")["location"];
        $this->assertEquals("tableau_" . self::AI_COLOR, $workerOwner, "AI collects the public worker on the acquired card");
    }
}
