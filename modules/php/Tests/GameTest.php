<?php

declare(strict_types=1);
namespace Bga\Games\wayfarers\Tests;

use Bga\Games\wayfarers\Operations\Op_gain;
use Bga\Games\wayfarers\Operations\Op_journal;
use Bga\Games\wayfarers\Operations\Op_order;
use Bga\Games\wayfarers\Operations\Op_seq;
use Bga\Games\wayfarers\States\GameDispatch;
use PHPUnit\Framework\TestCase;

use function Bga\Games\wayfarers\array_get;
use function Bga\Games\wayfarers\getPart;
use function Bga\Games\wayfarers\startsWith;

final class GameTest extends TestCase {
    private GameUT $game;
    function dispatchOneStep($done = null) {
        $game = $this->game;
        $state = $game->machine->dispatchOne();
        if ($state === null) {
            $state = GameDispatch::class;
        }
        if ($done !== null) {
            $this->assertEquals($done, $state);
        }
        $op = $this->game->machine->createTopOperationFromDbForOwner(null);
        return $op;
    }
    function dispatch($done = null) {
        $game = $this->game;
        $state = $game->machine->dispatchAll();
        if ($state === null) {
            $state = GameDispatch::class;
        }
        if ($done !== null) {
            $this->assertEquals($done, $state);
        }
        $op = $this->game->machine->createTopOperationFromDbForOwner(null);
        return $op;
    }
    function game(int $x = 0) {
        $game = new GameUT();
        $game->init($x);
        $this->game = $game;
        return $game;
    }

    protected function setUp(): void {
        $this->game();
    }
    public function testInstanciateAllOperations() {
        $this->game();
        $token_types = $this->game->material->get();
        $tested = [];
        foreach ($token_types as $key => $info) {
            $this->assertTrue(!!$key);
            if (!startsWith($key, "Op_")) {
                continue;
            }
            //echo "testing op $key\n";
            $this->subTestOp($key, $info);
            $tested[$key] = 1;
        }

        $dir = dirname(dirname(__FILE__));
        $files = glob("$dir/Operations/*.php");

        foreach ($files as $file) {
            $base = basename($file);
            $this->assertTrue(!!$base);
            if (!startsWith($base, "Op_")) {
                continue;
            }
            $mne = preg_replace("/Op_(.*).php/", "\\1", $base);
            $key = "Op_{$mne}";
            if (str_contains($key, "Base")) {
                continue;
            }
            if (array_key_exists($key, $tested)) {
                continue;
            }
            //echo "testing op $key\n";
            $this->subTestOp($key, ["type" => $mne]);
        }
    }

    function subTestOp($key, $info = []) {
        try {
            $type = array_get($info, "type", substr($key, 3));
            $this->assertTrue(!!$type, "empty type for $key");

            /** @var Operation */
            $op = $this->game->machine->instanciateOperation($type, PCOLOR);

            $args = $op->getArgs();
            $ttype = array_get($args, "ttype");
            $this->assertTrue($ttype != "", "empty ttype for $key");

            $propt = array_get($args, "prompt");
            if (isset($info["prompt"])) {
                $this->assertEquals($info["prompt"], $propt, "wrong prompt for $key");
            }

            $opName = $op->getOpName();
            $flatName = is_array($opName) ? GameUT::format_string_recursive($opName["log"], $opName["args"]) : $opName;
            $this->assertFalse(str_contains($flatName, "?"), "bad op name for $key: $flatName");
            $this->assertFalse($flatName == $op->getType(), "No name set for operation $key");
            $this->assertStringNotContainsString("wicon", $flatName, "OpName should not contain icon markup for $key: $flatName");

            return $op;
        } catch (\Exception $e) {
            $this->fail("$key: " . $e->getMessage());
        }
    }

    public function testAllDiceSpots() {
        $this->game();
        $token_types = $this->game->material->get();

        foreach ($token_types as $key => $info) {
            $this->assertTrue(!!$key);
            if (!startsWith($key, "card_")) {
                continue;
            }
            $r = $info["d"] ?? "";
            if (!$r) {
                continue;
            }

            try {
                $r = $info["dr"] ?? "";
                $this->assertTrue($r != "", "empty dr for $key");
                $this->game->machine->instanciateOperation($r, PCOLOR);
            } catch (\Exception $e) {
                $this->fail("$key dr=$r: " . $e->getMessage());
            }
        }
    }

    public function testAllInstRules() {
        $this->game();
        $token_types = $this->game->material->get();

        foreach ($token_types as $key => $info) {
            $this->assertTrue(!!$key);
            if (!startsWith($key, "card_")) {
                continue;
            }
            $r = $info["r"] ?? "";
            if (!$r) {
                continue;
            }

            try {
                $this->game->machine->instanciateOperation($r, PCOLOR);
            } catch (\Exception $e) {
                $this->fail("$key r=$r: " . $e->getMessage());
            }
        }
    }

    public function testAllInspExpr() {
        $this->game();
        $token_types = $this->game->material->get();

        foreach ($token_types as $key => $info) {
            $this->assertTrue(!!$key);
            if (!startsWith($key, "card_insp_")) {
                continue;
            }
            $r = $info["collect"] ?? "";
            $this->assertTrue($r != "", "empty collect for $key");
            try {
                $this->game->evaluateExpression($r, PCOLOR);
            } catch (\Exception $e) {
                $this->fail("$key collect=$r: " . $e->getMessage());
            }
        }
    }

    public function testAllSpaceCards() {
        $this->game();
        $token_types = $this->game->material->get();

        foreach ($token_types as $key => $info) {
            $this->assertTrue(!!$key);
            if (!startsWith($key, "card_space_")) {
                continue;
            }
            try {
                $r = $info["vpexp"] ?? "";
                $this->assertTrue($r != "", "empty vpexp for $key");
                $this->game->evaluateExpression((string) $r, PCOLOR);
                $r = $info["r"] ?? "";
                if ($r) {
                    $this->game->machine->instanciateOperation($r, PCOLOR);
                }
                $r = $info["tags"] ?? "";
                $this->assertTrue($r != "", "empty tags for $key");
            } catch (\Exception $e) {
                $this->fail("$key: " . $e->getMessage());
            }
        }
    }

    public function testFolk() {
        $this->game();
        $token_types = $this->game->material->get();

        foreach ($token_types as $key => $info) {
            $this->assertTrue(!!$key);
            if (!startsWith($key, "card_folk_")) {
                continue;
            }
            try {
                $r = $info["cost"] ?? "";
                $this->assertTrue($r != "", "empty cost for $key");

                $r = $info["dr"] ?? ($info["da"] ?? "");
                $this->assertTrue($r != "", "empty dr for $key");
                $this->game->machine->instanciateOperation($r, PCOLOR);
            } catch (\Exception $e) {
                $this->fail("$key: " . $e->getMessage());
            }
        }
    }

    function checkRules($ruleField, $key, $info, $canEmpty = false) {
        $r = $info[$ruleField] ?? "";
        if (!$canEmpty) {
            $this->assertTrue($r != "", "empty $ruleField for $key");
        } else {
            return;
        }
        try {
            $this->game->machine->instanciateOperation($r, PCOLOR);
        } catch (\Exception $e) {
            $this->fail("$key $ruleField=$r: " . $e->getMessage());
        }
        return $r;
    }

    /**
     * Test Material data for aiboard_X. Make sure all rules are implements
     */
    public function testAIBoard() {
        $this->game();
        $token_types = $this->game->material->get();

        foreach ($token_types as $key => $info) {
            $this->assertTrue(!!$key);
            if (!startsWith($key, "aiboard_")) {
                continue;
            }
            //echo "testing $key\n";
            $bnum = getPart($key, 1);
            $this->checkRules("t", $key, $info);
            $this->checkRules("r1", $key, $info);
            $this->checkRules("r2", $key, $info);
            for ($i = 0; $i < 21; $i++) {
                $this->checkRules("r", "aibonus_{$bnum}_{$i}", $token_types["aibonus_{$bnum}_{$i}"], true);
            }
        }
    }
    /**
     * Test getCaravanAssetsForDie with starting assets only (no upgrade tiles)
     */
    public function testGetCaravanAssetsForDie_StartingAssets() {
        $this->game();

        // Die value 1 should have camel (column 0)
        $assets = $this->game->getCaravanAssetsForDie(1, PCOLOR);
        $this->assertEquals(1, $assets["camel"], "Die 1 should have 1 camel");
        $this->assertEquals(0, $assets["telescope"], "Die 1 should have no telescope");
        $this->assertEquals(0, $assets["ship"], "Die 1 should have no ship");
        $this->assertEquals(0, $assets["pigeon"], "Die 1 should have no pigeon");

        // Die value 6 should have telescope (column 5)
        $assets = $this->game->getCaravanAssetsForDie(6, PCOLOR);
        $this->assertEquals(0, $assets["camel"], "Die 6 should have no camel");
        $this->assertEquals(1, $assets["telescope"], "Die 6 should have 1 telescope");
        $this->assertEquals(0, $assets["ship"], "Die 6 should have no ship");
        $this->assertEquals(0, $assets["pigeon"], "Die 6 should have no pigeon");

        // Die values 2-5 should have no starting assets
        for ($die = 2; $die <= 5; $die++) {
            $assets = $this->game->getCaravanAssetsForDie($die, PCOLOR);
            $this->assertEquals(0, $assets["camel"], "Die $die should have no camel");
            $this->assertEquals(0, $assets["telescope"], "Die $die should have no telescope");
            $this->assertEquals(0, $assets["ship"], "Die $die should have no ship");
            $this->assertEquals(0, $assets["pigeon"], "Die $die should have no pigeon");
        }
    }

    /**
     * Test getCaravanAssetsForDie with a 1x1 green upgrade tile
     */
    public function testGetCaravanAssetsForDie_With1x1Tile() {
        $this->game();

        // Place a 1x1 green camel tile (upg_green_31) at column 2 (state = 2 + 0*6 + 1 = 3)
        $this->game->tokens->db->moveToken("upg_green_31_1", "tableau_" . PCOLOR, 3);

        // Die value 3 (column 2) should now have camel from the tile
        $assets = $this->game->getCaravanAssetsForDie(3, PCOLOR);
        $this->assertEquals(1, $assets["camel"], "Die 3 should have 1 camel from green tile");

        // Die value 2 (column 1) should not have the camel
        $assets = $this->game->getCaravanAssetsForDie(2, PCOLOR);
        $this->assertEquals(0, $assets["camel"], "Die 2 should have no camel");
    }

    /**
     * Test getCaravanAssetsForDie with a 2x1 yellow upgrade tile
     * Yellow tiles: r is left column, r2 is right column
     */
    public function testGetCaravanAssetsForDie_With2x1Tile() {
        $this->game();

        // Place a 2x1 yellow tile (upg_yellow_2: r=camel, r2=diceMinus) at column 1 (state = 1 + 0*6 + 1 = 2)
        // This tile covers columns 1 and 2
        $this->game->tokens->db->moveToken("upg_yellow_2_1", "tableau_" . PCOLOR, 2);

        // Die value 2 (column 1) should have camel from r field
        $assets = $this->game->getCaravanAssetsForDie(2, PCOLOR);
        $this->assertEquals(1, $assets["camel"], "Die 2 should have 1 camel from yellow tile left side");
        $this->assertEquals(0, $assets["ship"], "Die 2 should have no ship");

        // Die value 3 (column 2) should NOT have camel (r2=diceMinus has no assets)
        $assets = $this->game->getCaravanAssetsForDie(3, PCOLOR);
        $this->assertEquals(0, $assets["camel"], "Die 3 should have no camel (right side has diceMinus)");

        // Die value 1 should still have starting camel only
        $assets = $this->game->getCaravanAssetsForDie(1, PCOLOR);
        $this->assertEquals(1, $assets["camel"], "Die 1 should have 1 starting camel");
    }

    /**
     * Test getCaravanAssetsForDie with a 2x1 blue tile that has ship on both sides
     */
    public function testGetCaravanAssetsForDie_With2x1TileShipBothSides() {
        $this->game();

        // Place blue tile upg_blue_9 (r=ship, r2=pigeon) at column 2 (state = 2 + 0*6 + 1 = 3)
        // This tile covers columns 2 and 3
        $this->game->tokens->db->moveToken("upg_blue_9_1", "tableau_" . PCOLOR, 3);

        // Die value 3 (column 2) should have ship from r field
        $assets = $this->game->getCaravanAssetsForDie(3, PCOLOR);
        $this->assertEquals(1, $assets["ship"], "Die 3 should have 1 ship from blue tile left side");
        $this->assertEquals(0, $assets["pigeon"], "Die 3 should have no pigeon");

        // Die value 4 (column 3) should have pigeon from r2 field
        $assets = $this->game->getCaravanAssetsForDie(4, PCOLOR);
        $this->assertEquals(0, $assets["ship"], "Die 4 should have no ship");
        $this->assertEquals(1, $assets["pigeon"], "Die 4 should have 1 pigeon from blue tile right side");
    }

    /**
     * Test getMissingAssetRequirements with empty requirements
     */
    public function testgetMissingAssetRequirements_EmptyRequirements() {
        $this->game();

        $missing = $this->game->getMissingAssetRequirements("", []);
        $this->assertEmpty($missing, "Empty requirements should have no missing assets");
    }

    /**
     * Test getMissingAssetRequirements with "any" requirements
     */
    public function testgetMissingAssetRequirements_AnyRequirements() {
        $this->game();

        // "any" means any die can be placed - no specific assets needed
        $missing = $this->game->getMissingAssetRequirements("any", ["camel" => 0]);
        $this->assertEmpty($missing, "'any' requirements should have no missing assets");

        $missing = $this->game->getMissingAssetRequirements("any", ["camel" => 1]);
        $this->assertEmpty($missing, "'any' requirements should have no missing assets with assets");
    }

    /**
     * Test getMissingAssetRequirements with single asset requirement
     */
    public function testgetMissingAssetRequirements_SingleAsset() {
        $this->game();

        // Has camel, requires camel - should pass
        $missing = $this->game->getMissingAssetRequirements("camel", ["camel" => 1]);
        $this->assertEmpty($missing, "Should meet camel requirement with camel");

        // No camel, requires camel - should fail
        $missing = $this->game->getMissingAssetRequirements("camel", ["camel" => 0]);
        $this->assertEquals(["camel"], $missing, "Should be missing camel");

        // Has ship, requires camel - should fail
        $missing = $this->game->getMissingAssetRequirements("camel", ["ship" => 1, "camel" => 0]);
        $this->assertEquals(["camel"], $missing, "Should be missing camel when only have ship");
    }

    /**
     * Test getMissingAssetRequirements with multiple asset requirements
     */
    public function testgetMissingAssetRequirements_MultipleAssets() {
        $this->game();

        // Requires telescope,camel - has both
        $missing = $this->game->getMissingAssetRequirements("telescope,camel", ["telescope" => 1, "camel" => 1]);
        $this->assertEmpty($missing, "Should meet telescope,camel with both assets");

        // Requires telescope,camel - has only camel
        $missing = $this->game->getMissingAssetRequirements("telescope,camel", ["camel" => 1, "telescope" => 0]);
        $this->assertEquals(["telescope"], $missing, "Should be missing telescope");

        // Requires telescope,camel - has neither
        $missing = $this->game->getMissingAssetRequirements("telescope,camel", ["camel" => 0, "telescope" => 0]);
        $this->assertCount(2, $missing, "Should be missing both assets");
    }

    /**
     * Test getMissingAssetRequirements with ship requirements
     */
    public function testgetMissingAssetRequirements_Ship() {
        $this->game();

        // Requires ship, no ship - should fail
        $missing = $this->game->getMissingAssetRequirements("ship", ["ship" => 0]);
        $this->assertEquals(["ship"], $missing, "Should be missing ship");

        // Requires ship, has ship - should pass
        $missing = $this->game->getMissingAssetRequirements("ship", ["ship" => 1]);
        $this->assertEmpty($missing, "Should meet ship requirement with ship");

        // Requires 2 ships, has 1 ship - should fail with 1 missing
        $missing = $this->game->getMissingAssetRequirements("ship,ship", ["ship" => 1]);
        $this->assertEquals(["ship"], $missing, "Should be missing 1 ship");

        // Requires 2 ships, has 0 ships - should fail with 2 missing
        $missing = $this->game->getMissingAssetRequirements("ship,ship", ["ship" => 0]);
        $this->assertCount(2, $missing, "Should be missing 2 ships");
    }

    /**
     * Bug 4: Test hasPigeonLeftover — pigeon tile on column 4, die 4 on ship-requiring card.
     * Pigeon should be available as leftover since ship requirement doesn't consume it.
     */
    public function testHasPigeonLeftover_PigeonTileNotConsumedByShipRequirement() {
        $this->game();

        // Give player 3 food
        $this->game->tokens->dbSetTokenState("tracker_food_" . PCOLOR, 3);

        // Put some water cards in the deck so deck option is available
        $this->game->tokens->db->moveToken("card_water_41_1", "deck_water", 1);
        $this->game->tokens->db->moveToken("card_water_42_1", "deck_water", 2);
        $this->game->tokens->db->moveToken("card_water_43_1", "deck_water", 3);

        // Place green pigeon tile (upg_green_34, w=1, r=pigeon) at column 4 (state = 3 + 0*6 + 1 = 4)
        $this->game->tokens->db->moveToken("upg_green_34_1", "tableau_" . PCOLOR, 4);

        // Place die with value 4 on card_home_3 (requires d=ship, r=2n_food:cardWater)
        $die = "dice_" . PCOLOR . "_1";
        $this->game->tokens->db->moveToken($die, "card_home_3_" . PCOLOR, 4);

        // Queue the full expression as Op_placeDie would: 2n_food:cardWater with die and reason
        $op = $this->game->machine->instanciateOperation("2n_food:cardWater", PCOLOR, [
            "die" => $die,
            "reason" => "card_home_3_" . PCOLOR,
        ]);
        $op->saveToDb(1, true);

        // Dispatch — should auto-resolve 2n_food (pays 2 food), then stop at cardWater for player input
        $top = $this->dispatch();
        $this->assertEquals("cardWater", $top->getType(), "Should be cardWater waiting for player input");

        // Food should be 1 after paying 2n_food
        $food = $this->game->tokens->getTrackerValue(PCOLOR, "food");
        $this->assertEquals(1, $food, "Should have 1 food after paying 2n_food");

        // Player chooses the deck
        $top->action_resolve(["target" => "deck_water"]);

        // Check that n_food was NOT queued (pigeon should cover the draw cost)
        $ops = $this->game->machine->db->getOperations();
        $opTypes = array_map(fn($o) => $o["type"], array_values($ops));
        $this->assertNotContains("n_food", $opTypes, "Pigeon leftover should skip food payment for draw");

        // Dispatch through cardDraw confirm + card selection
        $wop = $this->dispatch();
        $wop->action_resolve(["target" => "card_water_42_1"]);
        $this->dispatch();

        // Food should still be 1 — pigeon saved the draw cost
        $food = $this->game->tokens->getTrackerValue(PCOLOR, "food");
        $this->assertEquals(1, $food, "Food should be 1 — paid 2 for action, pigeon saved draw cost");
    }

    /**
     * Test hasPigeonLeftover — pigeon tile on column 4, die 4 on card requiring pigeon.
     * Pigeon should NOT be leftover since it's consumed by the requirement.
     */
    public function testHasPigeonLeftover_PigeonConsumedByRequirement() {
        $this->game();

        // Place green pigeon tile at column 4
        $this->game->tokens->db->moveToken("upg_green_34_1", "tableau_" . PCOLOR, 4);

        // Create a card that requires pigeon
        $cardKey = "card_land_test_1";
        $this->game->material->setRulesFor($cardKey, ["d" => "pigeon"]);

        $die = "dice_" . PCOLOR . "_1";
        $this->game->tokens->db->moveToken($die, $cardKey, 4);

        /** @var \Bga\Games\wayfarers\Operations\Op_cardBase */
        $op = $this->game->machine->instanciateOperation("cardLand", PCOLOR, [
            "die" => $die,
            "reason" => $cardKey,
        ]);

        $result = $op->hasPigeonLeftover();
        $this->assertFalse($result, "Pigeon should NOT be leftover — consumed by pigeon requirement");
    }

    /**
     * Test hasPigeonLeftover — no pigeon tile in caravan at all.
     */
    public function testHasPigeonLeftover_NoPigeonTile() {
        $this->game();

        $die = "dice_" . PCOLOR . "_1";
        $this->game->tokens->db->moveToken($die, "card_home_3_" . PCOLOR, 4);

        /** @var \Bga\Games\wayfarers\Operations\Op_cardBase */
        $op = $this->game->machine->instanciateOperation("cardWater", PCOLOR, [
            "die" => $die,
            "reason" => "card_home_3_" . PCOLOR,
        ]);

        $result = $op->hasPigeonLeftover();
        $this->assertFalse($result, "No pigeon tile — pigeon should not be available");
    }

    /**
     * Test isInspirationGoalAchieved returns false when collect field is empty
     */
    public function testIsInspirationGoalAchieved_EmptyCollect() {
        $this->game();

        // Create a mock card key
        $cardKey = "card_insp_test_1";

        // Mock material to return empty collect
        $this->game->material->setRulesFor($cardKey, ["collect" => "", "goal" => 5]);

        $result = $this->game->isInspirationGoalAchieved($cardKey, PCOLOR);
        $this->assertFalse($result, "Should return false when collect is empty");
    }

    /**
     * Test isInspirationGoalAchieved returns false when goal is 0
     */
    public function testIsInspirationGoalAchieved_ZeroGoal() {
        $this->game();

        $cardKey = "card_insp_test_2";
        $this->game->material->setRulesFor($cardKey, ["collect" => "tag_City", "goal" => 0]);

        $result = $this->game->isInspirationGoalAchieved($cardKey, PCOLOR);
        $this->assertFalse($result, "Should return false when goal is 0");
    }

    /**
     * Test isInspirationGoalAchieved returns false when goal is negative
     */
    public function testIsInspirationGoalAchieved_NegativeGoal() {
        $this->game();

        $cardKey = "card_insp_test_3";
        $this->game->material->setRulesFor($cardKey, ["collect" => "tag_City", "goal" => -1]);

        $result = $this->game->isInspirationGoalAchieved($cardKey, PCOLOR);
        $this->assertFalse($result, "Should return false when goal is negative");
    }

    /**
     * Test isInspirationGoalAchieved returns false when count is less than required
     */
    public function testIsInspirationGoalAchieved_CountLessThanRequired() {
        $this->game();

        $cardKey = "card_insp_test_4";
        $this->game->material->setRulesFor($cardKey, ["collect" => "tag_City", "goal" => 5]);

        // Add 2 City tags (less than 5)
        $this->game->tokens->db->moveToken("card_land_1_1", "tableau_" . PCOLOR);
        $this->game->material->setRulesFor("card_land_1_1", ["tags" => "City"]);
        $this->game->tokens->db->moveToken("card_land_2_1", "tableau_" . PCOLOR);
        $this->game->material->setRulesFor("card_land_2_1", ["tags" => "City"]);

        $result = $this->game->isInspirationGoalAchieved($cardKey, PCOLOR);
        $this->assertFalse($result, "Should return false when count (2) is less than goal (5)");
    }

    /**
     * Test isInspirationGoalAchieved returns true when count equals required
     */
    public function testIsInspirationGoalAchieved_CountEqualsRequired() {
        $this->game();

        $cardKey = "card_insp_test_5";
        $this->game->material->setRulesFor($cardKey, ["collect" => "tag_Vista", "goal" => 3]);

        // Add exactly 3 Vista tags
        for ($i = 1; $i <= 3; $i++) {
            $this->game->tokens->db->moveToken("card_land_vista_{$i}_1", "tableau_" . PCOLOR);
            $this->game->material->setRulesFor("card_land_vista_{$i}_1", ["tags" => "Vista"]);
        }

        $result = $this->game->isInspirationGoalAchieved($cardKey, PCOLOR);
        $this->assertTrue($result, "Should return true when count (3) equals goal (3)");
    }

    /**
     * Test isInspirationGoalAchieved returns true when count exceeds required
     */
    public function testIsInspirationGoalAchieved_CountExceedsRequired() {
        $this->game();

        $cardKey = "card_insp_test_6";
        $this->game->material->setRulesFor($cardKey, ["collect" => "tag_Harbour", "goal" => 2]);

        // Add 4 Harbour tags (more than 2)
        for ($i = 1; $i <= 4; $i++) {
            $this->game->tokens->db->moveToken("card_water_harbour_{$i}_1", "tableau_" . PCOLOR);
            $this->game->material->setRulesFor("card_water_harbour_{$i}_1", ["tags" => "Harbour"]);
        }

        $result = $this->game->isInspirationGoalAchieved($cardKey, PCOLOR);
        $this->assertTrue($result, "Should return true when count (4) exceeds goal (2)");
    }

    /**
     * Test isInspirationGoalAchieved with tracker expression
     */
    public function testIsInspirationGoalAchieved_TrackerExpression() {
        $this->game();

        $cardKey = "card_insp_test_7";
        $this->game->material->setRulesFor($cardKey, ["collect" => "tracker_coin", "goal" => 5]);

        // Create and set player's coin tracker to 6
        $trackerId = $this->game->tokens->getTrackerId(PCOLOR, "coin");
        $this->game->tokens->db->moveToken($trackerId, "miniboard_" . PCOLOR, 6);

        $result = $this->game->isInspirationGoalAchieved($cardKey, PCOLOR);
        $this->assertTrue($result, "Should return true when coin tracker (6) exceeds goal (5)");
    }

    /**
     * Test isInspirationGoalAchieved with card count expression
     */
    public function testIsInspirationGoalAchieved_CardExpression() {
        $this->game();

        $cardKey = "card_insp_test_8";
        $this->game->material->setRulesFor($cardKey, ["collect" => "tag_card_folk", "goal" => 3]);

        // Add 3 folk cards
        $this->game->tokens->db->moveToken("card_folk_1_1", "tableau_" . PCOLOR);
        $this->game->tokens->db->moveToken("card_folk_2_1", "tableau_" . PCOLOR);
        $this->game->tokens->db->moveToken("card_folk_3_1", "tableau_" . PCOLOR);

        $result = $this->game->isInspirationGoalAchieved($cardKey, PCOLOR);
        $this->assertTrue($result, "Should return true when folk count (3) meets goal (3)");
    }

    /**
     * Test isInspirationGoalAchieved with upgrade tile expression
     */
    public function testIsInspirationGoalAchieved_UpgradeTileExpression() {
        $this->game();

        $cardKey = "card_insp_test_9";
        $this->game->material->setRulesFor($cardKey, ["collect" => "tag_upg_green", "goal" => 2]);

        // Add 2 green upgrade tiles
        $this->game->tokens->db->moveToken("upg_green_1_1", "tableau_" . PCOLOR);
        $this->game->tokens->db->moveToken("upg_green_2_1", "tableau_" . PCOLOR);

        $result = $this->game->isInspirationGoalAchieved($cardKey, PCOLOR);
        $this->assertTrue($result, "Should return true when green tile count (2) meets goal (2)");
    }

    /**
     * Test isInspirationGoalAchieved edge case: goal is 1, count is 1
     */
    public function testIsInspirationGoalAchieved_MinimalGoal() {
        $this->game();

        $cardKey = "card_insp_test_10";
        $this->game->material->setRulesFor($cardKey, ["collect" => "tag_Water", "goal" => 1]);

        // Add exactly 1 Water tag
        $this->game->tokens->db->moveToken("card_water_1_1", "tableau_" . PCOLOR);
        $this->game->material->setRulesFor("card_water_1_1", ["tags" => "Water"]);

        $result = $this->game->isInspirationGoalAchieved($cardKey, PCOLOR);
        $this->assertTrue($result, "Should return true when count (1) meets minimal goal (1)");
    }

    /**
     * Test isInspirationGoalAchieved edge case: goal is 1, count is 1
     */
    public function testIsInspirationGoalAchieved_9() {
        $this->game();

        $cardKey = "card_insp_9";

        $this->game->tokens->db->moveToken("upg_green_1_1", "tableau_" . PCOLOR);
        $this->game->tokens->db->moveToken("upg_green_2_1", "tableau_" . PCOLOR);

        $result = $this->game->isInspirationGoalAchieved($cardKey, PCOLOR);
        $this->assertFalse($result);

        $this->game->tokens->db->moveToken("upg_black_2_1", "tableau_" . PCOLOR);

        $result = $this->game->isInspirationGoalAchieved($cardKey, PCOLOR);
        $this->assertFalse($result);

        $this->game->tokens->db->moveToken("upg_black_3_1", "tableau_" . PCOLOR);

        $result = $this->game->isInspirationGoalAchieved($cardKey, PCOLOR);
        $this->assertTrue($result);
    }

    public function testTriggeredVistaAbilities() {
        // Setup: Place a Vista card in player's tableau
        // card_land_20: trig="Stars", dr="food", tags="Vista"
        $vistaCard = "card_land_20_1";
        $this->game->tokens->db->moveToken($vistaCard, "tableau_" . PCOLOR, -2);

        // Setup: Tuck a folk card under the Vista card (same state in tableau)
        // card_folk_133: dr="coin", tags="Vista", cost=2
        $folkCard = "card_folk_133_1";
        $this->game->tokens->db->moveToken($folkCard, "tableau_" . PCOLOR, -2);

        // Test 1: getVistaTriggeredRules finds trigger when a Stars-tagged card is played
        // card_space_91 has tags="Stars" in material
        $starsCard = "card_space_91_1";
        $triggers = $this->game->getVistaTriggeredRules($starsCard, PCOLOR);
        $this->assertArrayHasKey($vistaCard, $triggers, "Vista card should trigger on Stars tag");
        $this->assertEquals("food", $triggers[$vistaCard], "Vista dr should be 'food'");

        // Test 2: Non-matching tag does NOT trigger
        // card_land_1 has tags="City" — should not match trig="Stars"
        $triggers = $this->game->getVistaTriggeredRules("card_land_1_1", PCOLOR);
        $this->assertEmpty($triggers, "City tag should not trigger Stars Vista");

        // Test 3: Implicit CardFolk tag triggers matching Vista card
        // card_land_31: trig="CardFolk", dr="diceMod,infMove"
        $folkVistaCard = "card_land_31_1";
        $this->game->tokens->db->moveToken($folkVistaCard, "tableau_" . PCOLOR, -3);
        $playedFolk = "card_folk_100_1";
        $triggers = $this->game->getVistaTriggeredRules($playedFolk, PCOLOR);
        $this->assertArrayHasKey($folkVistaCard, $triggers, "CardFolk Vista should trigger on folk card");

        // Test 4: Folk card tuck requirements do NOT count as tags for Vista triggering
        // card_land_34: trig="Harbour", dr="infYellow/infMove" — Vista that triggers on Harbour tag
        $harbourVistaCard = "card_land_34_1";
        $this->game->tokens->db->moveToken($harbourVistaCard, "tableau_" . PCOLOR, -4);
        // card_folk_116: tags="Harbour" — but this is a tuck requirement, not an actual tag
        $folkWithHarbourReq = "card_folk_116_1";
        $triggers = $this->game->getVistaTriggeredRules($folkWithHarbourReq, PCOLOR);
        $this->assertArrayNotHasKey(
            $harbourVistaCard,
            $triggers,
            "Harbour Vista should NOT trigger on folk card with Harbour tuck requirement"
        );
        // But the card_folk implicit tag should still trigger card_land_31 (trig=card_folk)
        $this->assertArrayHasKey($folkVistaCard, $triggers, "CardFolk Vista should still trigger on folk card");

        // Test 5: Vista card does not trigger itself
        $newVistaCard = "card_land_25_1"; // trig="Vista", tags="Vista"
        $this->game->tokens->db->moveToken($newVistaCard, "tableau_" . PCOLOR, -5);
        $triggers = $this->game->getVistaTriggeredRules($newVistaCard, PCOLOR);
        $this->assertArrayNotHasKey($newVistaCard, $triggers, "Vista card should not trigger itself");

        // Test 6: queueVistaTriggers queues both Vista dr and tucked folk dr
        /** @var \Bga\Games\wayfarers\Operations\Op_cardLand */
        $op = $this->game->machine->instanciateOperation("cardLand", PCOLOR);
        $op->queueVistaTriggers($starsCard);

        $ops = $this->game->machine->db->getOperations();
        $opTypes = array_map(fn($o) => $o["type"], array_values($ops));

        // Vista card_land_20 dr="food" should be queued
        $this->assertContains("food", $opTypes, "Vista card's dr (food) should be queued");
        // Tucked folk card_folk_133 dr="coin" should be queued
        $this->assertContains("coin", $opTypes, "Tucked folk card's dr (coin) should be queued");

        // Folk trigger should be queued before Vista trigger (folk first)
        $folkIdx = array_search("coin", $opTypes);
        $vistaIdx = array_search("food", $opTypes);
        $this->assertLessThan($vistaIdx, $folkIdx, "Folk card trigger should be queued before Vista trigger");
    }

    /**
     * Test countVpForSpaceCard with simple tag count: tag_City
     * card_space_92: vpexp="tag_City" — 1 VP per City tag
     */
    public function testCountVpForSpaceCard_TagCount() {
        // No City tags → 0 VP
        $vp = $this->game->countVpForSpaceCard("card_space_92", PCOLOR);
        $this->assertEquals(0, $vp);

        // Add 3 City-tagged cards (card_land_1..3 have tags="City")
        $this->game->tokens->db->moveToken("card_land_1_1", "tableau_" . PCOLOR);
        $this->game->tokens->db->moveToken("card_land_2_1", "tableau_" . PCOLOR);
        $this->game->tokens->db->moveToken("card_land_3_1", "tableau_" . PCOLOR);

        $vp = $this->game->countVpForSpaceCard("card_space_92", PCOLOR);
        $this->assertEquals(3, $vp);
    }

    /**
     * Test countVpForSpaceCard with constant + tag: 1+tag_Planet
     * card_space_86: vpexp="1+tag_Planet" — 1 VP + 1 VP per Planet tag
     */
    public function testCountVpForSpaceCard_ConstPlusTag() {
        // No Planet tags → 1 VP (constant only)
        $vp = $this->game->countVpForSpaceCard("card_space_86", PCOLOR);
        $this->assertEquals(1, $vp);

        // Place the card itself (has Planet tag) + another Planet card
        $this->game->tokens->db->moveToken("card_space_86_1", "tableau_" . PCOLOR);
        $this->game->tokens->db->moveToken("card_space_87_1", "tableau_" . PCOLOR);

        // 1 + 2 Planet tags = 3 VP
        $vp = $this->game->countVpForSpaceCard("card_space_86", PCOLOR);
        $this->assertEquals(3, $vp);
    }

    /**
     * Test countVpForSpaceCard with min function: min(tag_Planet,tag_Comet,tag_Stars)*3
     * card_space_91: vpexp="min(tag_Planet,tag_Comet,tag_Stars)*3" — 3 VP per complete set
     */
    public function testCountVpForSpaceCard_MinSets() {
        // No tags → 0 VP
        $vp = $this->game->countVpForSpaceCard("card_space_91", PCOLOR);
        $this->assertEquals(0, $vp);

        // 2 Planet, 1 Comet, 3 Stars → min(2,1,3) * 3 = 3 VP
        $this->game->tokens->db->moveToken("card_space_86_1", "tableau_" . PCOLOR); // Planet
        $this->game->tokens->db->moveToken("card_space_87_1", "tableau_" . PCOLOR); // Planet
        $this->game->tokens->db->moveToken("card_space_77_1", "tableau_" . PCOLOR); // Comet
        $this->game->tokens->db->moveToken("card_space_91_1", "tableau_" . PCOLOR); // Stars
        $this->game->tokens->db->moveToken("card_space_92_1", "tableau_" . PCOLOR); // Stars
        $this->game->tokens->db->moveToken("card_space_93_1", "tableau_" . PCOLOR); // Stars

        $vp = $this->game->countVpForSpaceCard("card_space_91", PCOLOR);
        $this->assertEquals(3, $vp);
    }

    /**
     * Test countVpForSpaceCard with win condition: win_Comet?4:3
     * card_space_77: vpexp="win_Comet?4:3" — 4 VP if more Comet tags than all opponents, else 3
     */
    public function testCountVpForSpaceCard_WinCondition() {
        // No Comet tags for either player → tied at 0, so lose condition → 3 VP
        $vp = $this->game->countVpForSpaceCard("card_space_77", PCOLOR);
        $this->assertEquals(3, $vp);

        // Give PCOLOR 2 Comets, opponent 0 → win → 4 VP
        $this->game->tokens->db->moveToken("card_space_77_1", "tableau_" . PCOLOR); // Comet
        $this->game->tokens->db->moveToken("card_space_78_1", "tableau_" . PCOLOR); // Comet

        $vp = $this->game->countVpForSpaceCard("card_space_77", PCOLOR);
        $this->assertEquals(4, $vp);

        // Give opponent 2 Comets too (need 2 separate cards — duplicate tags on one card count as 1)
        $this->game->tokens->db->moveToken("card_space_79_1", "tableau_" . BCOLOR); // Comet
        $this->game->tokens->db->moveToken("card_space_80_1", "tableau_" . BCOLOR); // Comet
        // Tied at 2 each → lose → 3 VP
        $vp = $this->game->countVpForSpaceCard("card_space_77", PCOLOR);
        $this->assertEquals(3, $vp);
    }

    /**
     * Test countVpForSpaceCard with tag existence ternary: tag_Sun?7:3
     * card_space_85 (Moon): vpexp="tag_Sun?7:3" — 7 VP if Sun tag present, else 3
     */
    public function testCountVpForSpaceCard_TagTernary() {
        // No Sun tag → 3 VP
        $vp = $this->game->countVpForSpaceCard("card_space_85", PCOLOR);
        $this->assertEquals(3, $vp);

        // Place a Sun card (card_space_112 has tags="Sun")
        $this->game->tokens->db->moveToken("card_space_112_1", "tableau_" . PCOLOR);

        // Has Sun tag → 7 VP
        $vp = $this->game->countVpForSpaceCard("card_space_85", PCOLOR);
        $this->assertEquals(7, $vp);
    }

    /**
     * Test countVpForSpaceCard with influence: 1+(inf_black/2)
     * card_space_97: vpexp="1+(inf_black/2)" — 1 VP + 1 VP per 2 black influence
     */
    public function testCountVpForSpaceCard_Influence() {
        // No influence → 1 VP
        $vp = $this->game->countVpForSpaceCard("card_space_97", PCOLOR);
        $this->assertEquals(1, $vp);

        // Place 5 influence tokens in black guild
        for ($i = 1; $i <= 5; $i++) {
            $this->game->tokens->db->moveToken("influence_" . PCOLOR . "_{$i}", "guild_black");
        }

        // 1 + (5/2) = 1 + 2 = 3 VP (integer division)
        $vp = $this->game->countVpForSpaceCard("card_space_97", PCOLOR);
        $this->assertEquals(3, $vp);
    }

    /**
     * Test countVpForSpaceCard with upgrade tile count: 1+upg_green
     * card_space_108: vpexp="1+upg_green" — 1 VP + 1 VP per Basic upgrade tile
     */
    public function testCountVpForSpaceCard_UpgradeTiles() {
        // No upgrade tiles → 1 VP
        $vp = $this->game->countVpForSpaceCard("card_space_108", PCOLOR);
        $this->assertEquals(1, $vp);

        // Place 3 green upgrade tiles
        $this->game->tokens->db->moveToken("upg_green_31_1", "tableau_" . PCOLOR);
        $this->game->tokens->db->moveToken("upg_green_32_1", "tableau_" . PCOLOR);
        $this->game->tokens->db->moveToken("upg_green_33_1", "tableau_" . PCOLOR);

        // 1 + 3 = 4 VP
        $vp = $this->game->countVpForSpaceCard("card_space_108", PCOLOR);
        $this->assertEquals(4, $vp);
    }

    /**
     * Automa Comet tag uses comet tracker value, not card tags
     */
    public function testCountPlayerTags_AutomaCometUsesTracker(): void {
        $this->game->setPlayersNumber(1);
        // Create tracker, starts at 0 → Comet count = 0
        $this->game->tokens->db->moveToken("tracker_comet_" . ACOLOR, "tableau_" . ACOLOR, 0);
        $count = $this->game->countPlayerTags("Comet", ACOLOR);
        $this->assertEquals(0, $count, "Automa Comet should be 0 when tracker is at 0");

        // Advance tracker to 5
        $this->game->tokens->db->setTokenState("tracker_comet_" . ACOLOR, 5);
        $count = $this->game->countPlayerTags("Comet", ACOLOR);
        $this->assertEquals(5, $count, "Automa Comet should equal comet tracker value");
    }

    /**
     * Automa regular tag (non-Comet) counts tags from cards in tableau
     */
    public function testCountPlayerTags_AutomaRegularTagFromCards(): void {
        $this->game->setPlayersNumber(1);

        // Place an additional Stars-tagged card in automa tableau (card_space_91 has tags="Stars")
        $this->game->tokens->db->moveToken("card_space_91_1", "tableau_" . ACOLOR);

        $count = $this->game->countPlayerTags("Stars", ACOLOR);
        $this->assertEquals(0, $count, "Automa should not count Stars tag");
    }

    /**
     * Automa counts tags from upgrade tiles in its tableau
     */
    public function testCountPlayerTags_TagFromUpgradeTile(): void {
        // Place an upgrade tile with a known tag in automa tableau
        // upg_black_1 has tags="City" based on material
        $this->game->tokens->db->moveToken("upg_black_1_1", "tableau_" . BCOLOR);
        $this->game->material->setRulesFor("upg_black_1_1", ["tags" => "City"]);

        $count = $this->game->countPlayerTags("City", BCOLOR);
        $this->assertGreaterThanOrEqual(1, $count, "Player should count City tag from upgrade tile");
    }

    /**
     * Automa Comet ignores any Comet-tagged cards; only uses tracker
     */
    public function testCountPlayerTags_AutomaCometIgnoresCards(): void {
        $this->game->setPlayersNumber(1);
        // Place a card with Comet tag in automa tableau (card_space_77 has tags="Comet")
        $this->game->tokens->db->moveToken("card_space_77_1", "tableau_" . ACOLOR);

        // Tracker still at 0 → result should be 0 (card tags are ignored for Comet)
        $count = $this->game->countPlayerTags("Comet", ACOLOR);
        $this->assertEquals(0, $count, "Automa Comet count should use tracker, not card tags");
    }

    /**
     * Test that action_whatever resolves an operation by picking a target and setting userArgs.
     * This is the zombie turn handler — it must call action_resolve (not resolve directly)
     * so that userArgs is set before getCheckedArg is called.
     */
    public function testActionWhatever_ResolvesWithTarget(): void {
        $this->game();
        $this->game->tokens->createTokens();

        // Instantiate a coin gain operation directly (bypass dispatch which auto-resolves single-choice ops)
        $op = $this->game->machine->instanciateOperation("coin", PCOLOR);

        // Get initial coin value
        $trackerId = $this->game->tokens->getTrackerId(PCOLOR, "coin");
        $before = (int) $this->game->tokens->db->getTokenState($trackerId);

        // Call action_whatever — simulates zombie turn
        $op->action_whatever();

        // Coin should have increased by 1
        $after = (int) $this->game->tokens->db->getTokenState($trackerId);
        $this->assertEquals($before + 1, $after, "Coin tracker should increase by 1 after action_whatever");
    }

    public function testCreateTokens_HomeSpaceCard() {
        $game = $this->game;
        $game->tokens->createTokens();

        // card_space_1 should be created for each player color
        foreach ($game->getPlayerColors() as $color) {
            $tokenId = "card_space_1_$color";
            $tokenInfo = $game->tokens->db->getTokenInfo($tokenId);
            $this->assertNotNull($tokenInfo, "Token $tokenId should exist after createTokens");
            $this->assertEquals("tableau_$color", $tokenInfo["location"], "Token $tokenId should be in tableau_$color");
        }
    }

    public function testGetAllDatas_HomeSpaceCard() {
        $game = $this->game;
        $game->tokens->createTokens();

        $allDatas = $game->getAllDatas();
        $tokens = $allDatas["tokens"];

        $color = PCOLOR;
        $tokenId = "card_space_1_$color";
        $this->assertArrayHasKey($tokenId, $tokens, "Token $tokenId should be in getAllDatas tokens");
    }

    public function testIsTrivial_gain() {
        $op = $this->game->machine->instanciateOperation("coin", PCOLOR);
        $this->assertTrue($op->isTrivial(), "Op_gain (coin) should be trivial");
    }

    public function testIsTrivial_seq_allTrivial() {
        $op = $this->game->machine->instanciateOperation("coin,food", PCOLOR);
        $this->assertTrue($op->isTrivial(), "Seq of trivial ops should be trivial");
    }

    public function testIsTrivial_seq_withNonTrivial() {
        $op = $this->game->machine->instanciateOperation("coin,cardFolk", PCOLOR);
        $this->assertFalse($op->isTrivial(), "Seq with non-trivial op should not be trivial");
    }

    public function testIsTrivial_or_singleOption() {
        $op = $this->game->machine->instanciateOperation("coin/coin", PCOLOR);
        // Both are trivial and identical, but it's still a choice between two non-void options
        $this->assertFalse($op->isTrivial(), "Or with multiple non-void options should not be trivial");
    }

    public function testIsTrivial_or_allVoid() {
        // Both options are void (no cards in mainarea and no deck), so there's no real choice
        $op = $this->game->machine->instanciateOperation("cardLand/cardSpace", PCOLOR);
        $this->assertTrue($op->isTrivial(), "Or with all void options should be trivial");
    }

    public function testGetIconicName_seq() {
        $op = $this->game->machine->instanciateOperation("n_infYellow,n_infBlue", PCOLOR);
        $name = $op->getIconicName();
        $this->assertIsString($name, "Op_seq::getIconicName should return a string");
        $this->assertStringContainsString("[wicon_inf_yellow_pay]", $name);
        $this->assertStringContainsString("[wicon_inf_blue_pay]", $name);
    }

    public function testGetIconicName_or() {
        $op = $this->game->machine->instanciateOperation("n_infYellow/n_infBlue", PCOLOR);
        $name = $op->getIconicName();
        $this->assertIsString($name, "Op_or::getIconicName should return a string");
        $this->assertStringContainsString("[wicon_inf_yellow_pay]", $name);
        $this->assertStringContainsString(" / ", $name);
        $this->assertStringContainsString("[wicon_inf_blue_pay]", $name);
    }

    public function testRestQueuesRestAbilities() {
        $this->game->tokens->createTokens();

        // Move all dice out of supply so isGoodRest() returns true (0-1 dice in supply)
        $dice = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_" . PCOLOR);
        foreach (array_keys($dice) as $dieKey) {
            $this->game->tokens->db->moveToken($dieKey, "limbo");
        }

        $this->game->machine->queue("rest", PCOLOR);

        // Dispatch to auto-resolve and verify coin gained exactly once
        $coinBefore = $this->game->tokens->getTrackerValue(PCOLOR, "coin");
        $top = $this->dispatchOneStep();
        $this->assertTrue($top instanceof Op_order, $top::class);
        $top = $this->dispatchOneStep();
        $this->assertTrue($top instanceof Op_seq, $top::class);
        $top = $this->dispatchOneStep();
        $this->assertTrue($top instanceof Op_gain, $top::class);
        $top = $this->dispatchOneStep();
        $this->assertTrue($top instanceof Op_journal, $top::class);
        $coinAfter = $this->game->tokens->getTrackerValue(PCOLOR, "coin");
        $this->assertEquals($coinBefore + 1, $coinAfter, "Rest ability should gain coin exactly once");
    }

    public function testEvaluateExpression_FolkCountDefaultTableau() {
        $this->game->tokens->createTokens();
        $count = $this->game->evaluateExpression("tag_card_folk", PCOLOR);
        $this->assertEquals(1, $count, "Default tableau should have 1 folk card (Capital Townsfolk)");
    }

    private function assertLastNotifResolved(string $context = "") {
        $notifications = $this->game->notify->_getNotifications();
        $this->assertNotEmpty($notifications, "No notification was sent" . ($context ? " ($context)" : ""));
        $notif = end($notifications);
        $log = $notif['log'];
        if (!$log) return; // empty log = suppressed notification
        $args = $notif['args'];
        // Check that all ${...} placeholders in the log have corresponding keys in args
        preg_match_all('/\$\{(\w+)\}/', $log, $matches);
        foreach ($matches[1] as $key) {
            $this->assertArrayHasKey($key, $args, "Notification missing key '$key' in log: $log" . ($context ? " ($context)" : ""));
        }
    }

    public function testDbSetTokenLocation_defaultNotif() {
        $this->game->tokens->createTokens();
        $this->game->tokens->dbSetTokenLocation("influence_" . PCOLOR . "_1", "guild_blue", 0, "*", [], PCOLOR_ID);
        $this->assertLastNotifResolved("default notif *");
    }

    public function testDbSetTokenLocation_customNotif_tokenName() {
        $this->game->tokens->createTokens();
        $this->game->tokens->dbSetTokenLocation(
            "influence_" . PCOLOR . "_1",
            "guild_blue",
            0,
            clienttranslate('${player_name} moves ${token_name} to ${place_name}'),
            [],
            PCOLOR_ID
        );
        $this->assertLastNotifResolved("token_name + place_name");
    }

    public function testDbSetTokenLocation_customNotif_tokenIcon() {
        $this->game->tokens->createTokens();
        $this->game->tokens->dbSetTokenLocation(
            "influence_" . PCOLOR . "_1",
            "guild_blue",
            0,
            clienttranslate('${player_name} gains ${token_icon}'),
            ["token_icon" => "wicon_inf_blue"],
            PCOLOR_ID
        );
        $this->assertLastNotifResolved("token_icon");
    }

    public function testDbSetTokenLocation_customNotif_placeFromName() {
        $this->game->tokens->createTokens();
        // First move to a known location
        $this->game->tokens->db->moveToken("influence_" . PCOLOR . "_1", "guild_blue");
        $this->game->tokens->dbSetTokenLocation(
            "influence_" . PCOLOR . "_1",
            "guild_yellow",
            0,
            clienttranslate('${player_name} moves ${token_name} from ${place_from_name} to ${place_name}'),
            [],
            PCOLOR_ID
        );
        $this->assertLastNotifResolved("place_from_name");
    }

    public function testDbSetTokenLocation_emptyNotif() {
        $this->game->tokens->createTokens();
        $this->game->tokens->dbSetTokenLocation("influence_" . PCOLOR . "_1", "guild_blue", 0, "", [], PCOLOR_ID);
        // Empty notification should not crash
        $this->assertLastNotifResolved("empty notif");
    }

    // --- Turn stats tests ---

    private function setupTurnOp(string $owner = PCOLOR): \Bga\Games\wayfarers\OpCommon\Operation {
        $op = $this->game->machine->instanciateOperation("turn", $owner);
        $op->saveToDb(1, true);
        return $this->dispatch();
    }

    public function testTurnStats_DiceAction() {
        $this->game->tokens->createTokens();
        $playerId = $this->game->custom_getPlayerIdByColor(PCOLOR);

        $op = $this->setupTurnOp();
        $this->game->fakeUserAction($op, "dice_" . PCOLOR . "_1");

        $this->assertEquals(1, $this->game->playerStats->get("game_turns", $playerId));
        $this->assertEquals(1, $this->game->playerStats->get("game_dice_actions", $playerId));
        $this->assertEquals(0, $this->game->playerStats->get("game_rest_actions", $playerId));
        $this->assertEquals(0, $this->game->playerStats->get("game_worker_actions", $playerId));
    }

    public function testTurnStats_RestAction() {
        $this->game->tokens->createTokens();
        $playerId = $this->game->custom_getPlayerIdByColor(PCOLOR);

        $op = $this->setupTurnOp();
        $this->game->fakeUserAction($op, "rest");

        $this->assertEquals(1, $this->game->playerStats->get("game_turns", $playerId));
        $this->assertEquals(0, $this->game->playerStats->get("game_dice_actions", $playerId));
        $this->assertEquals(1, $this->game->playerStats->get("game_rest_actions", $playerId));
        $this->assertEquals(0, $this->game->playerStats->get("game_worker_actions", $playerId));
    }

    public function testTurnStats_WorkerAction() {
        $this->game->tokens->createTokens();
        $playerId = $this->game->custom_getPlayerIdByColor(PCOLOR);

        // Place a worker in the player's supply so it's a valid selection
        $this->game->tokens->db->moveToken("worker_blue_1", "tableau_" . PCOLOR);

        $op = $this->setupTurnOp();
        $this->game->fakeUserAction($op, "worker_blue_1");

        $this->assertEquals(1, $this->game->playerStats->get("game_turns", $playerId));
        $this->assertEquals(0, $this->game->playerStats->get("game_dice_actions", $playerId));
        $this->assertEquals(0, $this->game->playerStats->get("game_rest_actions", $playerId));
        $this->assertEquals(1, $this->game->playerStats->get("game_worker_actions", $playerId));
    }

    public function testTurnStats_AutomaNoStats() {
        $this->game(1); // solo mode
        $this->game->tokens->createTokens();

        // Automa turn: auto() should route to ai_turn and never call resolve()
        $op = $this->game->machine->instanciateOperation("turn", ACOLOR);
        $autoResult = $op->auto();
        $this->assertTrue($autoResult, "Automa turn should auto-resolve");

        // Automa player ID should have no stats incremented
        $automaId = \Bga\Games\wayfarers\Game::PLAYER_AUTOMA;
        $this->assertEquals(0, $this->game->playerStats->get("game_turns", $automaId));
        $this->assertEquals(0, $this->game->playerStats->get("game_dice_actions", $automaId));
        $this->assertEquals(0, $this->game->playerStats->get("game_rest_actions", $automaId));
        $this->assertEquals(0, $this->game->playerStats->get("game_worker_actions", $automaId));
    }
}
