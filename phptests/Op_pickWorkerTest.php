<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Material;
use Bga\Games\wayfarers\Operations\Op_cardBase;
use Bga\Games\wayfarers\Operations\Op_pickWorker;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

/**
 * RULES.md line 252: Players can never place and retrieve the same Worker by any means
 * during a single turn. If that Worker was on a Card they wanted to acquire, they could
 * not choose to acquire that Card.
 *
 * Implementation: Op_placeWorker sets token_state=1 on the worker; Op_pickWorker and
 * Op_cardBase filter against state!=0; Op_turnconf resets state to 0 at end of turn.
 */
final class Op_pickWorkerTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init();
        $this->game->tokens->createTokens();
    }

    public function testWorkerPlacedThisTurnNotInPickWorkerAvailable(): void {
        $color = PCOLOR;

        // Two cards on main board; place workers on both. The one marked state=1
        // represents a worker placed this turn; the other (state=0) was placed earlier.
        $this->game->tokens->db->moveToken("card_land_1", "mainarea", 1);
        $this->game->tokens->db->moveToken("card_land_2", "mainarea", 2);
        $this->game->tokens->db->moveToken("worker_yellow_1", "card_land_1", 1); // placed this turn
        $this->game->tokens->db->moveToken("worker_yellow_2", "card_land_2", 0); // placed earlier

        /** @var Op_pickWorker */
        $op = $this->game->machine->instanciateOperation("pickWorker", $color);
        $available = $op->getAvailableWorkers();

        $this->assertNotContains("worker_yellow_1", $available, "Worker placed this turn must not be pickable");
        $this->assertContains("worker_yellow_2", $available, "Worker placed on prior turn must remain pickable");
    }

    public function testCardWithThisTurnWorkerCannotBeAcquired(): void {
        $color = PCOLOR;

        $this->game->tokens->db->moveToken("card_land_1", "mainarea", 1);
        $this->game->tokens->db->moveToken("card_land_2", "mainarea", 2);
        $this->game->tokens->db->moveToken("worker_yellow_1", "card_land_1", 1); // placed this turn
        // card_land_2 has no worker — acquisition should remain available (aside from cost)

        // Give the player enough coin to afford; test card_land cost variability by bumping coins.
        $this->game->effect_incCount($color, "coin", 99, "test");

        /** @var Op_cardBase */
        $op = $this->game->machine->instanciateOperation("cardLand", $color);
        $res = $op->getPossibleMoves();

        $this->assertArrayHasKey("card_land_1", $res);
        $this->assertSame(Material::ERR_NOT_APPLICABLE, $res["card_land_1"]["q"], "Card with this-turn worker must be blocked");
        $this->assertArrayHasKey("card_land_2", $res);
        $this->assertNotSame(Material::ERR_NOT_APPLICABLE, $res["card_land_2"]["q"], "Card without this-turn worker must not be blocked by this rule");
    }

    public function testTurnStartResetsWorkerStates(): void {
        $color = PCOLOR;

        $this->game->tokens->db->moveToken("card_land_1", "mainarea", 1);
        $this->game->tokens->db->moveToken("worker_yellow_1", "card_land_1", 1); // placed previous turn

        $op = $this->game->machine->instanciateOperation("turn", $color);
        $op->auto();

        $info = $this->game->tokens->db->getTokenInfo("worker_yellow_1");
        $this->assertSame(0, (int) $info["state"], "Op_turn::auto must reset worker state to 0");

        /** @var Op_pickWorker */
        $pickOp = $this->game->machine->instanciateOperation("pickWorker", $color);
        $this->assertContains("worker_yellow_1", $pickOp->getAvailableWorkers(), "Worker must be pickable again on next turn");
    }
}
