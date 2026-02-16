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
use Bga\Games\wayfarers\OpCommon\AiOperation;
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
        $token_types = $this->material->get();
        // Shuffle the Journal Tiles and place one faceup on each empty space of the Journal Track.
        $this->tokens->db->shuffle("deck_jtile");
        foreach ($token_types as $key => $info) {
            if (str_starts_with($key, "jpos_")) {
                $r = $this->getRulesFor($key, "r", "");
                if (str_contains($r, "jtile")) {
                    $this->tokens->db->pickTokensForLocation(1, "deck_jtile", $key);
                }
                // Place 1 Green Worker on each indicated space along the Journal Track.
                $r = $this->getRulesFor($key, "gw", "");
                if ($r) {
                    $this->tokens->db->moveToken("worker_green_$r", $key);
                }
            }
        }

        foreach ($token_types as $key => $info) {
            // Only process upgrade tiles
            if (!getPart($key, 2, true)) {
                continue;
            }
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
        $startingPlayer = (int) $this->getActivePlayerId();
        $p = $this->getPlayerIdsInOrder($startingPlayer);
        $pboards = [1, 2, 3, 4];
        shuffle($pboards);

        foreach ($p as $player_id) {
            $color = $this->custom_getPlayerColorById($player_id);
            // 1 Player Board (randomly assigned).
            $boardnum = array_shift($pboards);
            $this->setupPlayerBord($player_id, $boardnum);
            // 1 Player Marker in their chosen color (place it on the far-left end of the Journal Track).
            $this->tokens->db->moveToken("marker_$color", "mainarea", 0);
            // 1 Yellow Worker and 1 Blue Worker.
            $this->tokens->db->moveToken("worker_blue_{$i}", "tableau_$color", 0);
            $this->tokens->db->moveToken("worker_yellow_{$i}", "tableau_$color", 0);

            if ($i <= 2) {
                $this->effect_incCount($color, "coin", 3, "setup");
            } else {
                $this->effect_incCount($color, "coin", 4, "setup");
            }
            $this->effect_incCount($color, "food", 2, "setup");
            $this->tokens->dbSetTokenLocation("influence_{$color}_1", "guild_blue", 0, "*", [], $player_id);
            if ($i > 1) {
                $this->tokens->dbSetTokenLocation("influence_{$color}_2", "guild_yellow", 0, "*", [], $player_id);
            }
            // 5 Dice in their chosen color (roll 3 and place them next to their Player Board; keep 2 in reserve near the Minarets on the Main Board).
            // 15 Influence tokens in their chosen color.
            for ($j = 1; $j <= 3; $j++) {
                $newValue = bga_rand(1, 6);
                $this->tokens->db->setTokenState("dice_{$color}_{$j}", $newValue);
            }
            $this->tokens->db->moveToken("dice_{$color}_4", "supply");
            $this->tokens->db->moveToken("dice_{$color}_5", "supply");
            $i++;
        }

        // Solo mode: set up AI opponent
        if ($this->isSolo()) {
            $this->setupSolo();
        }

        $this->machine->queue("turn", $this->custom_getPlayerColorById($startingPlayer));
        return GameDispatch::class;
    }

    function setupSolo() {
        $i = 2; // solo AI is always 2nd
        $color = $this->getAutomaColor();

        // Assign AI a player board
        $boardnum = bga_rand(1, 4);
        $this->tokens->db->createToken("pboard_$color", "tableau_$color", -$boardnum);

        // Place AI marker on starting space of Journal Track
        $this->tokens->db->createToken("marker_{$color}", "mainarea", 0);

        // Give AI 1 Yellow Worker and 1 Blue Worker
        $this->tokens->db->moveToken("worker_blue_{$i}", "tableau_$color", 0);
        $this->tokens->db->moveToken("worker_yellow_{$i}", "tableau_$color", 0);

        // AI starts with 1 Influence in Yellow Guild and 1 in Blue Guild (no provisions/silver)
        $this->tokens->db->createTokensPack("influence_{$color}_{INDEX}", "tableau_$color", 45, 1);
        $this->tokens->dbSetTokenLocation("influence_{$color}_1", "guild_blue", 0, "*", [], self::PLAYER_AUTOMA);
        $this->tokens->dbSetTokenLocation("influence_{$color}_2", "guild_yellow", 0, "*", [], self::PLAYER_AUTOMA);

        // Create AI resource track marker (position 0 = top-left, values 0-7)
        $this->tokens->db->createToken("tracker_res_$color", "tableau_$color", 0);
        // Create AI comet track marker (position 0, values 0-10)
        $this->tokens->db->createToken("tracker_comet_$color", "tableau_$color", 0);

        $this->tokens->db->createToken("tracker_vp_$color", "miniboard_$color", 0);

        // Shuffle scheme cards
        $this->tokens->db->shuffle("deck_scheme");
    }

    public function getDefaultStatValue(string $key, string $type): ?int {
        if (startsWith($key, "game_vp_ai")) {
            return $this->isSolo() ? 0 : null;
        }
        if (startsWith($key, "game_")) {
            return 0;
        } elseif ($key === "turns_number") {
            return 0;
        }
        return null;
    }

    function setupPlayerBord(int $player_id, int $boardnum) {
        $color = $this->custom_getPlayerColorById($player_id);
        $this->tokens->db->setTokenState("tableau_$color", $boardnum);
        $this->tokens->db->setTokenState("pboard_$color", $boardnum);
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

        $players = $this->loadPlayersBasicInfosWithBots();

        foreach ($players as $player_id => $player) {
            foreach ($player as $pkey => $value) {
                $key = str_replace("player_", "", $pkey);
                $result["playerswithbots"][$player_id][$key] = $value;
            }
        }

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

    function getUserPreference(int $player_id, int $code): int {
        return (int) $this->userPreferences->get($player_id, $code);
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
            $this->custom_getPlayerIdByColor($color)
        );

        if ($value < 0 && $inc < 0) {
            $this->userAssert(clienttranslate("Insufficient resources to pay"));
        }
    }

    function effect_incVp(string $owner, int $inc, string $stat = "", string $target = "") {
        $player_id = $this->custom_getPlayerIdByColor($owner);

        if ($target) {
            if ($inc < 0) {
                $message = clienttranslate('${player_name} loses ${absInc} VP for ${token_name} ${reason}');
            } else {
                // if 0 print gain 0
                $message = clienttranslate('${player_name} gains ${absInc} VP for ${token_name} ${reason}');
            }
        } else {
            if ($inc < 0) {
                $message = clienttranslate('${player_name} loses ${absInc} VP ${reason}');
            } else {
                // if 0 print gain 0
                $message = clienttranslate('${player_name} gains ${absInc} VP ${reason}');
            }
        }

        if ($player_id == self::PLAYER_AUTOMA) {
            // automa cannot use same scoring mechanism, use custom tracker
            $trackerId = $this->tokens->getTrackerId($owner, "vp");
            $this->tokens->dbResourceInc($trackerId, $inc, $message, ["reason" => $stat, "token_name" => $target], self::PLAYER_AUTOMA);
            if ($stat) {
                if (!str_starts_with($stat, "game_vp_ai_")) {
                    $stat = str_replace("game_vp_", "game_vp_ai_", $stat);
                }
                $this->tableStats->inc($stat, $inc, $player_id);
            }

            return;
        }

        $this->playerScore->inc(
            $player_id,
            $inc,
            new NotificationMessage($message, [
                "reason" => $stat,
                "target" => $target,
                "token_name" => $target,
            ])
        );

        if ($stat) {
            $this->playerStats->inc($stat, $inc, $player_id);
        }
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
    function getRulesForAndAssert($token_id, $field = "r", $default = "") {
        $r = $this->material->getRulesFor($token_id, $field, $default);
        $this->systemAssert("Expected non empty rule for for $token_id:$field", $r);
        return $r;
    }
    function getTokenName($token_id, $default = "") {
        if (!$default) {
            $default = "$token_id ?";
        }
        $name = $this->material->getRulesFor($token_id, "nom", null);
        if ($name) {
            return $name;
        }
        $name = $this->material->getRulesFor($token_id, "name", $default);

        return $name;
    }

    function getTrackerIdAndValue(?string $color, string $type, ?array &$arr = null) {
        return $this->tokens->getTrackerIdAndValue($color, $type, $arr);
    }

    function getTagsSet(string $card) {
        $tags = $this->getRulesFor($card, "tags", "");
        $tagsarr = explode(" ", $tags);
        $res = [];
        foreach ($tagsarr as $tag) {
            if ($tag) {
                $res[$tag] = ($res[$tag] ?? 0) + 1;
            }
        }
        return $res;
    }
    /**
     * Count tags of a specific type for a player
     * @param string $tagName - the tag to count (City, Vista, Harbour, Open Water, etc.)
     * @param string $owner - player color
     * @return int - count of tags
     */
    function countPlayerTags(string $tagName, string $owner): int {
        $count = 0;

        $cardTypes = ["card_land", "card_water", "card_space", "card_home"];
        foreach ($cardTypes as $cardType) {
            $cards = $this->tokens->getTokensOfTypeInLocation($cardType, "tableau_$owner");
            foreach ($cards as $cardKey => $cardInfo) {
                $tags = $this->getTagsSet($cardKey);
                if (isset($tags[$tagName])) {
                    $count += $tags[$tagName];
                }
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

        // Capital star (pre-printed)
        $tags = $this->getTagsSet("card_space_1");
        if (isset($tags[$tagName])) {
            $count += $tags[$tagName];
        }

        return $count;
    }

    /**
     * Check if any Vista cards in the player's tableau are triggered by a newly played item.
     * Vista cards have a "trig" field that matches tags on incoming cards/upgrades.
     * @return array - [vistaCardKey => drRule] pairs for each triggered vista card
     */
    function getVistaTriggeredRules(string $playedItem, string $owner): array {
        $tags = $this->getTagsSet($playedItem);

        // Add implicit tags based on token type
        if (str_starts_with($playedItem, "card_folk")) {
            $tags["card_folk"] = 1;
        }
        if (str_starts_with($playedItem, "upg_")) {
            $tags["upg_any"] = 1;
        }

        $results = [];
        $cards = $this->tokens->getTokensOfTypeInLocation("card_land", "tableau_$owner");
        foreach ($cards as $cardKey => $cardInfo) {
            if ($cardKey === $playedItem) {
                continue;
            } // Don't trigger self

            $trig = $this->getRulesFor($cardKey, "trig", "");
            if (!$trig) {
                continue;
            }

            // trig can be "Planet/Sun/Moon" meaning OR
            $trigParts = explode("/", $trig);
            foreach ($trigParts as $trigTag) {
                if (isset($tags[trim($trigTag)])) {
                    $dr = $this->getRulesFor($cardKey, "dr", "");
                    if ($dr) {
                        $results[$cardKey] = $dr;
                    }
                    break;
                }
            }
        }
        return $results;
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

    function countFolk(string $owner): int {
        $tokens = $this->tokens->getTokensOfTypeInLocation("card_folk", "tableau_{$owner}");
        return count($tokens) + 1; // +1 because one is pre-printed
    }

    /**
     * Count influence tokens in a guild for a player
     */
    function countGuildInfluence(string $guild, string $owner): int {
        $tokens = $this->tokens->getTokensOfTypeInLocation("influence_{$owner}", $guild);
        return count($tokens);
    }

    /**
     * Check if a player has achieved an inspiration card's goal
     * @param string $cardKey - the inspiration card key (e.g., "card_insp_1")
     * @param string $owner - player color
     * @return bool - true if goal is achieved
     */
    function isInspirationGoalAchieved(string $cardKey, string $owner): bool {
        $collect = $this->getRulesFor($cardKey, "collect", "");
        $required = (int) $this->getRulesFor($cardKey, "goal", 0);

        if (!$collect || $required <= 0) {
            return false;
        }

        // Single requirement
        $count = $this->evaluateExpression($collect, $owner);
        return $count >= $required;
    }

    function countVpForSpaceCard(string $card, string $owner) {
        $vpexp = $this->getRulesFor($card, "vpexp", 0);
        return $this->evaluateExpression($vpexp, $owner);
    }

    /**
     * Get assets available for a specific die value from the caravan.
     * The caravan is a 6x3 grid where each column (0-5) corresponds to die values (1-6).
     * Starting assets: camel at column 0 (die 1), telescope at column 5 (die 6).
     *
     * @param int $dieValue - the die value (1-6)
     * @param string $owner - player color
     * @return array - associative array of assets with counts (e.g., ["camel" => 1, "ship" => 2])
     */
    function getCaravanAssetsForDie(int $dieValue, string $owner): array {
        $assets = [];
        // Assets are: camel, ship, pigeon, telescope
        $assetTypes = ["camel", "ship", "pigeon", "telescope"];
        foreach ($assetTypes as $assetType) {
            $assets[$assetType] = 0;
        }
        if (!$dieValue) {
            return $assets;
        }
        $column = $dieValue - 1; // Convert die value (1-6) to column index (0-5)

        // Starting assets (hardcoded positions in caravan)
        if ($column === 0) {
            $assets["camel"] = 1;
        }
        if ($column === 5) {
            $assets["telescope"] = 1;
        }

        // Get upgrade tiles in player's caravan
        $tiles = $this->tokens->getTokensOfTypeInLocation("upg", "tableau_$owner");

        foreach ($tiles as $tileKey => $tileInfo) {
            $state = $tileInfo["state"];
            if ($state <= 0) {
                continue; // Not placed in caravan
            }

            // State encodes position: state = x + y * 6 + 1
            $pos = $state - 1;
            $tileX = $pos % 6;

            // Get tile dimensions
            $w = (int) $this->getRulesFor($tileKey, "w", 1);

            // Check if this tile covers the target column
            if ($tileX <= $column && $column < $tileX + $w) {
                // For 2x1 tiles: r is left column, r2 is right column
                // For 1x1 or 1x2 tiles: only r applies
                $columnOffset = $column - $tileX; // 0 = left column, 1 = right column

                if ($w === 2) {
                    // 2x1 tile: use r for left column (offset 0), r2 for right column (offset 1)
                    $ruleKey = $columnOffset === 0 ? "r" : "r2";
                } else {
                    // 1x1 or 1x2 tile: just use r
                    $ruleKey = "r";
                }

                // Parse assets from the rule field
                $ruleField = $this->getRulesFor($tileKey, $ruleKey, "");
                $this->updateMatchingAssetsFromRule($ruleField, $assets);
            }
        }

        return $assets;
    }

    function updateMatchingAssetsFromRule(string|null $rule, array &$assets) {
        if (!$rule) {
            return;
        }
        $subrules = explode(",", $rule);
        foreach ($subrules as $single) {
            $single = trim($single);
            if (!$single) {
                continue;
            }
            $assets[$single] = ($assets[$single] ?? 0) + 1;
        }
    }

    /**
     * Check if available assets meet requirements
     * @param string $requirements - comma-separated list of required assets (e.g., "camel,ship")
     * @param array $availableAssets - associative array of asset => count
     * @return array - list of missing assets (empty array if all requirements met)
     */
    function getMissingAssetRequirements(string $requirements, array $available): array {
        // Empty or "any" means any die can be placed - no specific assets required
        if ($requirements === "" || $requirements === "any") {
            return [];
        }

        $required = explode(",", $requirements);
        $missing = [];

        foreach ($required as $asset) {
            $asset = trim($asset);
            if (empty($asset)) {
                continue;
            }

            if ($available[$asset] > 0) {
                $available[$asset]--;
            } else {
                $missing[] = $asset;
            }
        }

        return $missing;
    }

    function finalScoring() {
        $players = $this->loadPlayersBasicInfos();
        $guildInfluence = []; // Track influence per guild per player for majority

        foreach ($players as $player_id => $player) {
            $color = $this->custom_getPlayerColorById((int) $player_id);

            // 1. Primary Land and Water Tags (City, Vista, Harbour, Open Water)
            $primaryTags = ["City", "Vista", "Harbour", "Sea"];
            $tagCounts = [];
            $vp_tags = 0;
            foreach ($primaryTags as $tag) {
                $count = $this->countPlayerTags($tag, $color);
                $tagCounts[$tag] = $count;
                $vp = $this->getTagVP($count);
                $this->effect_incVp($color, $vp, "", "game_vp_tag_$tag");
                $vp_tags += $vp;
            }
            $this->playerStats->inc("game_vp_tags", $vp_tags, $player_id);

            // Sets bonus: 5 VP for each set of 4 unique primary tags
            $sets = min($tagCounts);
            if ($sets > 0) {
                $this->effect_incVp($color, $sets * 5, "game_vp_sets");
            }

            // 2. Space and Inspriration Cards VP
            $cards = $this->tokens->getTokensOfTypeInLocation("card_space", "tableau_$color");
            $inspCards = $this->tokens->getTokensOfTypeInLocation("card_insp", "tableau_$color");

            foreach ($cards as $cardKey => $cardInfo) {
                $vp = $this->countVpForSpaceCard($cardKey, $color);

                $this->effect_incVp($color, $vp, "game_vp_space", $cardKey);
                if (!$vp) {
                    continue;
                }
                // Check if there's a tucked inspiration card at the same position
                $spacePos = (int) $cardInfo["state"];
                foreach ($inspCards as $inspKey => $inspInfo) {
                    $inspPos = (int) $inspInfo["state"];
                    if ($inspPos === $spacePos) {
                        // Found tucked inspiration card, check if goal is achieved
                        if ($this->isInspirationGoalAchieved($inspKey, $color)) {
                            // Goal achieved - score space card VP again
                            $this->effect_incVp($color, $vp, "game_vp_insp", $inspKey);
                        } else {
                            $this->notifyMessage(
                                clienttranslate('${player_name} did not achieve the goal for tucked inspiration card ${token_name}'),
                                ["token_name" => $this->getTokenName($inspKey)],
                                $player_id
                            );
                        }
                        break; // Only one inspiration card per space card
                    }
                }
            }

            // 3. Caravan - VP from upgrade tiles
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

        if ($this->isSolo()) {
            $color = $this->getAutomaColor();

            // The AI scores VP for the following: 1VP per acquired Townsfolk
            // Card; 2VP per acquired Water/Land Card; 3VP per acquired Space
            // Card; 4VP per acquired Inspiration Card;
            $cardTypes = ["folk", "land", "water", "space", "insp"];
            foreach ($cardTypes as $type) {
                $cards = $this->tokens->getTokensOfTypeInLocation("card_$type", "tableau_$color");
                $c = count($cards);
                $vpc = AiOperation::getCardTypeVP($type);
                $vp = $vpc * $c;
                $stat = "game_vp_ai_$type";
                if ($type == "land" || $type == "water") {
                    $stat = "game_vp_ai_cards";
                }
                $this->effect_incVp($color, $vp, $stat);
            }
            //VP from acquired Upgrade Tiles;
            $tiles = $this->tokens->getTokensOfTypeInLocation("upg", "tableau_$color");
            foreach ($tiles as $tileKey => $tileInfo) {
                $vp = (int) $this->getRulesFor($tileKey, "vp", 0);
                if ($vp > 0) {
                    $this->effect_incVp($color, $vp, "game_vp_ai_caravan");
                }
            }
            //VP from Guild Majorities.
            foreach (["guild_black", "guild_yellow", "guild_blue"] as $guild) {
                $guildInfluence[$guild][$color] = $this->countGuildInfluence($guild, $color);
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

            $this->notifyMessage(clienttranslate('Scoring majority for ${token_name}, max influence ${max}'), [
                "token_name" => $guild,
                "max" => $maxInfluence,
            ]);

            // Only award VP if there's a single winner (no ties)
            if (count($winners) == 1 && $maxInfluence > 0) {
                $this->effect_incVp($winners[0], 3, "game_vp_guilds");
            } else {
                // add notify message that vp is no awarded because of tie
                $this->notifyMessage(clienttranslate('No VP awarded for ${token_name} due to tie'), ["token_name" => $guild]);
            }
        }

        // Set tiebreaker: Black Influence, then Yellow, then Blue
        foreach ($players as $player_id => $player) {
            $color = $this->custom_getPlayerColorById((int) $player_id);
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

        if ($this->isSolo()) {
            // notify with total of automa scope
            $score = $this->tokens->getTrackerValue($this->getAutomaColor(), "vp");
            $player_id = self::PLAYER_AUTOMA;
            $this->notifyMessage(clienttranslate('${player_name} gets total score of ${points}'), ["points" => $score], $player_id);
            // TODO: if Automa wins negate the score of player
        }

        $this->notify->all("endScores", "", ["endScores" => $this->getEndScores(), "final" => true]);
    }

    function evaluateTerm($x, $owner, $context = null, ?array $options = null) {
        if ($x === "true") {
            return 1;
        }
        if (str_starts_with($x, "tracker_")) {
            return $this->tokens->getTrackerValue($owner, getPart($x, 1));
        }

        // Handle tag_upg_ (upgrade tiles) - MUST come before generic tag_ check
        if (str_starts_with($x, "tag_upg_")) {
            $type = getPart($x, 2);
            if ($type === "any") {
                $tokens = $this->tokens->getTokensOfTypeInLocation("upg", "tableau_$owner");
            } else {
                $tokens = $this->tokens->getTokensOfTypeInLocation("upg_$type", "tableau_$owner");
            }
            return count($tokens);
        }

        // Handle tag_card_ (card counts) - MUST come before generic tag_ check
        if (str_starts_with($x, "tag_card_")) {
            // i.e. tag_card_land
            $ttype = getPart($x, 2);
            $tokens = $this->tokens->getTokensOfTypeInLocation("card_$ttype", "tableau_$owner");

            $plus = 0;
            if ($ttype === "folk" || $ttype == "land" || $ttype == "water" || $ttype == "star") {
                $plus = 1; // player starts with 1 of other pre-printed cards
            }
            return count($tokens) + $plus;
        }

        // Handle generic tag_ (actual game tags like City, Vista, etc.)
        if (str_starts_with($x, "tag_")) {
            return $this->countPlayerTags(getPart($x, 1), $owner);
        }
        if (str_starts_with($x, "inf_")) {
            return $this->countGuildInfluence(getPart($x, 1), $owner);
        }

        if (str_starts_with($x, "win_")) {
            // check if player has more Comet tags than all individual opponents
            $tag = getPart($x, 1);
            $tagCount = $this->countPlayerTags($tag, $owner);
            $players = $this->loadPlayersBasicInfos();
            foreach ($players as $player_id => $player) {
                $color = $this->custom_getPlayerColorById((int) $player_id);
                if ($color === $owner) {
                    continue;
                }
                $oppCount = $this->countPlayerTags($tag, $color);
                if ($oppCount >= $tagCount) {
                    return 0;
                }
            }
            return 1;
        }

        return parent::evaluateTerm($x, $owner, $context, $options);
    }

    function getEndScores(): array {
        $endScores = [];
        $players = $this->loadPlayersBasicInfos();
        $vp_stats = ["game_vp_tags", "game_vp_sets", "game_vp_space", "game_vp_insp", "game_vp_caravan", "game_vp_guilds"];

        foreach ($players as $player_id => $player) {
            foreach ($vp_stats as $stat) {
                $endScores[$player_id][$stat] = $this->playerStats->get($stat, $player_id);
            }
            $endScores[$player_id]["total"] = $this->playerStats->get("game_vp_total", $player_id);
        }

        if ($this->isSolo()) {
            $color = $this->getAutomaColor();
            $player_id = self::PLAYER_AUTOMA;

            $vp_stats_ai = [
                "game_vp_ai_folk" => "game_vp_sets",
                "game_vp_ai_cards" => "game_vp_tags", // TODO: sort out mapping later
                "game_vp_ai_space" => "game_vp_space",
                "game_vp_ai_insp" => "game_vp_insp",
                "game_vp_ai_caravan" => "game_vp_caravan",
                "game_vp_ai_guilds" => "game_vp_guilds",
            ];
            foreach ($vp_stats_ai as $stat => $mapstat) {
                $endScores[$player_id][$mapstat] = $this->tableStats->get($stat, $player_id);
            }
            $endScores[$player_id]["total"] = $this->tokens->getTrackerValue($color, "vp");
        }

        return $endScores;
    }

    /**
     * Trigger end of game condition
     * All players including the one who triggered get one more turn
     */
    function triggerEndGame(int $playerId): void {
        $gameStage = $this->tokens->db->getTokenState(Game::GAME_STAGE);

        // Only trigger if not already triggered (game_stage < 1 means not triggered yet)
        if ($gameStage < 1) {
            // Store the triggering player's number (1-4) in game_stage
            // This marks who triggered it so we know when to end after they complete their final turn

            $playerNo = $this->game->custom_getPlayerNoById($playerId);

            $this->tokens->dbSetTokenState(
                Game::GAME_STAGE,
                $playerNo,
                clienttranslate('${player_name} triggers end of game! All players get one more turn.')
            );

            // TODO: send special notification to clients to show end game banner
        }
    }

    /**
     * Queue the next turn, or end the game if this was the final turn.
     * When end game is triggered, game_stage holds the player number (1-4) who triggered it.
     * After that player completes their turn (everyone got their final turn), set game_stage to 5.
     */
    function queueNextTurnOrEnd(int $playerId): void {
        $gameStage = $this->tokens->db->getTokenState(Game::GAME_STAGE);

        // If end game was triggered (game_stage = 1-4)
        if ($gameStage >= 1 && $gameStage <= 4) {
            $triggeringPlayerNo = $gameStage;
            $currentPlayerNo = $this->custom_getPlayerNoById($playerId);

            // If the current player is the one who triggered end game,
            // that means everyone has had their final turn - end the game
            if ($currentPlayerNo == $triggeringPlayerNo) {
                $this->machine->queue("finalScoring");
                // Don't queue another turn - game will end
                return;
            }
        }

        // Continue with the next turn
        $nextPlayerId = $this->getNextReadyPlayerId($playerId);
        $this->systemAssert("loop", $nextPlayerId != $playerId);
        $this->machine->queue("turn", $this->custom_getPlayerColorById($nextPlayerId));
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
        $owner = $this->custom_getPlayerColorById($player_id);
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

    function debug_op(string $type) {
        $color = $this->getPlayerColorById((int) $this->getCurrentPlayerId());
        $this->machine->push($type, $color);
        $this->gamestate->jumpToState(StateConstants::STATE_GAME_DISPATCH);
    }

    function debug_ai_op(string $type) {
        $this->machine->push($type, "ffffff");
        $this->gamestate->jumpToState(StateConstants::STATE_GAME_DISPATCH);
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
        $color = $this->getPlayerColorById((int) $this->getCurrentPlayerId());

        $this->machine->push("5food", $color);
        $this->machine->push("5coin", $color);

        $this->gamestate->jumpToState(StateConstants::STATE_GAME_DISPATCH);
    }

    function debug_setupGameTables() {
        $this->DbQuery("DELETE FROM token");
        $this->DbQuery("DELETE FROM machine");
        $this->DbQuery("DELETE FROM multiundo");
        $this->DbQuery("DELETE FROM `stats`");
        $this->DbQuery("DELETE FROM `gamelog`");
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

    function debug_eval(string $x) {
        $color = $this->getPlayerColorById((int) $this->getCurrentPlayerId());
        $v = $this->evaluateExpression($x, $color);
        $this->notify->all("log", "result: $v");
    }
}
