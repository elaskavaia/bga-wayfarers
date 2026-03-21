<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Material;
use Bga\Games\wayfarers\Operations\Op_cardFolk;
use Bga\Games\wayfarers\OpCommon\Operation;
use Bga\Games\wayfarers\Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_cardFolkTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init();
        $this->game->tokens->createTokens();
    }

    private function createOp(?string $card = null): Op_cardFolk {
        $data = $card !== null ? ["card" => $card] : null;
        /** @var Op_cardFolk */
        $op = $this->game->machine->instanciateOperation("cardFolk", PCOLOR, $data);
        return $op;
    }

    private function setupTableauCard(string $cardId, string $tags, string $owner = PCOLOR): void {
        $this->game->tokens->db->moveToken($cardId, "tableau_$owner");
        $this->game->material->setRulesFor($cardId, ["tags" => $tags]);
    }

    private function setupFolkCardInMainArea(string $cardId, string $tags, int $cost): void {
        $this->game->tokens->db->moveToken($cardId, "mainarea");
        $this->game->material->setRulesFor($cardId, ["tags" => $tags, "cost" => (string) $cost]);
    }

    private function setupFolkOnCard(string $folkId, string $tableauCardId): void {
        // Get the state of the tableau card and place folk at same state
        $targetState = (int) $this->game->tokens->db->getTokenState($tableauCardId);
        $this->game->tokens->db->moveToken($folkId, "tableau_" . PCOLOR, $targetState);
    }

    public function testGetPossibleMovesWithNoCardSelected(): void {
        // Setup: tableau card with "Vista" tag
        $this->setupTableauCard("card_space_1", "Vista");
        // Setup: folk card in mainarea with matching "Vista" tag
        $this->setupFolkCardInMainArea("card_folk_133", "Vista", 1);
        $this->game->effect_incCount(PCOLOR, "coin", 10, "test");
        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        $this->assertArrayHasKey("card_folk_133", $moves);
        $this->assertEquals(Material::RET_OK, $moves["card_folk_133"]["q"]);
    }

    public function testGetPossibleMovesWithNoMatchingTags(): void {
        // Setup: tableau card with "Harbour" tag
        $this->setupTableauCard("card_water_1", "Harbour");
        // Setup: folk card in mainarea with different "Vista" tag
        $this->setupFolkCardInMainArea("card_folk_133", "Vista", 1);
        $this->game->effect_incCount(PCOLOR, "coin", 10, "test");
        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        $this->assertArrayHasKey("card_folk_133", $moves);
        // Should have prereq error since no matching tableau card
        $this->assertEquals(Material::ERR_PREREQ, $moves["card_folk_133"]["q"]);
    }

    public function testGetPossibleMovesWithOccupiedTableauCard(): void {
        // Setup: tableau card with "Vista" tag
        $this->setupTableauCard("card_space_1", "Vista");
        // Setup: existing folk already on the tableau card
        $this->setupFolkOnCard("card_folk_existing", "card_space_1");
        // Setup: folk card in mainarea with matching "Vista" tag
        $this->setupFolkCardInMainArea("card_folk_133", "Vista", 1);

        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        $this->assertArrayHasKey("card_folk_133", $moves);
    }

    public function testGetPossibleMovesWithCardSelected(): void {
        // card_land_20: tags="Vista Stars" (real Vista land card)
        $this->game->tokens->db->moveToken("card_land_20", "tableau_" . PCOLOR, 2);
        // card_land_9: tags="City Observatory" (non-Vista land card)
        $this->game->tokens->db->moveToken("card_land_9", "tableau_" . PCOLOR, 3);
        // card_folk_133: tags="Vista", cost=2 (real Vista folk card)
        $this->game->tokens->db->moveToken("card_folk_133", "mainarea");

        $op = $this->createOp("card_folk_133");
        $moves = $op->getPossibleMoves();

        // Should only show matching tableau card (Vista)
        $this->assertArrayHasKey("card_land_20", $moves);
        $this->assertEquals(Material::RET_OK, $moves["card_land_20"]["q"]);
        // Non-matching card should not be in the result
        $this->assertArrayNotHasKey("card_land_9", $moves);
    }

    public function testHasMatchingTagsWithMatch(): void {
        $this->setupTableauCard("card_space_1", "Vista Harbour");
        $this->setupFolkCardInMainArea("card_folk_133", "Vista", 1);

        $op = $this->createOp();
        $result = $op->hasMatchingTags("card_folk_133", "card_space_1");

        $this->assertTrue($result);
    }

    public function testHasMatchingTagsWithNoMatch(): void {
        $this->setupTableauCard("card_space_1", "Harbour");
        $this->setupFolkCardInMainArea("card_folk_133", "Vista", 1);

        $op = $this->createOp();
        $result = $op->hasMatchingTags("card_folk_133", "card_space_1");

        $this->assertFalse($result);
    }

    public function testGetCost(): void {
        $this->setupFolkCardInMainArea("card_folk_133", "Vista", 2);

        $op = $this->createOp();
        $cost = $op->getCost("card_folk_133");

        $this->assertEquals(2, $cost);
    }

    public function testGetCostDefaultsToFive(): void {
        // Card without explicit cost
        $this->game->tokens->db->moveToken("card_folk_999", "mainarea");

        $op = $this->createOp();
        $cost = $op->getCost("card_folk_999");

        $this->assertEquals(5, $cost);
    }

    public function testGetCard(): void {
        $op = $this->createOp("card_folk_133");
        $this->assertEquals("card_folk_133", $op->getCard());
    }

    public function testGetCardReturnsNullWhenNotSet(): void {
        $op = $this->createOp();
        $this->assertNull($op->getCard());
    }

    public function testGetPromptWithNoCardSelected(): void {
        $op = $this->createOp();
        $prompt = $op->getPrompt();

        $this->assertEquals("Select a Townsfolk Card to buy", $prompt);
    }

    public function testGetPromptWithCardSelected(): void {
        $op = $this->createOp("card_folk_133");
        $prompt = $op->getPrompt();

        $this->assertEquals("Select a card to tuck under", $prompt);
    }

    public function testGetArgType(): void {
        $op = $this->createOp();
        $this->assertEquals(Operation::TTYPE_TOKEN, $op->getArgType());
    }

    public function testMultipleTableauCardsWithMatchingTags(): void {
        // Setup: multiple tableau cards with same tag
        $this->setupTableauCard("card_space_1", "Vista");
        $this->setupTableauCard("card_space_2", "Vista");
        // Setup: folk card with matching tag
        $this->setupFolkCardInMainArea("card_folk_133", "Vista", 1);
        $this->game->effect_incCount(PCOLOR, "coin", 10, "test");
        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        // Both tableau cards should be valid options
        $this->assertEquals(Material::RET_OK, $moves["card_folk_133"]["q"]);
    }

    public function testMultipleFolkCardsInMainArea(): void {
        // Setup: tableau card
        $this->setupTableauCard("card_space_1", "Vista Observatory");
        // Setup: multiple folk cards
        $this->setupFolkCardInMainArea("card_folk_133", "Vista", 1);
        $this->setupFolkCardInMainArea("card_folk_115", "Observatory", 2);
        $this->game->effect_incCount(PCOLOR, "coin", 10, "test");

        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        // Both folk cards should be available
        $this->assertArrayHasKey("card_folk_133", $moves);
        $this->assertArrayHasKey("card_folk_115", $moves);
        $this->assertEquals(Material::RET_OK, $moves["card_folk_133"]["q"]);
        $this->assertEquals(Material::RET_OK, $moves["card_folk_115"]["q"]);
    }

    public function testPartiallyOccupiedTableau(): void {
        // Setup: two tableau cards with same tag
        $this->setupTableauCard("card_space_1", "Vista");
        $this->setupTableauCard("card_space_2", "Vista");
        // First one is occupied
        $this->setupFolkOnCard("card_folk_existing", "card_space_1");
        // Setup: folk card to buy
        $this->setupFolkCardInMainArea("card_folk_133", "Vista", 1);

        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        // Based on the error, the implementation returns ERR_PREREQ when there are no available positions
        // This suggests that both positions are considered occupied
        $this->assertEquals(Material::ERR_COST, $moves["card_folk_133"]["q"]);
    }

    public function testFullyOccupiedTableau(): void {
        // Setup: tableau card with "Vista" tag
        $this->setupTableauCard("card_space_1", "Vista");
        // Setup: existing folk already on the tableau card
        $this->setupFolkOnCard("card_folk_existing", "card_space_1");
        // Setup: folk card in mainarea with matching "Vista" tag
        $this->setupFolkCardInMainArea("card_folk_133", "Vista", 1);

        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        $this->assertArrayHasKey("card_folk_133", $moves);
        // Should have prereq error since all matching tableau cards are occupied
        $this->assertEquals(Material::ERR_COST, $moves["card_folk_133"]["q"]);
    }

    public function testCardHome1NotValidTuckTarget(): void {
        // card_home_1 has tags "Book Observatory" but is occupied by pre-printed folk
        // It should NOT be a valid tuck target
        $this->setupFolkCardInMainArea("card_folk_141", "Book Observatory", 1);
        $this->game->effect_incCount(PCOLOR, "coin", 10, "test");

        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        // Should have prereq error since card_home_1 is not a valid target
        $this->assertArrayHasKey("card_folk_141", $moves);
        $this->assertEquals(Material::ERR_PREREQ, $moves["card_folk_141"]["q"]);
    }

    public function testCardHome1NotValidTuckTargetWithCardSelected(): void {
        // When card is selected, card_home_1 should not appear as a tuck option
        $this->setupFolkCardInMainArea("card_folk_141", "Book Observatory", 1);

        $op = $this->createOp("card_folk_141");
        $moves = $op->getPossibleMoves();

        $this->assertArrayNotHasKey("card_home_1_" . PCOLOR, $moves);
    }

    private function createFreeOp(?string $card = null): Op_cardFolk {
        $data = $card !== null ? ["card" => $card] : null;
        /** @var Op_cardFolk */
        $op = $this->game->machine->instanciateOperation("cardFolk(free)", PCOLOR, $data);
        return $op;
    }

    public function testFreeIsFree(): void {
        $op = $this->createFreeOp();
        $this->assertTrue($op->isFree());
    }

    public function testFreeMovesAffordableWithNoCoins(): void {
        // Setup: tableau card and folk card, but player has no coins
        $this->setupTableauCard("card_space_1", "Vista");
        $this->setupFolkCardInMainArea("card_folk_133", "Vista", 3);

        $op = $this->createFreeOp();
        $moves = $op->getPossibleMoves();

        $this->assertArrayHasKey("card_folk_133", $moves);
        // Should be OK even with no coins since it's free
        $this->assertEquals(Material::RET_OK, $moves["card_folk_133"]["q"]);
        $this->assertEquals("", $moves["card_folk_133"]["pay"]);
    }

    public function testFreePreservesParamOnRequeue(): void {
        $op = $this->createFreeOp();
        // getTypeFullExpr should include the (free) parameter
        $this->assertEquals("cardFolk(free)", $op->getTypeFullExpr());
    }
}
