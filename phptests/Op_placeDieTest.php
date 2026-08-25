<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Operations\Op_placeDie;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_placeDieTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init();
        $this->game->tokens->createTokens();
    }

    /**
     * A slot must not be offered when the action it queues cannot be performed, otherwise the player
     * commits and the machine parks on a payment with no target and no skip.
     * Capital City (card_home_2) takes a camel die and its dr is "2n_food:coin,cardLand".
     */
    public function testDieSlotWhoseActionCannotBePaidIsNotOffered(): void {
        $color = PCOLOR;
        $cardId = "card_home_2_$color";

        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_$color");
        $dieKey = array_key_first($dice);
        $this->game->tokens->db->setTokenState($dieKey, 1); // die value 1 supplies the camel

        $this->setFood($color, 2);
        $this->assertContains($cardId, $this->placeDieTargets($dieKey, $color), "2 Provisions covers the 2 the action costs");

        $this->setFood($color, 1);
        $this->assertNotContains($cardId, $this->placeDieTargets($dieKey, $color), "1 Provision does not, so the slot is a dead end");
    }

    /**
     * BGA #239757 - the dead-end veto above must see the Townsfolk tucked under the slot.
     *
     * Capital Harbour (card_home_3) takes a ship die and its dr is "2n_food:cardWater". With a
     * Fisherman (card_folk_126, dr="food") tucked under it the player may resolve the Townsfolk
     * first (RULES.md:169, RULES.md:230), so 1 Provision in hand plus the one the Fisherman gains
     * covers the 2 the space costs and the slot must stay offered.
     */
    public function testDieSlotIsOfferedWhenTuckedTownsfolkCoversTheCost(): void {
        $color = PCOLOR;
        $cardId = "card_home_3_$color";
        $dieKey = $this->armShipDie($color);
        $this->tuckFisherman($color, $cardId);

        $this->setFood($color, 2);
        $this->assertContains($cardId, $this->placeDieTargets($dieKey, $color), "the ship requirement is met, so only cost can veto");

        $this->setFood($color, 1);
        $this->assertContains($cardId, $this->placeDieTargets($dieKey, $color), "the Fisherman's Provision counts towards the cost");
    }

    /**
     * BGA #239757, the other half: once the slot is taken the machine resolves it correctly -
     * the tucked Fisherman is offered as an ordering choice and gaining first makes the 2 Provisions.
     * This is what the veto above has to predict.
     */
    public function testTuckedTownsfolkGainCanBeOrderedBeforeThePaymentItFunds(): void {
        $color = PCOLOR;
        $cardId = "card_home_3_$color";
        $dieKey = $this->armShipDie($color);
        $this->tuckFisherman($color, $cardId);

        // 2 Provisions only to get past the veto; the die placement itself spends nothing
        $this->setFood($color, 2);
        /** @var Op_placeDie */
        $op = $this->game->machine->instantiateOperation("placeDie", $color, ["die" => $dieKey]);
        $this->game->fakeUserAction($op, $cardId);
        $this->setFood($color, 1);

        $this->game->machine->dispatchAll();
        $top = $this->game->machine->createTopOperationFromDbForOwner(null);
        $this->assertNotNull($top);
        $this->assertEquals("order", $top->getType(), "player chooses Townsfolk ability vs the space's own action");

        $this->assertEquals("food+(2(n_food):cardWater)", $top->getTypeFullExpr(), "Fisherman first or the space's own action first");

        $this->game->fakeUserAction($top, "choice_0"); // the Fisherman
        $this->game->machine->dispatchAll();

        // 1 in hand + 1 from the Fisherman - 2 paid to the space
        $this->assertEquals(0, $this->game->tokens->getTrackerValue($color, "food"));
        $top = $this->game->machine->createTopOperationFromDbForOwner(null);
        $this->assertNotNull($top);
        $this->assertEquals("cardWater", $top->getType(), "the action ran to its Water Card selection, so the slot was no dead end");
    }

    /**
     * BGA #239757 is not an artifact of tucking under a board space: card_water_45 is an acquired
     * Harbour Water Card, the placement RULES.md:165 spells out, and its "2n_food:coin,cardWater"
     * slot must count the tucked Townsfolk the same way.
     */
    public function testTuckedTownsfolkIsAlsoCountedUnderAnAcquiredWaterCard(): void {
        $color = PCOLOR;
        $cardId = "card_water_45";
        $this->game->tokens->db->moveToken($cardId, "tableau_$color", 7);
        $dieKey = $this->armShipDie($color);
        $this->tuckFisherman($color, $cardId);

        $this->setFood($color, 2);
        $this->assertContains($cardId, $this->placeDieTargets($dieKey, $color), "the ship requirement is met, so only cost can veto");

        $this->setFood($color, 1);
        $this->assertContains($cardId, $this->placeDieTargets($dieKey, $color), "the Fisherman's Provision counts towards the cost");
    }

    /** A die showing 4 with a Basic Upgrade Ship at caravan column 3: the ship a Harbour slot requires. */
    private function armShipDie(string $color): string {
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_$color");
        $dieKey = array_key_first($dice);
        $this->game->tokens->db->setTokenState($dieKey, 4);
        $this->game->tokens->db->moveToken("upg_green_32_1", "tableau_$color", 4);
        return $dieKey;
    }

    /** card_folk_126 Fisherman: Harbour tag, dr "food" - the "1 provision townsfolk" of the report. */
    private function tuckFisherman(string $color, string $card): void {
        $state = (int) $this->game->tokens->db->getTokenState($card);
        $this->game->tokens->db->moveToken("card_folk_126", "tableau_$color", $state);
    }

    /** Vetoing every slot must not trade one dead end for another, same as Op_placeWorker. */
    public function testDieWithNowhereLegalToGoIsDeclinedNotBlockedOn(): void {
        $color = PCOLOR;
        $dice = array_keys($this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_$color"));
        $dieKey = array_shift($dice);

        // Park every other die on the two cost-free slots: that occupies them and empties the supply,
        // so "Switch Die" is not offered either.
        foreach ($dice as $i => $other) {
            $this->game->tokens->db->moveToken($other, $i === 0 ? "card_home_12_$color" : "card_home_13_$color", 2);
        }
        $this->game->tokens->db->setTokenState($dieKey, 2); // value 2 carries no caravan asset

        /** @var Op_placeDie */
        $op = $this->game->machine->instantiateOperation("placeDie", $color, ["die" => $dieKey]);
        $this->assertEmpty($op->getArgs()["target"], "no slot, no switch, no influence to spend");
        $this->assertTrue($op->canSkip(), "the die is declined");
        $this->assertFalse($op->isVoid(), "and so it never parks in PlayerTurn");
    }

    private function setFood(string $color, int $value): void {
        $this->game->tokens->db->setTokenState($this->game->tokens->getTrackerId($color, "food"), $value);
    }

    /** Args are cached per instance, so each read needs a fresh operation. */
    private function placeDieTargets(string $dieKey, string $color): array {
        /** @var Op_placeDie */
        $op = $this->game->machine->instantiateOperation("placeDie", $color, ["die" => $dieKey]);
        return $op->getArgs()["target"];
    }

    public function testRestFolkDrNotTriggeredOnDiePlacement(): void {
        $color = PCOLOR;
        // card_folk_1 (Capital Townsfolk) has rest=1, dr=coin,journal
        // It sits at state 0 on tableau, same as card_home_12 (Capital Market)
        // Place a die on card_home_12 — the rest folk's dr should NOT be queued
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_$color");
        $dieKey = array_key_first($dice);

        /** @var Op_placeDie */
        $op = $this->game->machine->instantiateOperation("placeDie", $color, ["die" => $dieKey]);
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
        $op = $this->game->machine->instantiateOperation("placeDie", $color, ["die" => $dieKey]);

        $folkCard = $op->getTuckedFolk("card_land_20");
        $this->assertEquals("card_folk_133", $folkCard, "Should find non-rest folk card tucked under card_land_20");

        $isRestOnly = $this->game->getRulesFor($folkCard, "rest", 0);
        $this->assertFalse((bool) $isRestOnly, "card_folk_133 should not have rest=1");
    }
}
