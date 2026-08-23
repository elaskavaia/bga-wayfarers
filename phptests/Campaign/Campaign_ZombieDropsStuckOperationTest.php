<?php

declare(strict_types=1);

namespace Tests\Campaign;

/**
 * The PlayerTurn::zombie backstop: an operation with no target and no way to skip must not stall a
 * zombie seat in a "Cannot skip this action" loop (the 16/08 tournament crash class). The zombie
 * drops the stuck operation with a player notification and the game advances. Live players are
 * unaffected - they still exit via Undo.
 */
class Campaign_ZombieDropsStuckOperationTest extends CampaignBase {
    public function testStuckOperationIsDroppedAndTheGameAdvances(): void {
        $this->setupGame(2);
        $color = $this->getActiveColor();

        // An unpayable mandatory payment: 3 Provisions owed, none held - no target, no skip
        $this->game->tokens->db->setTokenState($this->game->tokens->getTrackerId($color, "food"), 0);
        $this->game->machine->push("3n_food", $color, ["reason" => "test"]);
        $this->driver->runDispatchLoop();
        $this->assertOpType("n_food");
        $this->assertEmpty($this->getOpArgs()["target"], "nothing to pay with");

        $this->driver->runZombie((int) $this->game->getMostlyActivePlayerId());

        $logs = array_filter(array_column($this->game->notify->_getNotifications(), "log"), "is_string");
        $this->assertNotEmpty(preg_grep('/as zombie.*cannot perform/', $logs), "the drop is announced to the table");
        $this->assertOpType("turn", "the stuck payment is gone and the game is back at the turn choice");
    }
}
