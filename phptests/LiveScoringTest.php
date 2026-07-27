<?php

declare(strict_types=1);

use Tests\GameUT;
use PHPUnit\Framework\TestCase;

/**
 * Live scoring (BGA suggestion #219576): EndScore::finalScoring can run without committing, filling a
 * table instead of writing score/stats/log. Guards both halves of that contract - the preview writes
 * nothing, and it produces the same numbers the real scoring later commits.
 */
final class LiveScoringTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init(2);
        $this->game->setGameStateValue("variant_live_scoring", 1);
        $this->game->tokens->createTokens();
        $this->setupScoringMaterial();
    }

    /** 2 Land cards, an upgrade tile and a black guild majority for the first player (on top of their home card) */
    private function setupScoringMaterial(): void {
        $db = $this->game->tokens->db;
        $db->moveToken("card_land_1", "tableau_" . PCOLOR);
        $db->moveToken("card_land_2", "tableau_" . PCOLOR);
        $db->moveToken("upg_yellow_5", "tableau_" . PCOLOR);
        $db->moveToken("influence_" . PCOLOR . "_1", "guild_black");
    }

    private function playerId(string $color): int {
        return $this->game->custom_getPlayerIdByColor($color);
    }

    public function testPreviewComputesScoreWithoutWritingIt(): void {
        $table = $this->game->scoreAllTable();
        $playerId = $this->playerId(PCOLOR);

        $this->assertSame(8, $table[$playerId]["total"], "3 City tags + home card + 1 VP tile + black guild majority");
        $this->assertSame(3, $table[$playerId]["game_vp_tags"], "home card counts as a City too");
        $this->assertSame(1, $table[$playerId]["game_vp_space"], "the home card scores as a space card");
        $this->assertSame(1, $table[$playerId]["game_vp_caravan"]);
        $this->assertSame(3, $table[$playerId]["game_vp_guilds"]);

        $this->assertSame(0, $this->game->playerScore->get($playerId), "preview must not touch the score");
        $this->assertSame(0, $this->game->playerStats->get("game_vp_tags", $playerId), "preview must not touch stats");
    }

    public function testPreviewIsIdempotent(): void {
        $first = $this->game->scoreAllTable();
        $second = $this->game->scoreAllTable();
        $this->assertEquals($first, $second);
    }

    public function testPreviewMatchesCommittedScore(): void {
        $table = $this->game->scoreAllTable();

        $this->game->gamestate->changeActivePlayer($this->playerId(PCOLOR));
        (new Bga\Games\wayfarers\States\EndScore($this->game))->finalScoring();

        foreach ($table as $playerId => $entry) {
            $this->assertSame($entry["total"], $this->game->playerScore->get((int) $playerId), "player $playerId");
            foreach (GameUT::VP_STATS as $stat) {
                $this->assertSame($entry[$stat] ?? 0, $this->game->playerStats->get($stat, (int) $playerId), "player $playerId, $stat");
            }
        }
    }

    /** Live scoring pushes previews into the panel counters, so committing must reset rather than add on top */
    public function testCommittedScoreIgnoresWhateverWasDisplayedBefore(): void {
        $playerId = $this->playerId(PCOLOR);
        $this->game->playerScore->set($playerId, 99);

        $table = $this->game->scoreAllTable();
        $this->game->gamestate->changeActivePlayer($playerId);
        (new Bga\Games\wayfarers\States\EndScore($this->game))->finalScoring();

        $this->assertSame($table[$playerId]["total"], $this->game->playerScore->get($playerId));
    }

    /** The final payload is now the preview too, so the automa total must match the VP tracker the commit fills */
    public function testSoloPreviewMatchesCommittedAutomaScore(): void {
        $game = new GameUT();
        $game->init(1);
        $game->tokens->createTokens();
        $game->tokens->db->moveToken("card_land_1", "tableau_" . PCOLOR);
        $game->tokens->db->moveToken("card_land_2", "tableau_" . PCOLOR);
        $game->tokens->db->moveToken("card_folk_1", "tableau_" . ACOLOR);
        $game->tokens->db->createToken("tracker_vp_" . ACOLOR, "miniboard_" . ACOLOR, 0);

        $table = $game->scoreAllTable();
        $game->gamestate->changeActivePlayer($game->custom_getPlayerIdByColor(PCOLOR));
        (new Bga\Games\wayfarers\States\EndScore($game))->finalScoring();

        $this->assertSame($table[GameUT::PLAYER_AUTOMA]["total"], $game->tokens->getTrackerValue(ACOLOR, "vp"));
    }

    public function testSoloPreviewIncludesAutoma(): void {
        $game = new GameUT();
        $game->init(1);
        $game->setGameStateValue("variant_live_scoring", 1);
        $game->tokens->createTokens();
        $game->tokens->db->moveToken("card_folk_1", "tableau_" . ACOLOR);

        $table = $game->scoreAllTable();
        $this->assertSame(1, $table[GameUT::PLAYER_AUTOMA]["total"], "1 VP per Townsfolk card acquired by the automa");
        $this->assertSame(1, $table[GameUT::PLAYER_AUTOMA]["game_vp_ai_folk"]);

        $update = $game->getScoresUpdate();
        $this->assertFalse($update["final"]);
        $this->assertArrayNotHasKey(GameUT::PLAYER_AUTOMA, $update["endScores"], "the automa belongs in aiEndScores");
        $this->assertSame(1, $update["aiEndScores"][GameUT::PLAYER_AUTOMA]["total"]);
    }

    public function testNothingIsSentWhenTheOptionIsOff(): void {
        $this->game->setGameStateValue("variant_live_scoring", 0);
        $update = $this->game->getScoresUpdate();

        $this->assertNull($update["endScores"]);
        $this->assertNull($update["aiEndScores"]);
    }

    /** The live update rides the endScores notification, so it must carry the same keys the final one does */
    public function testScoresUpdateMatchesEndScoresShape(): void {
        $update = $this->game->getScoresUpdate();
        $playerId = $this->playerId(PCOLOR);

        $this->assertNull($update["aiEndScores"], "no automa in a 2 player game");
        $this->assertEqualsCanonicalizing(
            array_merge(["total"], GameUT::VP_STATS),
            array_keys($update["endScores"][$playerId]),
            "same entries the score sheet expects"
        );
    }
}
