<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * implementation : © Alena Laskavaia <laskava@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 */

declare(strict_types=1);

namespace Bga\Games\wayfarers\Operations;

use Bga\Games\wayfarers\Game;
use Bga\Games\wayfarers\OpCommon\AiOperation;
use Bga\Games\wayfarers\OpCommon\Operation;

class Op_finalScoring extends Operation {
    function resolve(): void {
        $this->dbSetTokenState(Game::GAME_STAGE, 5, clienttranslate("Final turn complete. Game ends!"));
        $this->finalScoring();
    }

    function finalScoring(): void {
        $game = $this->game;
        $players = $game->loadPlayersBasicInfos();
        $guildInfluence = []; // Track influence per guild per player for majority

        foreach ($players as $player_id => $player) {
            $color = $game->custom_getPlayerColorById((int) $player_id);

            // 1. Primary Land and Water Tags (City, Vista, Harbour, Open Water)
            $primaryTags = ["City", "Vista", "Harbour", "Sea"];
            $tagCounts = [];
            $vp_tags = 0;
            foreach ($primaryTags as $tag) {
                $count = $game->countPlayerTags($tag, $color);
                $tagCounts[$tag] = $count;
                $vp = $game->getTagVP($count);
                $game->effect_incVp($color, $vp, "", "game_vp_tag_$tag");
                $vp_tags += $vp;
            }
            $game->playerStats->inc("game_vp_tags", $vp_tags, $player_id);

            // Sets bonus: 5 VP for each set of 4 unique primary tags
            $sets = min($tagCounts);
            if ($sets > 0) {
                $game->effect_incVp($color, $sets * 5, "game_vp_sets");
            }

            // 2. Space and Inspriration Cards VP
            $cards = $game->tokens->getTokensOfTypeInLocation("card_space", "tableau_$color");
            $inspCards = $game->tokens->getTokensOfTypeInLocation("card_insp", "tableau_$color");

            foreach ($cards as $cardKey => $cardInfo) {
                $vp = $game->countVpForSpaceCard($cardKey, $color);

                $game->effect_incVp($color, $vp, "game_vp_space", $cardKey);
                if (!$vp) {
                    continue;
                }
                // Check if there's a tucked inspiration card at the same position
                $spacePos = (int) $cardInfo["state"];
                foreach ($inspCards as $inspKey => $inspInfo) {
                    $inspPos = (int) $inspInfo["state"];
                    if ($inspPos === $spacePos) {
                        // Found tucked inspiration card, check if goal is achieved
                        if ($game->isInspirationGoalAchieved($inspKey, $color)) {
                            // Goal achieved - score space card VP again
                            $game->effect_incVp($color, $vp, "game_vp_insp", $inspKey);
                        } else {
                            $game->notifyMessage(
                                clienttranslate('${player_name} did not achieve the goal for tucked inspiration card ${token_name}'),
                                ["token_name" => $game->getTokenName($inspKey)],
                                $player_id
                            );
                        }
                        break; // Only one inspiration card per space card
                    }
                }
            }

            // 3. Caravan - VP from upgrade tiles
            $tiles = $game->tokens->getTokensOfTypeInLocation("upg", "tableau_$color");
            foreach ($tiles as $tileKey => $tileInfo) {
                $vp = (int) $game->getRulesFor($tileKey, "vp", 0);
                if ($vp > 0) {
                    $game->effect_incVp($color, $vp, "game_vp_caravan");
                }
            }

            // Track guild influence for majorities
            foreach (["guild_black", "guild_yellow", "guild_blue"] as $guild) {
                $guildInfluence[$guild][$color] = $game->countGuildInfluence($guild, $color);
            }
        }

        if ($game->isSolo()) {
            $color = $game->getAutomaColor();

            // The AI scores VP for the following: 1VP per acquired Townsfolk
            // Card; 2VP per acquired Water/Land Card; 3VP per acquired Space
            // Card; 4VP per acquired Inspiration Card;
            $cardTypes = ["folk", "land", "water", "space", "insp"];
            foreach ($cardTypes as $type) {
                $cards = $game->tokens->getTokensOfTypeInLocation("card_$type", "tableau_$color");
                $c = count($cards);
                $vpc = AiOperation::getCardTypeVP($type);
                $vp = $vpc * $c;
                $stat = "game_vp_ai_$type";
                if ($type == "land" || $type == "water") {
                    $stat = "game_vp_ai_cards";
                }
                $game->effect_incVp($color, $vp, $stat);
            }
            //VP from acquired Upgrade Tiles;
            $tiles = $game->tokens->getTokensOfTypeInLocation("upg", "tableau_$color");
            foreach ($tiles as $tileKey => $tileInfo) {
                $vp = (int) $game->getRulesFor($tileKey, "vp", 0);
                if ($vp > 0) {
                    $game->effect_incVp($color, $vp, "game_vp_ai_caravan");
                }
            }
            //VP from Guild Majorities.
            foreach (["guild_black", "guild_yellow", "guild_blue"] as $guild) {
                $guildInfluence[$guild][$color] = $game->countGuildInfluence($guild, $color);
            }
        }

        // 4. Guild Majorities - 3 VP to player with most influence in each guild
        foreach (["guild_black", "guild_yellow", "guild_blue"] as $guild) {
            $maxInfluence = 0;
            $winners = [];

            foreach ($guildInfluence[$guild] as $color => $influence) {
                if ($influence > $maxInfluence) {
                    $maxInfluence = $influence;
                    $winners = [$color];
                } elseif ($influence == $maxInfluence && $influence > 0) {
                    $winners[] = $color;
                }
            }

            $game->notifyMessage(clienttranslate('Scoring majority for ${token_name}, max influence ${max}'), [
                "token_name" => $guild,
                "max" => $maxInfluence,
            ]);

            // Only award VP if there's a single winner (no ties)
            if (count($winners) == 1 && $maxInfluence > 0) {
                $game->effect_incVp($winners[0], 3, "game_vp_guilds");
            } else {
                // add notify message that vp is no awarded because of tie
                $game->notifyMessage(clienttranslate('No VP awarded for ${token_name} due to tie'), ["token_name" => $guild]);
            }
        }

        // Set tiebreaker: Black Influence, then Yellow, then Blue
        $score = 0;
        foreach ($players as $player_id => $player) {
            $color = $game->custom_getPlayerColorById((int) $player_id);
            $black = $guildInfluence["guild_black"][$color] ?? 0;
            $yellow = $guildInfluence["guild_yellow"][$color] ?? 0;
            $blue = $guildInfluence["guild_blue"][$color] ?? 0;
            // Encode tiebreaker as single number: black * 10000 + yellow * 100 + blue
            $tiebreaker = $black * 10000 + $yellow * 100 + $blue;
            $game->playerScoreAux->set($player_id, $tiebreaker);

            $score = $game->playerScore->get($player_id);
            $game->notifyMessage(clienttranslate('${player_name} gets total score of ${points}'), ["points" => $score], $player_id);
            $game->playerStats->set("game_vp_total", $score, $player_id);
        }

        $aiEndScored = null;
        $reverseScoring = false;
        if ($game->isSolo()) {
            // notify with total of automa scope
            $aiScore = $game->tokens->getTrackerValue($game->getAutomaColor(), "vp");
            $player_id = Game::PLAYER_AUTOMA;
            $game->notifyMessage(clienttranslate('${player_name} gets total score of ${points}'), ["points" => $aiScore], $player_id);
            $aiEndScored = $game->getAiEndScores();

            if ($score < $aiScore) {
                $game->notifyMessage(clienttranslate('${player_name} wins! You lose'), [], $player_id);
                $game->notifyMessage(clienttranslate("Scoring is negated (cannot keep positive scoring in solo when lost)"));
                $game->playerScore->set($game->getFirstPlayer(), -$score);
                $reverseScoring = true;
            }
        }

        $game->notify->all("endScores", "", [
            "endScores" => $game->getEndScores(),
            "aiEndScores" => $aiEndScored,
            "final" => true,
            "reverseScoring" => $reverseScoring,
        ]);

        // $playersDb = $game->getCollectionFromDb("SELECT * FROM `player`");
        // $players = Player::fromPlayersDb($playersDb);
        // return GameResult::individualRanking($players, reverseScore: $reverseScoring);
    }
}
