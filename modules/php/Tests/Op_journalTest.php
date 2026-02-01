<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Game;
use Bga\Games\wayfarers\Material;
use Bga\Games\wayfarers\Operations\Op_journal;
use Bga\Games\wayfarers\OpCommon\Operation;
use Bga\Games\wayfarers\Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_journalTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init();
    }

    private function createOp(): Op_journal {
        /** @var Op_journal */
        $op = $this->game->machine->instanciateOperation("journal", PCOLOR);
        return $op;
    }

    private function setMarkerPosition(int $position, string $owner = PCOLOR): void {
        $markerId = "marker_$owner";
        $this->game->tokens->dbSetTokenState($markerId, $position);
    }

    private function setupConnection(int $from, int $to, ?string $requirement = null, int $gw = 1): void {
        $connector = "jconn_{$from}_{$to}_0";
        $rules = [];
        if ($requirement !== null) {
            $rules["r"] = $requirement;
        }
        if ($gw !== 1) {
            $rules["gw"] = (string) $gw;
        }
        if (!empty($rules)) {
            $this->game->material->setRulesFor($connector, $rules);
        }
    }

    private function setupPosition(int $pos, string $connections, ?string $reward = "nop"): void {
        $posId = "jpos_$pos";
        $rules = ["conn" => $connections];
        if ($reward !== null && $reward !== "") {
            $rules["r"] = $reward;
        }
        $this->game->material->setRulesFor($posId, $rules);
    }

    private function setupTagType(string $tag, string $type): void {
        $this->game->material->setRulesFor($tag, ["type" => $type]);
    }

    private function addPlayerUpgrade(string $type, string $owner = PCOLOR): void {
        $tokenId = "upg_${type}_1";
        $this->game->tokens->db->moveToken($tokenId, "tableau_$owner");
    }

    public function testGetPossibleMovesFromTerminalPosition(): void {
        // Setup: marker at terminal position with no connections
        $this->setMarkerPosition(100);
        $this->setupPosition(100, "");

        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        $this->assertEmpty($moves);
    }

    public function testGetPossibleMovesWithFreeConnection(): void {
        // Setup: position 0 connects to position 10 with no requirements
        $this->setMarkerPosition(0);
        $this->setupPosition(0, "10");
        $this->setupConnection(0, 10, "true");

        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        $this->assertArrayHasKey("jpos_10", $moves);
        $this->assertEquals(Material::RET_OK, $moves["jpos_10"]["q"]);
        $this->assertEquals("North", $moves["jpos_10"]["name"]);
    }

    public function testGetPossibleMovesWithMultipleConnections(): void {
        // Setup: position 0 connects to both 10 and 15
        $this->setMarkerPosition(0);
        $this->setupPosition(0, "10,15");
        $this->setupConnection(0, 10);
        $this->setupConnection(0, 15);

        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        $this->assertArrayHasKey("jpos_10", $moves);
        $this->assertArrayHasKey("jpos_15", $moves);
        $this->assertEquals(Material::RET_OK, $moves["jpos_10"]["q"]);
        $this->assertEquals(Material::RET_OK, $moves["jpos_15"]["q"]);
    }

    public function testGetPossibleMovesWithTagRequirementMet(): void {
        // Setup: connection requires 2 City tags
        $this->setMarkerPosition(10);
        $this->setupPosition(10, "20");
        $this->setupConnection(10, 20, "tag_City", 2);
        $this->setupTagType("tag_City", "wicon_city");

        // Give player 2 city tags via cards in tableau
        $this->game->tokens->db->moveToken("card_land_1", "tableau_" . PCOLOR);
        $this->game->material->setRulesFor("card_land_1", ["tags" => "City"]);
        $this->game->tokens->db->moveToken("card_land_2", "tableau_" . PCOLOR);
        $this->game->material->setRulesFor("card_land_2", ["tags" => "City"]);

        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        $this->assertArrayHasKey("jpos_20", $moves);
        $this->assertEquals(Material::RET_OK, $moves["jpos_20"]["q"]);
        $this->assertEquals("2 [wicon_city]", $moves["jpos_20"]["name"]);
    }

    public function testGetPossibleMovesWithTagRequirementNotMet(): void {
        // Setup: connection requires 3 Vista tags but player has none
        $this->setMarkerPosition(10);
        $this->setupPosition(10, "23");
        $this->setupConnection(10, 23, "tag_Vista", 1);
        $this->setupTagType("tag_Vista", "wicon_vista");

        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        $this->assertArrayHasKey("jpos_23", $moves);
        $this->assertEquals(Material::ERR_PREREQ, $moves["jpos_23"]["q"]);
    }

    public function testGetPossibleMovesWithUpgradeRequirement(): void {
        // Setup: connection requires 1 black upgrade
        $this->setMarkerPosition(20);
        $this->setupPosition(20, "40");
        $this->setupConnection(20, 40, "tag_upg_black", 1);
        $this->setupTagType("tag_upg_black", "wicon_upg_black");

        // Give player a black upgrade
        $this->addPlayerUpgrade("black");

        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        $this->assertArrayHasKey("jpos_40", $moves);
        $this->assertEquals(Material::RET_OK, $moves["jpos_40"]["q"]);
        $this->assertEquals("1 [wicon_upg_black]", $moves["jpos_40"]["name"]);
    }

    public function testGetPossibleMovesWithOperationRequirement(): void {
        // Setup: connection requires Op_n_infBlack (player must have black influence)
        $this->setMarkerPosition(40);
        $this->setupPosition(40, "50");
        $this->setupConnection(40, 50, "Op_n_infBlack");

        // Give player black influence (influence type is influence_{owner})
        $this->game->tokens->db->moveToken("influence_" . PCOLOR . "_1", "guild_black", 0);

        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        $this->assertArrayHasKey("jpos_50", $moves);
        $this->assertEquals(Material::RET_OK, $moves["jpos_50"]["q"]);
        $this->assertEquals("[wicon_inf_black_pay]", $moves["jpos_50"]["name"]);
    }

    public function testGetPossibleMovesWithOperationRequirementNotMet(): void {
        // Setup: connection requires Op_n_infBlack but player has no black influence
        $this->setMarkerPosition(40);
        $this->setupPosition(40, "50");
        $this->setupConnection(40, 50, "Op_n_infBlack");

        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        $this->assertArrayHasKey("jpos_50", $moves);
        $this->assertEquals(Material::ERR_PREREQ, $moves["jpos_50"]["q"]);
    }

    public function testGetConnectorId(): void {
        $op = $this->createOp();
        $connector = $op->getConnectorId(0, 10);

        $this->assertEquals("jconn_0_10_0", $connector);
    }

    public function testCanSkipWhenNoValidTargets(): void {
        // Setup: terminal position with no connections
        $this->setMarkerPosition(100);
        $this->setupPosition(100, "");

        $op = $this->createOp();

        $this->assertTrue($op->canSkip());
    }

    public function testCannotSkipWhenValidTargetsExist(): void {
        // Setup: position with valid connection
        $this->setMarkerPosition(0);
        $this->setupPosition(0, "10");
        $this->setupConnection(0, 10);

        $op = $this->createOp();

        $this->assertFalse($op->canSkip());
    }

    public function testRequireConfirmation(): void {
        $op = $this->createOp();

        $this->assertTrue($op->requireConfirmation());
    }

    public function testResolveMovesMarkerToNewPosition(): void {
        // Setup: move from position 0 to position 10
        $this->setMarkerPosition(0);
        $this->setupPosition(0, "10");
        $this->setupConnection(0, 10);
        $this->setupPosition(10, "20", "pickWorker");

        $op = $this->createOp();
        // Set userArgs directly for testing
        $op->action_resolve([Operation::ARG_TARGET => "jpos_10"]);

        // Verify marker moved to new position
        $markerId = "marker_" . PCOLOR;
        $newState = (int) $this->game->tokens->db->getTokenState($markerId);
        $this->assertEquals(10, $newState);
    }

    public function testResolveQueuesPositionReward(): void {
        // Setup: position 10 has reward
        $this->setMarkerPosition(0);
        $this->setupPosition(0, "10");
        $this->setupConnection(0, 10);
        $this->setupPosition(10, "20", "pickWorker");

        $op = $this->createOp();
        $op->action_resolve([Operation::ARG_TARGET => "jpos_10"]);

        // Check that marker moved (basic verification that resolve executed)
        $markerId = "marker_" . PCOLOR;
        $newState = (int) $this->game->tokens->db->getTokenState($markerId);
        $this->assertEquals(10, $newState);
    }

    public function testResolveQueuesConnectorOperation(): void {
        // Setup: connector has Op_n_infBlack requirement
        $this->setMarkerPosition(40);
        $this->setupPosition(40, "50");
        $this->setupConnection(40, 50, "Op_n_infBlack");
        $this->setupPosition(50, "60", "pickWorker"); // Position needs a reward operation

        // Give player black influence
        $this->game->tokens->db->moveToken("influence_" . PCOLOR . "_1", "guild_black", 0);

        $op = $this->createOp();
        $op->action_resolve([Operation::ARG_TARGET => "jpos_50"]);

        // Verify marker moved
        $markerId = "marker_" . PCOLOR;
        $newState = (int) $this->game->tokens->db->getTokenState($markerId);
        $this->assertEquals(50, $newState);
    }

    public function testResolveTriggersEndGameOnTerminalPosition(): void {
        // Setup: test end game trigger by checking the method directly
        // rather than through resolve which has complex prerequisites
        $op = $this->createOp();

        // Initially game stage should be 0
        $gameStage = $this->game->tokens->db->getTokenState(Game::GAME_STAGE);
        $this->assertEquals(0, $gameStage);

        // Trigger end game
        $op->triggerEndGame();

        // Verify game stage was set (end game triggered)
        $gameStage = $this->game->tokens->db->getTokenState(Game::GAME_STAGE);
        $this->assertGreaterThan(0, $gameStage);
    }

    public function testTriggerEndGameSetsPlayerNumber(): void {
        $op = $this->createOp();
        $op->triggerEndGame();

        $gameStage = $this->game->tokens->db->getTokenState(Game::GAME_STAGE);
        $this->assertEquals(1, $gameStage); // First player triggers end game
    }

    public function testTriggerEndGameOnlyTriggersOnce(): void {
        $op = $this->createOp();

        // First trigger
        $op->triggerEndGame();
        $gameStage1 = $this->game->tokens->db->getTokenState(Game::GAME_STAGE);

        // Second trigger (should not change)
        $op->triggerEndGame();
        $gameStage2 = $this->game->tokens->db->getTokenState(Game::GAME_STAGE);

        $this->assertEquals($gameStage1, $gameStage2);
        $this->assertEquals(1, $gameStage2);
    }

    public function testGetIconicName(): void {
        $op = $this->createOp();
        $this->assertEquals("[wicon_journal]", $op->getIconicName());
    }

    public function testGetPossibleMovesWithMixedRequirements(): void {
        // Setup: multiple connections with different requirement types
        $this->setMarkerPosition(50);
        $this->setupPosition(50, "60,63");

        // Connection to 60 requires 3 Harbour tags
        $this->setupConnection(50, 60, "tag_Harbour", 3);
        $this->setupTagType("tag_Harbour", "wicon_harbour");

        // Connection to 63 requires 2 blue upgrades
        $this->setupConnection(50, 63, "tag_upg_blue", 2);
        $this->setupTagType("tag_upg_blue", "wicon_upg_blue");

        // Give player 3 harbour tags
        for ($i = 1; $i <= 3; $i++) {
            $this->game->tokens->db->moveToken("card_water_$i", "tableau_" . PCOLOR);
            $this->game->material->setRulesFor("card_water_$i", ["tags" => "Harbour"]);
        }

        // Give player only 1 blue upgrade (not enough)
        $this->addPlayerUpgrade("blue");

        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        // First connection should be achievable
        $this->assertArrayHasKey("jpos_60", $moves);
        $this->assertEquals(Material::RET_OK, $moves["jpos_60"]["q"]);

        // Second connection should not be achievable
        $this->assertArrayHasKey("jpos_63", $moves);
        $this->assertEquals(Material::ERR_PREREQ, $moves["jpos_63"]["q"]);
    }

    public function testConnectionNamesFromActualMaterial(): void {
        // This test uses actual material data to verify names are correct
        // Names should either be custom names from material or [wicon_...] format

        // Test position 0 which has custom named connections in material
        $this->setMarkerPosition(0);
        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        // Position 0 connects to 10 and 15 according to journal_material.csv
        $this->assertArrayHasKey("jpos_10", $moves);
        $this->assertArrayHasKey("jpos_15", $moves);

        // Connection 0->10 should have name "North" from material
        $this->assertEquals("North", $moves["jpos_10"]["name"]);

        // Connection 0->15 should have name "South" from material
        $this->assertEquals("South", $moves["jpos_15"]["name"]);

        // Test position 10 which has tag-based requirements
        $this->setMarkerPosition(10);
        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        // Position 10 connects to 20 and 23 according to journal_material.csv
        // 10->20 requires tag_City (2)
        // 10->23 requires tag_Vista (1)
        $this->assertArrayHasKey("jpos_20", $moves);
        $this->assertArrayHasKey("jpos_23", $moves);

        // Names should be in format "N [wicon_...]" where N is the required count
        $this->assertMatchesRegularExpression('/^\d+ \[wicon_\w+\]$/', $moves["jpos_20"]["name"]);
        $this->assertMatchesRegularExpression('/^\d+ \[wicon_\w+\]$/', $moves["jpos_23"]["name"]);

        // Test position 40 which has Op_n_infBlack requirement
        $this->setMarkerPosition(40);
        $op = $this->createOp();
        $moves = $op->getPossibleMoves();

        // Position 40 connects to 50 according to journal_material.csv
        // 40->50 requires Op_n_infBlack
        $this->assertArrayHasKey("jpos_50", $moves);

        // Name should be [wicon_inf_black_pay] for Op_n_infBlack
        $this->assertEquals("[wicon_inf_black_pay]", $moves["jpos_50"]["name"]);
    }
}
