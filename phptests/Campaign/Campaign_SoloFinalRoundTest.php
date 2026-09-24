<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Game;
use Bga\Games\wayfarers\StateConstants;
use Tests\Campaign\CampaignBase;
use Tests\Harness\GameDriver;

/**
 * Production (studio error 690170, table 916678579): "generated notifications are larger than 128k".
 * BGA sends every notification of one request as a single packet. In solo the two heaviest requests
 * come at the very end: the human's final action, which also runs the automa's whole last turn (a Rest:
 * comet, acquisition, journal, scheme shuffle), and the confirm that follows, which scores both players.
 *
 * This drives both on a loaded late-game table and measures each packet against the framework limit.
 */
class Campaign_SoloFinalRoundTest extends CampaignBase {
    private const AI = "ffffff";

    public function testFinalHumanActionAutomaLastTurnAndScoringFitInOnePacket(): void {
        $this->setupGame(1);
        $pc = $this->getActiveColor();
        $this->assertOpType("turn");

        $this->loadLateGameTableaus($pc);

        // One die placed on a Land card so Rest is offered; two left in supply so no Rest abilities prompt.
        $land = array_key_first($this->game->tokens->getTokensOfTypeInLocation("card_land", "tableau_$pc"));
        $die = array_key_first($this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_$pc"));
        $this->game->tokens->db->moveToken($die, $land, 3);

        // Three faceup red Scheme cards: the automa rests on its next turn.
        foreach ([4, 5, 6] as $i => $num) {
            $this->game->tokens->db->moveToken("card_scheme_$num", "tableau_" . self::AI, $i + 2);
        }

        // The automa triggered the end game, so the human gets a final turn, then the automa, then scoring.
        $automaNo = $this->game->custom_getPlayerNoById(Game::PLAYER_AUTOMA);
        $this->game->tokens->db->setTokenState(Game::GAME_STAGE, $automaNo);

        $this->respond("rest");
        $this->assertOpType("rest", "a rest without abilities asks for a confirm first");
        $this->respond("confirm");
        $this->assertOpType("reroll", "rerolling the placed die asks for a confirm too");
        $this->respond("confirm");

        // Optional rerolls of the supply dice, one prompt each. The last "Keep as is" is the request
        // that carries the automa's whole last turn.
        $from = 0;
        for ($i = 0; $i < 3 && $this->game->tokens->db->getTokenState(Game::GAME_STAGE) !== 5; $i++) {
            $this->assertOpType("reroll", "only optional supply rerolls are left");
            $from = count($this->game->notify->_getNotifications());
            $this->skip();
        }
        $this->assertSame(5, $this->game->tokens->db->getTokenState(Game::GAME_STAGE), "the automa took its final turn");
        $this->measurePacket($from, "human action + automa last turn");
        $logs = array_column(array_slice($this->game->notify->_getNotifications(), $from), "log");
        $this->assertContains('${player_name} rests', $logs, "the automa rested inside that request");

        // The end of the queue lands on the confirm prompt; confirming is the request that scores.
        $this->assertSame(StateConstants::STATE_PLAYER_TURN_CONF, $this->game->gamestate->getCurrentMainStateId());
        $from = count($this->game->notify->_getNotifications());
        $this->respond("confirm");
        $scoring = $this->measurePacket($from, "final scoring");
        $this->assertArrayHasKey("endScores", $scoring, "scoring ran inside the confirm request: " . json_encode($scoring));
    }

    /** Asserts the request stayed under the packet limit; returns its notification counts by type */
    private function measurePacket(int $from, string $request): array {
        $packet = array_slice($this->game->notify->_getNotifications(), $from);
        $bytes = strlen(json_encode($packet));
        $types = array_count_values(array_column($packet, "type"));
        arsort($types);
        $this->assertLessThan(
            GameDriver::MAX_PACKET_BYTES,
            $bytes,
            "$request emitted $bytes bytes in " . count($packet) . " notifications: " . json_encode($types)
        );
        return $types;
    }

    /** Both tableaus stuffed with cards and tiles, influence in every guild, so scoring has a lot to say. */
    private function loadLateGameTableaus(string $pc): void {
        $db = $this->game->tokens->db;
        $owners = ["tableau_$pc", "tableau_" . self::AI];
        foreach (["card_land", "card_water", "card_space", "card_insp", "card_folk", "upg"] as $type) {
            $i = 0;
            foreach (array_keys($this->game->tokens->getTokensOfTypeInLocation($type, "deck_%")) as $key) {
                // Space and Inspiration cards pair up by state, so every Space card carries a tucked goal.
                $db->moveToken($key, $owners[$i % 2], intdiv($i, 2) + 2);
                $i++;
            }
        }
        foreach (["black", "yellow", "blue"] as $n => $guild) {
            $db->moveToken("influence_{$pc}_" . ($n + 1), "guild_$guild");
            $db->moveToken("influence_" . self::AI . "_" . ($n + 1), "guild_$guild");
        }
    }
}
