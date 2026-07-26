<?php

declare(strict_types=1);

use Tests\Campaign\CampaignBase;

/**
 * BGA #227894 (4-player "block"/softlock) - reproduces the real defect end-to-end through the op-machine.
 *
 * A player places a worker on a card that carries an OPPONENT's influence. Placing on such a card queues
 * `cardInteract` (Op_placeWorker.php:112, buy=false), which forces the acting player to pay the influence
 * owner one coin or one provision. When the player has NEITHER coin NOR provision, both payment options are
 * ERR_COST (Op_cardInteract::getPossibleMoves), so the op has zero valid targets. Op_cardInteract also
 * requireConfirmation()==true (opponent influence present) and does not override canSkip(), so the op is
 * void yet cannot be auto-resolved or skipped: the player is stuck (matches the "IMPOSSIBLE TO UNDO"
 * softlock in the report).
 *
 * RULES.md:83: "If they cannot pay, then they cannot interact with that Card." The interaction should be
 * blocked UP FRONT (the worker placement / card slot should not be offered) when the player cannot pay.
 *
 * Documents buggy behavior for BGA #227894 (softlock); flip once fixed - interacting with an influenced
 * card the player cannot pay for should be blocked up front (RULES.md:83).
 */
class Campaign_CardInteractCannotPaySoftlockTest extends CampaignBase {
    private function landAtPosition1(): string {
        foreach ($this->game->tokens->getTokensOfTypeInLocation("card_land", "mainarea") as $key => $info) {
            if ((int) $info["state"] === 1) {
                return $key;
            }
        }
        $this->fail("no land card at mainarea position 1");
    }

    private function opponentColor(string $notThis): string {
        foreach ($this->game->getPlayerColors() as $color) {
            if ($color !== $notThis) {
                return $color;
            }
        }
        $this->fail("no opponent color found");
    }

    private function zeroOutResource(string $color, string $resource): void {
        $count = (int) $this->game->tokens->db->getTokenState("tracker_{$resource}_{$color}");
        if ($count !== 0) {
            $this->game->effect_incCount($color, $resource, -$count, "test");
        }
        $this->assertEquals(0, (int) $this->game->tokens->db->getTokenState("tracker_{$resource}_{$color}"));
    }

    public function testInteractingWithInfluencedCardWithNoMoneySoftlocks(): void {
        $this->setupGame(4);
        $pc = $this->getActiveColor();
        $this->assertOpType("turn");

        $opp = $this->opponentColor($pc);

        // The acting player has nothing to pay with.
        $this->zeroOutResource($pc, "coin");
        $this->zeroOutResource($pc, "food");

        // A land card at position 1 (worker action) carrying an OPPONENT's influence.
        $land = $this->landAtPosition1();
        $this->game->tokens->db->moveToken("influence_{$opp}_20", $land);

        // Give the acting player a green worker (green may be placed on any non-space card).
        $this->game->tokens->db->moveToken("worker_green_1", "tableau_$pc", 0);

        // Player turn: place the green worker on the opponent-influenced land card.
        $this->respond("worker_green_1");
        $this->assertOpType("placeWorker");
        $this->respond($land);

        // BUG: placing on an opponent-influenced card queues cardInteract even though the player cannot pay.
        $this->assertOpType("cardInteract", "player is forced into the pay-to-interact step");

        // Both payment options (coin and food) are ERR_COST, so there are NO valid targets to choose.
        $target = $this->getOpArgs()["target"] ?? [];
        $this->assertSame([], $target, "no payable option: both coin and food are ERR_COST");

        // ...and the op cannot be skipped or auto-resolved, so the player is stuck (the softlock).
        $op = $this->game->machine->instanciateSimpleOperation("cardInteract", $pc, ["card" => $land, "buy" => false]);
        $this->assertTrue($op->requireConfirmation(), "opponent influence forces confirmation");
        $this->assertFalse($op->canSkip(), "cardInteract cannot be skipped");
        $this->assertTrue($op->isVoid(), "no valid target and not skippable => void/stuck");

        // Once fixed (interaction blocked up front), this test should be flipped: the land slot should not be
        // an offered target for placement, so the player would never reach an unpayable cardInteract.
    }
}
