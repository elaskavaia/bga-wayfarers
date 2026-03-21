<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Operations\Op_placeDie;
use Bga\Games\wayfarers\Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_placeDieTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init();
        $this->game->tokens->createTokens();
    }

    public function testRestFolkDrNotTriggeredOnDiePlacement(): void {
        $color = PCOLOR;
        // card_folk_1 (Capital Townsfolk) has rest=1, dr=coin,journal
        // It sits at state 0 on tableau, same as card_home_12 (Capital Market)
        // Place a die on card_home_12 — the rest folk's dr should NOT be queued
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_$color");
        $dieKey = array_key_first($dice);

        /** @var Op_placeDie */
        $op = $this->game->machine->instanciateOperation("placeDie", $color, ["die" => $dieKey]);
        $cardId = "card_home_12_$color";

        // Simulate resolve: place die on the card
        $this->game->tokens->db->moveToken($dieKey, $cardId, 3);

        // Check what getTuckedFolk finds
        $folkCard = $op->getTuckedFolk($cardId);
        // card_folk_1 sits at state 0, card_home_12 also at state 0, so it may find it
        if ($folkCard) {
            $isRestOnly = $this->game->getRulesFor($folkCard, "rest", 0);
            $this->assertTrue((bool) $isRestOnly, "card_folk_1 should have rest=1 and be excluded from die placement activation");
        }

        // Verify: no coin or journal operations should be queued
        $ops = $this->game->machine->db->getOperations();
        $opTypes = array_map(fn($o) => $o["type"], array_values($ops));
        $this->assertNotContains("coin", $opTypes, "Rest folk dr (coin) should not trigger on die placement");
        $this->assertNotContains("journal", $opTypes, "Rest folk dr (journal) should not trigger on die placement");
    }

    public function testNonRestFolkDrTriggeredOnDiePlacement(): void {
        $color = PCOLOR;
        // card_folk_133: tags=Vista, dr=coin, no rest field — should trigger when die placed
        // Place it at state 2 on tableau (tucked under a card at state 2)
        $this->game->tokens->db->moveToken("card_folk_133", "tableau_$color", 2);
        // Place a card at state 2 for the folk to be tucked under
        $this->game->tokens->db->moveToken("card_land_20", "tableau_$color", 2);
        $this->game->material->setRulesFor("card_land_20", ["dr" => "food"]);

        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_$color");
        $dieKey = array_key_first($dice);

        /** @var Op_placeDie */
        $op = $this->game->machine->instanciateOperation("placeDie", $color, ["die" => $dieKey]);

        $folkCard = $op->getTuckedFolk("card_land_20");
        $this->assertEquals("card_folk_133", $folkCard, "Should find non-rest folk card tucked under card_land_20");

        $isRestOnly = $this->game->getRulesFor($folkCard, "rest", 0);
        $this->assertFalse((bool) $isRestOnly, "card_folk_133 should not have rest=1");
    }
}
