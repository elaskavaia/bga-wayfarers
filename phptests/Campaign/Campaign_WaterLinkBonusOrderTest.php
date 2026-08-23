<?php

declare(strict_types=1);

use Tests\Campaign\CampaignBase;
use Bga\Games\wayfarers\OpCommon\Operation;

/**
 * BGA #221524 - acquiring a Water Card fires several immediate effects at once, and RULES.md:225
 * lets the player perform them "in any order they choose".
 *
 * queuePool merges the simultaneous effects into a single `order` operation, so the connection
 * (link) Blue Influence and the triggered Vista are offered together: the player can take the
 * link Influence first and have the Vista move that same Influence.
 */
class Campaign_WaterLinkBonusOrderTest extends CampaignBase {
    /**
     * card_water_61 c2="u___" (Blue Influence on its right edge) meets card_water_66 c1="xx__", so
     * acquiring 66 grants the connection Blue Influence. card_water_66 is tagged Sea, which triggers
     * the Vista on card_land_22 (trig=Sea, dr="infBlue/infMove").
     */
    public function testWaterConnectionBonusIsOrderableWithTheVistaItCanFeed(): void {
        $this->setupGame(3);
        $color = $this->getActiveColor();

        $this->game->tokens->db->moveToken("card_land_22", "tableau_$color", 2);
        $this->game->tokens->db->moveToken("card_water_61", "tableau_$color", 2);
        $this->game->tokens->db->moveToken("card_water_66", "mainarea", 0);

        $this->game->machine->push("cardWater", $color, ["card" => "card_water_66", "params" => "free"]);
        $this->driver->runDispatchLoop();
        $this->respond("card_water_66");

        $orderRow = $this->game->machine->findOperation("order", $color);
        $this->assertNotNull($orderRow, "simultaneous immediate effects must merge into one order choice");
        $subTypes = array_column(Operation::decodeData($orderRow["data"])["args"] ?? [], "type");
        $this->assertContains("or", $subTypes, "the Sea tag must trigger the Vista on card_land_22 (infBlue/infMove choice)");
        $this->assertContains("infBlue", $subTypes, "the water connection must grant a Blue Influence");

        // The player chooses the order, so the link Influence can feed the Vista.
        $this->assertEquals("order", $this->getOpType(), "the player is offered the order choice");
    }
}
