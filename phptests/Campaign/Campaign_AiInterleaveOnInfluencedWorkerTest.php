<?php

declare(strict_types=1);

use Tests\Campaign\CampaignBase;

/**
 * BGA #233581 (solo vs AI "Aida") - reproduces the real defect end-to-end through the op-machine.
 *
 * The human places a green worker on a Land card (action position 1 = "cardFolk,diceMod") that
 * carries the automa's influence. Paying to interact with an automa-influenced card queues `ai_res`
 * (the automa "receives" the payment as resource-marker movement). When that movement crosses the
 * 4.5 bonus, Op_ai_res fires the aiboard `r2` bonus - on board 3 (Aida the Mayor) that is
 * `ai_cardFolk`. Because `cardInteract` is queued ahead of the worker action's own `cardFolk`, that
 * bonus used to run BEFORE the human was offered his own folk pick - the automa acting in the middle
 * of the human's still-unfinished turn.
 *
 * Fixed: Op_ai_res defers the bonus when the automa's `turn` op is still pending, inserting it right
 * before that turn so it resolves when the automa activates.
 */
class Campaign_AiInterleaveOnInfluencedWorkerTest extends CampaignBase {
    private const AI = "ffffff";

    private function landAtPosition1(): string {
        foreach ($this->game->tokens->getTokensOfTypeInLocation("card_land", "mainarea") as $key => $info) {
            if ((int) $info["state"] === 1) {
                return $key;
            }
        }
        $this->fail("no land card at mainarea position 1");
    }

    /** Automa "acquires card" log entries attributed to the resource-track bonus. */
    private function bonusAcquisitionLogs(): array {
        return array_values(
            array_filter(
                $this->game->notify->_getNotifications(),
                fn($n) => ($n["args"]["reason"] ?? "") === "restracker_bonus" && str_contains($n["log"] ?? "", "acquires card at position")
            )
        );
    }

    /** Ranks of the automa's pending operations, keyed by op type. */
    private function automaOpRanks(): array {
        $ranks = [];
        foreach ($this->game->machine->db->getOperations(self::AI) as $row) {
            $ranks[$row["type"]] = (int) $row["rank"];
        }
        return $ranks;
    }

    public function testAutomaDefersResourceTrackBonusUntilItsOwnTurn(): void {
        $this->setupGame(1);
        $pc = $this->getActiveColor();
        $this->assertOpType("turn");

        // Force the automa onto board 3 (Aida the Mayor): resource-track 4.5 bonus r2 = ai_cardFolk.
        $this->game->tokens->db->moveToken("pboard_" . self::AI, "tableau_" . self::AI, -3);
        // Park the automa resource marker one step below the 4.5 bonus so a +1 payment crosses it.
        $this->game->tokens->db->moveToken("tracker_res_" . self::AI, "tableau_" . self::AI, 4);

        // A land card at position 1 (worker action cardFolk,diceMod) carrying AUTOMA influence.
        $land = $this->landAtPosition1();
        $this->game->tokens->db->moveToken("influence_" . self::AI . "_20", $land);

        // Give the human a green worker in supply and coins to pay the interaction.
        $this->game->tokens->db->moveToken("worker_green_1", "tableau_$pc", 0);
        $this->game->effect_incCount($pc, "coin", 5, "test");

        $folkBefore = $this->countTokens("card_folk", "tableau_" . self::AI);

        // Human turn: place the green worker on the automa-influenced land card.
        $this->respond("worker_green_1");
        $this->assertOpType("placeWorker");
        $this->respond($land);

        // Placing on an automa-influenced card asks the human to pay to interact.
        $this->assertOpType("cardInteract");
        $this->respond("tracker_coin_$pc");

        // The payment fired ai_res -> crossed 4.5 -> r2 = ai_cardFolk. The human's OWN folk pick is
        // only being offered now, so his worker action is not yet complete.
        $this->assertOpType("cardFolk", "human's own folk pick is still pending - his action is not complete");

        $this->assertEquals(
            $folkBefore,
            $this->countTokens("card_folk", "tableau_" . self::AI),
            "automa must not acquire a Townsfolk mid-human-action"
        );

        $ranks = $this->automaOpRanks();
        $this->assertArrayHasKey("ai_cardFolk", $ranks, "resource-track bonus is deferred, not dropped");
        $this->assertArrayHasKey("turn", $ranks, "automa turn is still pending");
        $this->assertLessThan($ranks["turn"], $ranks["ai_cardFolk"], "bonus resolves when the automa activates");
        $this->assertCount(0, $this->bonusAcquisitionLogs(), "bonus has not executed yet");

        // Finish the human's worker action (own folk pick, then the die bump).
        $this->respond($this->getOpArgs()["target"][0]);
        $this->assertOpType("diceMod");
        $this->respond($this->getOpArgs()["target"][0]);

        // Control is back with the human, so the automa's turn ran - and took its deferred bonus.
        $this->assertOpType("turn");
        $bonusLogs = $this->bonusAcquisitionLogs();
        $this->assertCount(1, $bonusLogs, "bonus executed once, during the automa turn");
        $this->assertStringContainsString('${reason}', $bonusLogs[0]["log"], "log tells the player why the automa acquired");
        $this->assertGreaterThan($folkBefore, $this->countTokens("card_folk", "tableau_" . self::AI), "automa ends up with the Townsfolk");
    }
}
