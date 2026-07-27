<?php

declare(strict_types=1);

use Bga\GameFramework\GameResult\GameResult;
use Bga\Games\wayfarers\States\EndScore;
use Bga\Games\wayfarers\StateConstants;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

/**
 * Losing a solo game against the automa ends with a GameResult instead of the normal end state.
 * The player row is taken from loadPlayersBasicInfos, which is keyed by player_id - reading it as
 * $players[0] passed null to GameResult::solo and killed the table (production, 27/07).
 */
final class SoloGameResultTest extends TestCase {
    private function soloGame(int $automaSpaceCards): GameUT {
        $game = new GameUT();
        $game->init(1);
        $game->tokens->createTokens();
        $game->tokens->db->createToken("tracker_vp_" . ACOLOR, "miniboard_" . ACOLOR, 0);
        for ($i = 1; $i <= $automaSpaceCards; $i++) {
            $game->tokens->db->moveToken("card_space_$i", "tableau_" . ACOLOR);
        }
        $game->gamestate->changeActivePlayer($game->custom_getPlayerIdByColor(PCOLOR));
        return $game;
    }

    public function testAutomaWinsReturnsSoloResultForTheRealPlayer(): void {
        $game = $this->soloGame(4);
        $result = (new EndScore($game))->finalScoring();

        $this->assertInstanceOf(GameResult::class, $result);
        $this->assertGreaterThan(
            $game->playerScore->get($game->custom_getPlayerIdByColor(PCOLOR)),
            $game->tokens->getTrackerValue(ACOLOR, "vp"),
            "test setup: the automa has to be ahead for the loss branch to run"
        );
    }

    public function testPlayerWinsEndsTheGameNormally(): void {
        $game = $this->soloGame(0);
        $game->tokens->db->moveToken("card_land_1", "tableau_" . PCOLOR);
        $game->tokens->db->moveToken("card_land_2", "tableau_" . PCOLOR);

        $this->assertSame(StateConstants::STATE_END_GAME, (new EndScore($game))->finalScoring());
        $this->assertGreaterThan(
            $game->tokens->getTrackerValue(ACOLOR, "vp"),
            $game->playerScore->get($game->custom_getPlayerIdByColor(PCOLOR)),
            "test setup: the player has to be ahead for the win branch to run"
        );
    }
}
