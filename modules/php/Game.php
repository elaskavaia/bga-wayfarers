<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * wayfarers implementation : © Alena Laskavaia <laskava@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * wayfarers.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */

declare(strict_types=1);

namespace Bga\Games\wayfarers;

use Bga\GameFramework\NotificationMessage;
use Bga\Games\wayfarers\Common\PGameTokens;
use Bga\Games\wayfarers\Db\DbMultiUndo;
use Bga\Games\wayfarers\Db\DbTokens;
use Bga\Games\wayfarers\OpCommon\ComplexOperation;
use Bga\Games\wayfarers\OpCommon\OpMachine;
use Bga\Games\wayfarers\States\GameDispatch;

class Game extends Base {
    const GAME_STAGE = "game_stage";
    public static Game $instance;
    public OpMachine $machine;
    public Material $material;
    public PGameTokens $tokens;
    public DbMultiUndo $dbMultiUndo;

    function __construct() {
        Game::$instance = $this;
        parent::__construct();
        self::initGameStateLabels([
            "variant_draft_num" => 100,
            "variant_solo_dif" => 101,
            "variant_multi" => 102,
        ]);

        $this->material = new Material();
        $this->machine = new OpMachine();
        $tokens = new DbTokens($this);
        $this->tokens = new PGameTokens($this, $tokens);
        $this->dbMultiUndo = new DbMultiUndo($this, "restorePlayerTables");

        $this->notify->addDecorator(function (string $message, array $args) {
            if (str_contains($message, '${reason}') && !isset($args["reason"])) {
                $args["reason"] = "";
            }
            return $args;
        });
    }

    /*
        setupGameTables:
        
        init all game tables (players and stats init in base class)
        called from setupNewGame
    */
    protected function setupGameTables() {
        $this->tokens->createTokens();
        $tokens = $this->tokens->db;
        // setup
        $pnum = $this->getPlayersNumber();
        //         Main Board Setup

        // Place the 3 Main Board Sections in the center of the play area (either side can be used for variety).

        // TODO: pick a side

        // Shuffle the Townsfolk, Space, Land, Water, and Inspiration Cards into separate decks and place them in their designated spaces on the Main Board.
        // Draw the top 4 cards from each deck and place them faceup next to their respective draw piles.

        $this->tokens->db->shuffle("deck_folk");
        $tokens = $this->tokens->db->pickTokensForLocation(4, "deck_folk", "mainarea");
        $i = 1;
        foreach ($tokens as $token) {
            $this->tokens->db->setTokenState($token["key"], $i);
            $i++;
        }
        $this->tokens->db->shuffle("deck_space");
        $tokens = $this->tokens->db->pickTokensForLocation(4, "deck_space", "mainarea");
        $i = 1;
        foreach ($tokens as $token) {
            $this->tokens->db->setTokenState($token["key"], $i);
            $i++;
        }
        $this->tokens->db->shuffle("deck_land");
        $tokens = $this->tokens->db->pickTokensForLocation(4, "deck_land", "mainarea");
        $i = 1;
        foreach ($tokens as $token) {
            $this->tokens->db->setTokenState($token["key"], $i);
            $i++;
        }
        $this->tokens->db->shuffle("deck_water");
        $tokens = $this->tokens->db->pickTokensForLocation(4, "deck_water", "mainarea");
        $i = 1;
        foreach ($tokens as $token) {
            $this->tokens->db->setTokenState($token["key"], $i);
            $i++;
        }
        $this->tokens->db->shuffle("deck_insp");
        $tokens = $this->tokens->db->pickTokensForLocation(4, "deck_insp", "mainarea");
        $i = 1;
        foreach ($tokens as $token) {
            $this->tokens->db->setTokenState($token["key"], $i);
            $i++;
        }
        // Shuffle the Journal Tiles and place one faceup on each empty space of the Journal Track.
        $this->tokens->db->shuffle("deck_jtile");
        $i = 1;
        foreach ($tokens as $token) {
            $this->tokens->db->pickTokensForLocation(1, "deck_jtile", "jbonus_$i");
            $i++;
        }

        // Place 1 Green Worker on each indicated space along the Journal Track.
        $i = 1;
        foreach ($tokens as $token) {
            $k = $i + 10;
            $this->tokens->db->moveToken("worker_green_$i", "jbonus_$k");
            $i++;
        }

        $token_types = $this->material->get();
        foreach ($token_types as $key => $info) {
            // Only process upgrade tiles
            if (str_starts_with($key, "upg_pink")) {
                // Place all 10 Special (Pink) Upgrade Tiles on their designated spaces on the Main Board.
                $this->tokens->db->createTokensPack("{$key}_{INDEX}", "mainarea", 1, 1);
            } elseif (str_starts_with($key, "upg_")) {
                // Place 1 of each unique Green, Black, Yellow, and Blue Upgrade Tile per player on the Main Board. Return extras to the box if playing with fewer than 4 players.
                $this->tokens->db->createTokensPack("{$key}_{INDEX}", "mainarea", $pnum, 1);
            }
        }

        // Place Silver and Provisions near the Main Board to form the Main Supply.

        // Player Setup

        // Each player receives:

        // Randomly determine the first player. Use the chart to distribute starting Provisions, Silver, and Guild Influence based on turn order.
        // Return any unused Player Boards, Dice, Influence tokens, Player Markers, and Workers to the box.

        $i = 1;
        $startingPlayer = $this->getActivePlayerId();
        $p = $this->getPlayerIdsInOrder($startingPlayer);

        foreach ($p as $player_id) {
            $color = $this->getPlayerColorById($player_id);
            // 1 Player Board (randomly assigned).
            // XXX
            // 1 Player Marker in their chosen color (place it on the far-left end of the Journal Track).
            $this->tokens->db->moveToken("marker_$color", "mainarea", 0);
            // 1 Yellow Worker and 1 Blue Worker.
            $this->tokens->db->pickTokensForLocation(1, "worker_blue", "tableau_$color");
            $this->tokens->db->pickTokensForLocation(1, "worker_yellow", "tableau_$color");

            if ($i <= 2) {
                $this->effect_incCount($color, "coin", 3, "setup");
            } else {
                $this->effect_incCount($color, "coin", 4, "setup");
            }
            $this->effect_incCount($color, "food", 2, "setup");
            $this->tokens->db->moveToken("influence_{$color}_1", "guild_blue");
            if ($i > 1) {
                $this->tokens->db->moveToken("influence_{$color}_2", "guild_yellow");
            }
            // 5 Dice in their chosen color (roll 3 and place them next to their Player Board; keep 2 in reserve near the Minarets on the Main Board).
            // 15 Influence tokens in their chosen color.
            $this->tokens->db->moveToken("dice_{$color}_4", "supply");
            $this->tokens->db->moveToken("dice_{$color}_5", "supply");
            $i++;
        }

        $this->machine->queue("turn", $this->getPlayerColorById($startingPlayer));
        return GameDispatch::class;
    }

    function switchActivePlayer(int $playerId, bool $moreTime = true) {
        if ($playerId <= 2) {
            return;
        }

        if (!$this->gamestate->isPlayerActive($playerId)) {
            if ($this->gamestate->isMultiactiveState()) {
                $this->gamestate->setPlayersMultiactive([$playerId], "notpossible", false);
            } else {
                $this->gamestate->changeActivePlayer($playerId);
            }
            if ($moreTime) {
                $this->giveExtraTime($playerId);
            }
        }
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas(): array {
        $result = [];
        $result = parent::getAllDatas();

        $result = array_merge($result, $this->tokens->getAllDatas());

        $isGameEnded = $this->isEndOfGame();
        $result["gameEnded"] = $isGameEnded;
        $result["endScores"] = $isGameEnded ? $this->getEndScores() : null;

        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression() {
        return 0;
    }

    function isEndOfGame() {
        $num = $this->tokens->db->getTokenState(Game::GAME_STAGE);
        return $num >= 5;
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    function effect_incCount(string $color, string $type, int $inc = 1, string $reason, array $options = []) {
        $message = array_get($options, "message", "*");
        unset($options["message"]);

        $token_id = $this->tokens->getTrackerId($color, $type);

        $value = $this->tokens->dbResourceInc(
            $token_id,
            $inc,
            $message,
            ["reason" => $reason, "place_from" => $reason] + $options,
            $this->getPlayerIdByColor($color)
        );

        if ($value < 0 && $inc < 0) {
            $this->userAssert(clienttranslate("Insufficient resources to pay"));
        }
    }

    function effect_incVp(string $owner, int $inc, string $stat = "", array $options = []) {
        $player_id = $this->getPlayerIdByColor($owner);

        if ($inc < 0) {
            $message = clienttranslate('${player_name} loses ${absInc} VP ${reason}');
        } else {
            // if 0 print gain 0
            $message = clienttranslate('${player_name} gains ${absInc} VP ${reason}');
        }

        $score = $this->playerScore->inc(
            $player_id,
            $inc,
            new NotificationMessage($message, [
                "reason" => $stat,
            ])
        );

        $this->playerStats->inc($stat, $inc, $player_id);

        $this->notifyWithName(
            "score",
            "",
            [
                "player_score" => $score,
                "inc" => $inc,
                "absImc" => abs((int) $inc),
                "duration" => 500,
                //"target" => $target,
            ],
            $player_id
        );
    }

    function isSimultanousPlay() {
        return ((int) $this->getGameStateValue("variant_multi")) ? 1 : 0;
    }

    function getVariantSoloDif() {
        return (int) $this->getGameStateValue("variant_solo_dif");
    }

    function getRulesFor($token_id, $field = "r", $default = "") {
        return $this->material->getRulesFor($token_id, $field, $default);
    }
    function getTokenName($token_id, $default = "") {
        if (!$default) {
            $default = "$token_id ?";
        }
        return $this->material->getRulesFor($token_id, "name", $default);
    }

    function getTrackerIdAndValue(?string $color, string $type, ?array &$arr = null) {
        return $this->tokens->getTrackerIdAndValue($color, $type, $arr);
    }

    function getTagsSet(string $card) {
        $tags = $this->getRulesFor($card, "tags");
        $tagsarr = explode(" ", $tags);
        $res = [];
        foreach ($tagsarr as $tag) {
            if ($tag) {
                $res[$tag] = 1; // XXX multi?
            }
        }
        return $res;
    }
    /**
     * Count tags of a specific type for a player
     * @param string $tagName - the tag to count (City, Vista, Harbour, Water, etc.)
     * @param string $owner - player color
     * @return int - count of tags
     */
    function countPlayerTags(string $tagName, string $owner): int {
        $count = 0;

        // Count from cards in tableau
        $cards = $this->tokens->getTokensOfTypeInLocation("card", "tableau_$owner");
        foreach ($cards as $cardKey => $cardInfo) {
            $tags = $this->getTagsSet($cardKey);
            if (isset($tags[$tagName])) {
                $count += $tags[$tagName];
            }
        }

        // Count from upgrade tiles in caravan
        $tiles = $this->tokens->getTokensOfTypeInLocation("upg", "tableau_$owner");
        foreach ($tiles as $tileKey => $tileInfo) {
            $tags = $this->getTagsSet($tileKey);
            if (isset($tags[$tagName])) {
                $count += $tags[$tagName];
            }
        }

        return $count;
    }

    /**
     * Get VP for primary tag count based on scoring table
     * 2 tags = 2 VP, 3 = 4 VP, 4 = 7 VP, 5 = 10 VP, 6 = 13 VP, 7+ = 16 VP
     */
    function getTagVP(int $count): int {
        return match (true) {
            $count < 2 => 0,
            $count == 2 => 2,
            $count == 3 => 4,
            $count == 4 => 7,
            $count == 5 => 10,
            $count == 6 => 13,
            default => 16, // 7+
        };
    }

    /**
     * Count influence tokens in a guild for a player
     */
    function countGuildInfluence(string $guild, string $owner): int {
        $tokens = $this->tokens->getTokensOfTypeInLocation("influence_{$owner}", $guild);
        return count($tokens);
    }

    function finalScoring() {
        $players = $this->loadPlayersBasicInfos();
        $guildInfluence = []; // Track influence per guild per player for majority

        foreach ($players as $player_id => $player) {
            $color = $this->getPlayerColorById((int) $player_id);

            // 1. Primary Land and Water Tags (City, Vista, Harbour, Water)
            $primaryTags = ["City", "Vista", "Harbour", "Water"];
            $tagCounts = [];

            foreach ($primaryTags as $tag) {
                $count = $this->countPlayerTags($tag, $color);
                $tagCounts[$tag] = $count;
                $vp = $this->getTagVP($count);
                if ($vp > 0) {
                    $this->effect_incVp($color, $vp, "game_vp_tags");
                }
            }

            // Sets bonus: 5 VP for each set of 4 unique primary tags
            $sets = min($tagCounts);
            if ($sets > 0) {
                $this->effect_incVp($color, $sets * 5, "game_vp_sets");
            }

            // 2. Space Cards VP
            $cards = $this->tokens->getTokensOfTypeInLocation("card_space", "tableau_$color");
            foreach ($cards as $cardKey => $cardInfo) {
                $vp = (int) $this->getRulesFor($cardKey, "vp", 0);
                if ($vp > 0) {
                    $this->effect_incVp($color, $vp, "game_vp_space");
                }
            }

            // 3. Inspiration Cards (achieved goals double Space Card VP - already handled above if applicable)
            $inspirationCards = $this->tokens->getTokensOfTypeInLocation("card_insp", "tableau_$color");
            foreach ($inspirationCards as $cardKey => $cardInfo) {
                // TODO: inspiration is calculated diffrently
                $vp = (int) $this->getRulesFor($cardKey, "vp", 0);
                if ($vp > 0) {
                    $this->effect_incVp($color, $vp, "game_vp_inspiration");
                }
            }

            // 4. Caravan - VP from upgrade tiles
            $tiles = $this->tokens->getTokensOfTypeInLocation("upg", "tableau_$color");
            foreach ($tiles as $tileKey => $tileInfo) {
                $vp = (int) $this->getRulesFor($tileKey, "vp", 0);
                if ($vp > 0) {
                    $this->effect_incVp($color, $vp, "game_vp_caravan");
                }
            }

            // Track guild influence for majorities
            foreach (["guild_black", "guild_yellow", "guild_blue"] as $guild) {
                $guildInfluence[$guild][$color] = $this->countGuildInfluence($guild, $color);
            }
        }

        // 5. Guild Majorities - 3 VP to player with most influence in each guild
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

            // Only award VP if there's a single winner (no ties)
            if (count($winners) == 1 && $maxInfluence > 0) {
                $this->effect_incVp($winners[0], 3, "game_vp_guilds");
            }
        }

        // Set tiebreaker: Black Influence, then Yellow, then Blue
        foreach ($players as $player_id => $player) {
            $color = $this->getPlayerColorById((int) $player_id);
            $black = $guildInfluence["guild_black"][$color] ?? 0;
            $yellow = $guildInfluence["guild_yellow"][$color] ?? 0;
            $blue = $guildInfluence["guild_blue"][$color] ?? 0;
            // Encode tiebreaker as single number: black * 10000 + yellow * 100 + blue
            $tiebreaker = $black * 10000 + $yellow * 100 + $blue;
            $this->playerScoreAux->set($player_id, $tiebreaker);

            $score = $this->playerScore->get($player_id);
            $this->notifyMessage(clienttranslate('${player_name} gets total score of ${points}'), ["points" => $score], $player_id);
            $this->playerStats->set("game_vp_total", $score, $player_id);
        }

        $this->notify->all("endScores", "", ["endScores" => $this->getEndScores(), "final" => true]);
    }

    function getEndScores(): array {
        $endScores = [];
        $players = $this->loadPlayersBasicInfos();
        $vp_stats = ["game_vp_tags", "game_vp_sets", "game_vp_space", "game_vp_inspiration", "game_vp_caravan", "game_vp_guilds"];

        foreach ($players as $player_id => $player) {
            foreach ($vp_stats as $stat) {
                $endScores[$player_id][$stat] = $this->playerStats->get($stat, $player_id);
            }
            $endScores[$player_id]["total"] = $this->playerStats->get("game_vp_total", $player_id);
        }

        return $endScores;
    }

    public function customUndoSavepoint(int $player_id, int $barrier = 0, string $label = "undo"): void {
        $this->debugLog("customUndoSavepoint $player_id bar= $barrier");
        if ($this->isMultiActive()) {
            $this->dbMultiUndo->doSaveUndoSnapshot(["barrier" => $barrier, "label" => $label], $player_id, true);
        } else {
            $this->dbMultiUndo->doSaveUndoSnapshot(["barrier" => $barrier, "label" => $label], $player_id, true);
            $this->undoSavepoint();
        }
    }

    function restorePlayerTables($table, $saved_data, $meta) {
        $player_id = (int) $meta["player_id"];
        $owner = $this->getPlayerColorById($player_id);
        if ($table == "token") {
            // filter the data
            $curtokens = $this->tokens->db->getTokensOfTypeInLocation(null, "%_{$owner}%");
            $saved_data = array_filter($saved_data, function ($row) use ($owner, $curtokens) {
                return str_contains($row["token_location"], $owner) ||
                    str_contains($row["token_key"], $owner) ||
                    array_key_exists($row["token_key"], $curtokens);
            });
            $keys = array_map(fn($row) => $row["token_key"], $saved_data);
            $this->notifyMessage(clienttranslate('${player_name} undoes their turn'), [], $player_id);
            $this->tokens->db->dbReplaceValues($saved_data);
            foreach ($keys as $token_id) {
                $info = $this->tokens->db->getTokenInfo($token_id);
                $this->tokens->dbSetTokenLocation($token_id, $info["location"], $info["state"], "", [], $player_id);
            }

            //return true;
        } elseif ($table == "machine") {
            $multi = $this->game->machine->getAllOperationsMulti();
            foreach ($multi as $dop) {
                if ($dop["owner"] == $owner) {
                    $this->game->machine->hide((int) $dop["id"]);
                }
            }
            $this->game->machine->db->normalize();
            $saved_data = array_filter($saved_data, function ($row) use ($owner) {
                return $row["owner"] == $owner && $row["rank"] >= 0;
            });
            uasort($saved_data, function ($a, $b) {
                return $a["rank"] <=> $b["rank"];
            });
            $rank = 1;
            foreach ($saved_data as $dop) {
                $dop["rank"] = $rank++;
            }
            $this->game->machine->db->interrupt(count($saved_data));
            $this->game->machine->db->insertList(null, $saved_data);
            //return true;
        }
        return false;
    }

    function multiPlayerUndo($owner) {
        if ($this->game->isMultiActive()) {
            $this->dbMultiUndo->undoRestorePoint(0, true);
        } else {
            throw new BgaSystemException("Not implemented");
        }
    }

    function debug_op(string $type) {
        $color = $this->getCurrentPlayerColor();
        $this->machine->push($type, $color);
        $this->gamestate->jumpToState(StateConstants::STATE_GAME_DISPATCH);
    }

    function debug_specialCard(int $num) {
        $color = $this->getCurrentPlayerColor();
        $cards = $this->tokens->getTokensOfTypeInLocation("action_special", "tableau_{$color}");
        $this->tokens->dbSetTokensLocation($cards, "limbo", 0);
        $this->tokens->dbSetTokenLocation("action_special_$num", "tableau_{$color}", 0);
    }

    function debug_q() {
        $this->customUndoSavepoint((int) $this->getCurrentPlayerId(), 0);
    }
    function debug_game_variant(string $type = "variant_multi", int $value = 1) {
        $this->setGameStateValue($type, $value);
    }
    /**
     * Example of debug function.
     * Here, jump to a state you want to test (by default, jump to next player state)
     * You can trigger it on Studio using the Debug button on the right of the top bar.
     */
    public function debug_goToState(int $state = 3) {
        $this->gamestate->jumpToState($state);
    }

    /**
     * Another example of debug function, to easily test the zombie code.
     */
    public function debug_playAutomatically(int $moves = 1) {
        $count = 0;
        while (intval($this->gamestate->getCurrentMainStateId()) < 99 && $count < $moves) {
            $count++;
            foreach ($this->gamestate->getActivePlayerList() as $playerId) {
                $playerId = (int) $playerId;
                $this->gamestate->runStateClassZombie($this->gamestate->getCurrentState($playerId), $playerId);
            }
        }
    }
    public function debug_playAutomatically1() {
        return $this->debug_playAutomatically(1);
    }

    function debug_maxRes() {
        $color = $this->getCurrentPlayerColor();

        foreach (Material::getAllNonPoopResources() as $res) {
            $this->effect_incCount($color, $res, 2, "debug");
        }

        $this->gamestate->jumpToState(StateConstants::STATE_GAME_DISPATCH);
    }

    function debug_setupGameTables() {
        $this->DbQuery("DELETE FROM token");
        $this->DbQuery("DELETE FROM machine");
        $this->DbQuery("DELETE FROM multiundo");
        $this->gamestate->jumpToState(StateConstants::STATE_GAME_DISPATCH);
        $this->setupGameTables();
        //$newGameDatas = $this->getAllTableDatas(); // this is framework function
        //$this->notify->player($this->getActivePlayerId(), "resetInterfaceWithAllDatas", "", $newGameDatas); // this is notification to reset all data
        $this->notify->all("message", "setup is done", []);
        $this->notify->all("undoRestorePoint", "", []);
        $this->gamestate->jumpToState(StateConstants::STATE_GAME_DISPATCH);
    }

    function debug_dumpMachineDb() {
        $t = $this->machine->gettablearr();
        $this->debugLog("all stack " . ($t[0]["type"] ?? "halt"), $t);
        return $t;
    }
    function debugConsole($info, $args = []) {
        $this->notify->all("log", $info, $args);
        $this->warn($info);
    }
    function debugLog($info, $args = []) {
        $this->notify->all("log", "", ["log" => $info, "args" => $args]);
        //$this->warn($info . ": " . toJson($args));
    }
}
