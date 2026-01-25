<?php

declare(strict_types=1);
namespace Bga\Games\wayfarers\Tests;

use Bga\GameFramework\NotificationMessage;
use Bga\GameFramework\Notify;
use Bga\Games\wayfarers\OpCommon\OpExpression;
use Bga\Games\wayfarers\Game;
use Bga\Games\wayfarers\OpCommon\Operation;
use Bga\Games\wayfarers\Common\PGameTokens;
use Bga\Games\wayfarers\OpCommon\ComplexOperation;
use Bga\Games\wayfarers\Operations\Op_or;
use Bga\Games\wayfarers\Operations\Op_paygain;
use Bga\Games\wayfarers\Operations\Op_seq;
use Bga\Games\wayfarers\Operations\Op_cotag;
use Bga\Games\wayfarers\Operations\Op_craft;
use Bga\Games\wayfarers\Operations\Op_pay;
use Bga\Games\wayfarers\OpCommon\OpMachine;
use Bga\Games\wayfarers\Operations\Op_barrier;
use Bga\Games\wayfarers\Operations\Op_furnish;
use Bga\Games\wayfarers\Operations\Op_furnishPay;
use Bga\Games\wayfarers\Operations\Op_task;
use Bga\Games\wayfarers\Operations\Op_turn;
use Bga\Games\wayfarers\Operations\Op_turnall;
use Bga\Games\wayfarers\Operations\Op_turnpick;
use Bga\Games\wayfarers\StateConstants;
use Bga\Games\wayfarers\States\GameDispatch;
use Bga\Games\wayfarers\States\GameDispatchForced;
use Bga\Games\wayfarers\States\MachineHalted;
use Bga\Games\wayfarers\States\MultiPlayerMaster;
use Bga\Games\wayfarers\States\PlayerTurn;
use Bga\Games\wayfarers\Tests\MachineInMem;
use Bga\Games\wayfarers\Tests\TokensInMem;
use PHPUnit\Framework\TestCase;

use function Bga\Games\wayfarers\array_get;
use function Bga\Games\wayfarers\startsWith;

//    'player_colors' => ["ef58a2", "a0d28c", "6cd0f6", "ffcc02"],
define("PCOLOR", "a0d28c");
define("BCOLOR", "6cd0f6");
define("ACOLOR", "ffcc02");

class FakeNotify extends Notify {
    public function all(string $notifName, string|NotificationMessage $message = "", array $args = []): void {
        //echo "Notify all: $notifName : $message\n";
    }
    public function player(int $playerId, string $notifName, string|NotificationMessage $message = "", array $args = []): void {
        //echo "Notify player $playerId: $notifName : $message\n";
    }
}

class GameUT extends Game {
    var $multimachine;
    var $xtable;
    var $gameap_number = 0;
    var $var_colonies = 0;
    var $_colors = [];

    function __construct() {
        parent::__construct();
        //$this->gamestate = new GameStateInMem();

        //$this->tokens = new TokensInMem($this);
        $this->xtable = [];
        $this->machine = new OpMachine(new MachineInMem($this, $this->xtable));
        $this->curid = 1;
        $this->_colors = [PCOLOR, BCOLOR];
        $this->notify = new FakeNotify();

        $tokens = new TokensInMem($this);
        $this->tokens = new PGameTokens($this, $tokens);
    }

    public function _($s): string {
        return $s;
    }

    function getPlayersNumber(): int {
        return count($this->_colors);
    }

    function setPlayersNumber(int $num) {
        switch ($num) {
            case 2:
                $this->_colors = [PCOLOR, BCOLOR];
                break;
            case 3:
                $this->_colors = [PCOLOR, BCOLOR, ACOLOR];
                break;
            case 4:
                $this->_colors = [PCOLOR, BCOLOR, ACOLOR, "ef58a2"];
                break;
            default:
                throw new BgaVisibleSystemException("Invalid number of players");
        }
    }

    function getUserPreference(int $player_id, int $code) {
        return 0;
    }
    function getAutomaColor() {
        return ACOLOR;
    }

    function init(int $x = 0) {
        //$this->adjustedMaterial(true);
        //$this->createTokens();
        $this->gamestate->changeActivePlayer(10);
        $this->gamestate->jumpToState(StateConstants::STATE_GAME_DISPATCH);
        return $this;
    }

    function clean_cache() {}

    function getMultiMachine() {
        return $this->multimachine;
    }

    public $curid;

    public function getCurrentPlayerId($bReturnNullIfNotLogged = false): string|int {
        return $this->curid;
    }

    public function getCurrentPlayerColor(): string {
        return $this->getPlayerColorById($this->curid);
    }

    function _getColors() {
        return $this->_colors;
    }

    function fakeUserAction(Operation $op, $target = null) {
        return $op->action_resolve([Operation::ARG_TARGET => $target]);
    }

    // override/stub methods here that access db and stuff
}

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
            echo "testing op $key\n";
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
            echo "testing op $key\n";
            $this->subTestOp($key, ["type" => $mne]);
        }
    }

    function subTestOp($key, $info = []) {
        $type = array_get($info, "type", substr($key, 3));
        $this->assertTrue(!!$type);

        /** @var Operation */
        $op = $this->game->machine->instanciateOperation($type, PCOLOR);

        $args = $op->getArgs();
        $ttype = array_get($args, "ttype");
        $this->assertTrue($ttype != "", "empty ttype for $key");

        $propt = array_get($args, "prompt");
        if (isset($info["prompt"])) {
            $this->assertEquals($info["prompt"], $propt, $type);
        }

        $this->assertFalse(str_contains($op->getOpName(), "?"), $op->getOpName());
        $this->assertFalse($op->getOpName() == $op->getType(), "No name set for operation $key");
        return $op;
    }

    public function testAllDiceSpots() {
        $this->game();
        $token_types = $this->game->material->get();

        foreach ($token_types as $key => $info) {
            $this->assertTrue(!!$key);
            if (!startsWith($key, "card_")) {
                continue;
            }
            echo "testing dspot $key\n";
            $r = $info["d"] ?? "";
            if (!$r) {
                continue;
            }

            //$this->game->machine->instanciateOperation($r, PCOLOR);
            $r = $info["dr"] ?? "";
            $this->assertTrue($r != "", "empty dr for $key");
            $this->game->machine->instanciateOperation($r, PCOLOR);
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
            echo "testing insta $key\n";
            $r = $info["r"] ?? "";
            if (!$r) {
                continue;
            }

            $this->game->machine->instanciateOperation($r, PCOLOR);
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
            echo "testing insp card $key\n";
            $r = $info["collect"] ?? "";
            $this->assertTrue($r != "", "empty collect for $key");
            echo "testing insp card expr $r\n";
            $this->game->evaluateExpression($r, PCOLOR);
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
            echo "testing card $key\n";
            $r = $info["vpexp"] ?? "";
            $this->assertTrue($r != "", "empty vpexp for $key");
            $this->game->evaluateExpression((string) $r, PCOLOR);
            $r = $info["r"] ?? "";
            if ($r) {
                $this->game->machine->instanciateOperation($r, PCOLOR);
            }
            $r = $info["tags"] ?? "";
            $this->assertTrue($r != "", "empty tags for $key");
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
            echo "testing $key\n";
            $r = $info["cost"] ?? "";
            $this->assertTrue($r != "", "empty cost for $key");

            $r = $info["dr"] ?? ($info["da"] ?? "");
            $this->assertTrue($r != "", "empty dr for $key");
            $this->game->machine->instanciateOperation($r, PCOLOR);
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

        // Set player's coin tracker to 6
        $trackerId = $this->game->tokens->getTrackerId(PCOLOR, "coin");
        $this->game->tokens->db->setTokenState($trackerId, 6);

        $result = $this->game->isInspirationGoalAchieved($cardKey, PCOLOR);
        $this->assertTrue($result, "Should return true when coin tracker (6) exceeds goal (5)");
    }

    /**
     * Test isInspirationGoalAchieved with card count expression
     */
    public function testIsInspirationGoalAchieved_CardExpression() {
        $this->game();

        $cardKey = "card_insp_test_8";
        $this->game->material->setRulesFor($cardKey, ["collect" => "card_folk", "goal" => 3]);

        // Add 2 folk cards (plus 1 pre-printed = 3 total)
        $this->game->tokens->db->moveToken("card_folk_1_1", "tableau_" . PCOLOR);
        $this->game->tokens->db->moveToken("card_folk_2_1", "tableau_" . PCOLOR);

        $result = $this->game->isInspirationGoalAchieved($cardKey, PCOLOR);
        $this->assertTrue($result, "Should return true when folk count (3 with pre-printed) meets goal (3)");
    }

    /**
     * Test isInspirationGoalAchieved with upgrade tile expression
     */
    public function testIsInspirationGoalAchieved_UpgradeTileExpression() {
        $this->game();

        $cardKey = "card_insp_test_9";
        $this->game->material->setRulesFor($cardKey, ["collect" => "upg_green", "goal" => 2]);

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
}
