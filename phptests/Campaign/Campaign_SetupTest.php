<?php

declare(strict_types=1);

use Tests\Campaign\CampaignBase;

/**
 * Proof-of-concept campaign (integration) test: runs the REAL setup + dispatch through the
 * op-machine and real state classes (only the token/machine stores are in-memory), then
 * asserts the game lands on the first player's turn with the main board dealt.
 */
class Campaign_SetupTest extends CampaignBase {
    public function testSetupDealsMainBoardAndStartsFirstTurn(): void {
        $this->setupGame(2);

        $this->assertOpType("turn", "after setup the first player's turn is waiting");

        // 4 faceup cards drawn from each of the 5 decks (folk, land, water, space, inspiration).
        $this->assertCount(20, $this->game->tokens->getTokensOfTypeInLocation("card", "mainarea"), "main area dealt");
    }
}
