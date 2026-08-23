<?php

declare(strict_types=1);

use Tests\Campaign\CampaignBase;

/**
 * BGA #221524 - acquiring a Water Card fires several immediate effects at once and the player gets
 * no say in what order they resolve.
 *
 * RULES.md:225 lets a player perform the actions of a placement "in any order they choose", so the
 * reported combo is legal: take the connection (link) Blue Influence first, then use a triggered
 * Vista to move that same Influence. The implementation cannot express it: Op_cardBase::resolve()
 * queues the card's own bonus and the Vista triggers, and only then calls placeCard(), so
 * Op_cardWater::checkSideMatchingBonuses() lands the link bonus at the highest rank of all - always
 * last, never wrapped in an `order` choice.
 *
 * DOCUMENTS BUGGY BEHAVIOUR ON PURPOSE so the suite stays green until a fix lands. When the immediate
 * effects become a free-order choice, flip the assertions: the first interactive op should be `order`
 * (or the link bonus should be reachable first), not the Vista's `infBlue/infMove`.
 */
class Campaign_WaterLinkBonusOrderTest extends CampaignBase {
    /**
     * card_water_61 c2="u___" (Blue Influence on its right edge) meets card_water_66 c1="xx__", so
     * acquiring 66 grants the connection Blue Influence. card_water_66 is tagged Sea, which triggers
     * the Vista on card_land_22 (trig=Sea, dr="infBlue/infMove").
     */
    public function testWaterConnectionBonusIsForcedAfterTheVistaItShouldFeed(): void {
        $this->setupGame(3);
        $color = $this->getActiveColor();

        $this->game->tokens->db->moveToken("card_land_22", "tableau_$color", 2);
        $this->game->tokens->db->moveToken("card_water_61", "tableau_$color", 2);
        $this->game->tokens->db->moveToken("card_water_66", "mainarea", 0);

        $this->game->machine->push("cardWater", $color, ["card" => "card_water_66", "params" => "free"]);
        $this->driver->runDispatchLoop();
        $this->respond("card_water_66");

        $ranks = [];
        foreach ($this->game->machine->db->getOperations() as $row) {
            $ranks[$row["type"]] = (int) $row["rank"];
        }
        $this->assertArrayHasKey("infBlue/infMove", $ranks, "the Sea tag must trigger the Vista on card_land_22");
        $this->assertArrayHasKey("infBlue", $ranks, "the water connection must grant a Blue Influence");

        // BUG: the connection bonus sits behind the Vista, so it cannot be the Influence the Vista moves.
        $this->assertGreaterThan(
            $ranks["infBlue/infMove"],
            $ranks["infBlue"],
            "BGA #221524: connection bonus is queued after the Vista trigger instead of being orderable"
        );

        // BUG: the player is dropped straight into the Vista choice, with no `order` step in front of it.
        $this->assertEquals("or", $this->getOpType(), "BGA #221524: the Vista resolves first and no order choice is offered");
        $this->assertEquals("card_land_22", $this->getOpArgs()["data"]["reason"] ?? "", "the forced first choice is the Vista's");
    }
}
