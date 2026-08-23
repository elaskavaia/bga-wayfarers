<?php

declare(strict_types=1);

namespace Tests\Campaign;

/**
 * Reproduction for the production zombie crash "Cannot skip this action"
 * (Operation::skip via PlayerTurn::zombie, tournament table 16/08).
 *
 * An action is offered without checking that its cost can be paid. Once the player commits,
 * the mandatory pay operation has no valid target and canSkip() is false, so it parks in
 * PlayerTurn forever: a live player sees no button, and a zombie's action_whatever() falls
 * into action_skip() and throws.
 *
 * Explore (action_land_4, 3n_food) is reachable on the very first turn - players start with
 * 2 Provisions. The same hole exists in Op_placeDie (Capital City / Capital Harbour, 2n_food)
 * and Op_upgBase step 1 (tile costs 3n_coin, affordability never checked).
 */
class Campaign_UnaffordableActionCostTest extends CampaignBase {
    public function testExploreNotOfferedWithoutEnoughProvisions(): void {
        $this->setupGame(2);
        $color = $this->getActiveColor();
        $this->assertEquals(2, $this->game->tokens->getTrackerValue($color, "food"), "players start with 2 Provisions");

        $this->assertOpType("turn");
        $this->respond("worker_yellow_1");
        $this->assertOpType("placeWorker");

        // card_land_26 sits at mainarea position 4, whose board action is Explore: pay 3 Provisions
        $this->assertEquals(4, (int) $this->game->tokens->db->getTokenState("card_land_26"));
        $this->assertNotValidTarget("card_land_26", "Explore costs 3 Provisions and the player holds 2");

        // position 1 is Recruit, "cardFolk,diceMod" - no cost, so the veto must leave it alone
        $this->assertEquals(1, (int) $this->game->tokens->db->getTokenState("card_land_20"));
        $this->assertValidTarget("card_land_20", "a board action with no cost stays offered");
    }
}
