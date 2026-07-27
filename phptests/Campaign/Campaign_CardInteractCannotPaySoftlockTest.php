<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Material;
use Tests\Campaign\CampaignBase;

/**
 * BGA #227894 (4-player "block"/softlock) - verifies the fix end-to-end through the op-machine.
 *
 * RULES.md:83: "If they cannot pay, then they cannot interact with that Card." A player with neither coin
 * nor provision used to be offered a card carrying an opponent's Influence anyway; the resulting
 * `cardInteract` had zero valid targets, requireConfirmation()==true and no canSkip(), so the turn was stuck
 * (the "IMPOSSIBLE TO UNDO" softlock in the report). The interaction is now blocked up front: such a card is
 * not an offered target for placing a worker, retrieving a worker, or acquiring the card.
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

    private function setResource(string $color, string $resource, int $target): void {
        $count = (int) $this->game->tokens->db->getTokenState("tracker_{$resource}_{$color}");
        if ($count !== $target) {
            $this->game->effect_incCount($color, $resource, $target - $count, "test");
        }
        $this->assertEquals($target, (int) $this->game->tokens->db->getTokenState("tracker_{$resource}_{$color}"));
    }

    /** Opponent-influenced land card at mainarea position 1, plus a green worker in the player's supply. */
    private function setupInfluencedLand(string $pc, string $opp): string {
        $land = $this->landAtPosition1();
        $this->game->tokens->db->moveToken("influence_{$opp}_20", $land);
        $this->game->tokens->db->moveToken("worker_green_1", "tableau_$pc", 0);
        return $land;
    }

    public function testCannotPlaceWorkerOnInfluencedCardWithNoMoney(): void {
        $this->setupGame(4);
        $pc = $this->getActiveColor();
        $this->assertOpType("turn");
        $opp = $this->opponentColor($pc);

        $this->setResource($pc, "coin", 0);
        $this->setResource($pc, "food", 0);
        $land = $this->setupInfluencedLand($pc, $opp);

        $this->respond("worker_green_1");
        $this->assertOpType("placeWorker");
        $this->assertNotValidTarget($land, "an unpayable influenced card cannot be interacted with (RULES.md:83)");

        // Other land cards stay available, so the player is not stuck.
        $this->assertNotEmpty($this->getOpArgs()["target"] ?? []);
    }

    public function testPlacingWorkerOnInfluencedCardResolvesWhenPayable(): void {
        $this->setupGame(4);
        $pc = $this->getActiveColor();
        $opp = $this->opponentColor($pc);

        $this->setResource($pc, "coin", 1);
        $this->setResource($pc, "food", 0);
        $this->setResource($opp, "coin", 0);
        $land = $this->setupInfluencedLand($pc, $opp);

        $this->respond("worker_green_1");
        $this->assertValidTarget($land, "a payable influenced card is still offered");
        $this->respond($land);

        $this->assertOpType("cardInteract");
        $this->assertValidTarget("tracker_coin_$pc", "coin is the only affordable payment");
        $this->assertNotValidTarget("tracker_food_$pc");
        $this->respond("tracker_coin_$pc");

        $this->assertEquals(0, (int) $this->game->tokens->db->getTokenState("tracker_coin_$pc"), "player paid 1 coin");
        $this->assertEquals(1, (int) $this->game->tokens->db->getTokenState("tracker_coin_$opp"), "influence owner was paid");
        $this->assertEquals($land, $this->game->tokens->db->getTokenInfo("worker_green_1")["location"], "worker landed on the card");
    }

    public function testCannotPickWorkerFromInfluencedCardWithNoMoney(): void {
        $this->setupGame(4);
        $pc = $this->getActiveColor();
        $opp = $this->opponentColor($pc);

        $this->setResource($pc, "coin", 0);
        $this->setResource($pc, "food", 0);
        $land = $this->setupInfluencedLand($pc, $opp);
        // A worker already on that card, placed on an earlier turn (state 0 = retrievable).
        $this->game->tokens->db->moveToken("worker_blue_1", $land, 0);

        $op = $this->game->machine->instanciateSimpleOperation("pickWorker", $pc);
        $this->assertNotContains("worker_blue_1", $op->getPossibleMoves(), "cannot retrieve a worker you cannot pay for");
        $this->assertTrue($op->canSkip(), "pickWorker stays skippable, so no softlock");
    }

    public function testCannotAcquireInfluencedCardWithNoMoney(): void {
        $this->setupGame(4);
        $pc = $this->getActiveColor();
        $opp = $this->opponentColor($pc);

        $this->setResource($pc, "coin", 0);
        $this->setResource($pc, "food", 0);
        $land = $this->setupInfluencedLand($pc, $opp);

        $op = $this->game->machine->instanciateSimpleOperation("cardLand", $pc);
        $moves = $op->getPossibleMoves();
        $this->assertArrayHasKey($land, $moves);
        $this->assertSame(Material::ERR_INFLUENCE_FEE, $moves[$land]["q"], "cannot acquire a card you cannot pay the influence owner for");
    }

    /** A chosen worker with every legal card blocked must not strand the player either. */
    public function testWorkerPlacementWithEveryCardBlockedIsSkippable(): void {
        $this->setupGame(4);
        $pc = $this->getActiveColor();
        $opp = $this->opponentColor($pc);

        $this->setResource($pc, "coin", 0);
        $this->setResource($pc, "food", 0);
        // A blue worker may go on a water card or on an inspiration card.
        $inf = 20;
        foreach (["card_water", "card_insp"] as $type) {
            foreach (array_keys($this->game->tokens->getTokensOfTypeInLocation($type, "mainarea")) as $card) {
                $this->game->tokens->db->moveToken("influence_{$opp}_" . $inf++, $card);
            }
        }

        $op = $this->game->machine->instanciateSimpleOperation("placeWorker", $pc, ["worker" => "worker_blue_1"]);
        $this->assertTrue($op->noValidTargets(), "every card a blue worker could go on is blocked");
        $this->assertTrue($op->canSkip(), "placing a chosen worker with no valid card stays skippable");
    }

    /** With every card blocked the acquisition is void: pruned from choices, undo for a committed op. */
    public function testAcquisitionWithEveryCardBlockedIsVoid(): void {
        $this->setupGame(4);
        $pc = $this->getActiveColor();
        $opp = $this->opponentColor($pc);

        $this->setResource($pc, "coin", 0);
        $this->setResource($pc, "food", 0);
        $inf = 20;
        foreach (array_keys($this->game->tokens->getTokensOfTypeInLocation("card_insp", "mainarea")) as $card) {
            $this->game->tokens->db->moveToken("influence_{$opp}_" . $inf++, $card);
        }

        $op = $this->game->machine->instanciateSimpleOperation("cardInsp", $pc);
        $this->assertTrue($op->noValidTargets(), "every inspiration card is blocked");
        $this->assertTrue($op->isVoid(), "an acquisition with every card blocked is void, not skippable");
    }

    /** The card's own price is paid before the Influence fee, so both must fit in the player's supply. */
    public function testCannotAcquireInfluencedCardWhenPriceEatsAllTheCoins(): void {
        $this->setupGame(4);
        $pc = $this->getActiveColor();
        $opp = $this->opponentColor($pc);
        $this->setResource($pc, "food", 0);

        $folk = array_key_first($this->game->tokens->getTokensOfTypeInLocation("card_folk", "mainarea"));
        $this->game->tokens->db->moveToken("influence_{$opp}_20", $folk);

        $op = $this->game->machine->instanciateSimpleOperation("cardFolk", $pc);
        $cost = (int) $this->game->getRulesFor($folk, "cost", 5);

        $this->setResource($pc, "coin", $cost);
        $this->assertSame(Material::ERR_INFLUENCE_FEE, $op->getPossibleMoves()[$folk]["q"], "no coin left over for the Influence fee");

        $this->setResource($pc, "coin", $cost + 1);
        $this->assertEmpty($op->getPossibleMoves()[$folk]["err"] ?? "", "price plus fee is affordable");
    }
}
