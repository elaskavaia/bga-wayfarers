<?php

declare(strict_types=1);

namespace Bga\Games\wayfarers;

class Material {
    const RET_OK = 0;
    const ERR_COST = 1;
    const ERR_PREREQ = 2;
    const ERR_OCCUPIED = 3;
    const ERR_MAX = 4;
    const ERR_NONE_LEFT = 5;
    const ERR_NOT_APPLICABLE = 6;
    const ERR_NO_PLACE = 7;

    const MA_PREF_CONFIRM_TURN = 101;

    private array $token_types;
    private bool $adjusted = false;
    public function __construct() {
        $this->token_types = [
            // #error codes - MANUAL ENTRY
            "err_0" => [
                //
                "code" => Material::RET_OK,
                "type" => "err",
                "name" => clienttranslate("Ok"),
            ],
            "err_1" => [
                //
                "code" => Material::ERR_COST,
                "type" => "err",
                "name" => clienttranslate("Insufficient Resources"),
            ],

            "err_2" => [
                //
                "code" => Material::ERR_PREREQ,
                "type" => "err",
                "name" => clienttranslate("Prerequisites are not fulfilled"),
            ],
            "err_3" => [
                //
                "code" => Material::ERR_OCCUPIED,
                "type" => "err",
                "name" => clienttranslate("Location is occupied"),
            ],
            "err_4" => [
                //
                "code" => Material::ERR_MAX,
                "type" => "err",
                "name" => clienttranslate("Maximum capacity is reached"),
            ],
            "err_5" => [
                //
                "code" => Material::ERR_NONE_LEFT,
                "type" => "err",
                "name" => clienttranslate("None left"),
            ],

            "err_6" => [
                //
                "code" => Material::ERR_NOT_APPLICABLE,
                "type" => "err",
                "name" => clienttranslate("Not applicable"),
            ],

            "err_7" => [
                //
                "code" => Material::ERR_NO_PLACE,
                "type" => "err",
                "name" => clienttranslate("Not valid placement"),
            ],

            /* --- gen php begin loc_material --- */
    "deck_folk" => [ 
        "type" => "location",
        "create" => 0,
        "name" => clienttranslate("Townsfolk Deck"),
        "location" => "supply",
        "scope" => "global",
        "counter" => "public",
        "content" => "hidden",
],
    "deck_land" => [ 
        "type" => "location",
        "create" => 0,
        "name" => clienttranslate("Land Deck"),
        "location" => "supply",
        "scope" => "global",
        "counter" => "public",
        "content" => "hidden",
],
    "deck_water" => [ 
        "type" => "location",
        "create" => 0,
        "name" => clienttranslate("Water Deck"),
        "location" => "supply",
        "scope" => "global",
        "counter" => "public",
        "content" => "hidden",
],
    "deck_space" => [ 
        "type" => "location",
        "create" => 0,
        "name" => clienttranslate("Space Deck"),
        "location" => "supply",
        "scope" => "global",
        "counter" => "public",
        "content" => "hidden",
],
    "deck_insp" => [ 
        "type" => "location",
        "create" => 0,
        "name" => clienttranslate("Inspiration Deck"),
        "location" => "supply",
        "scope" => "global",
        "counter" => "public",
        "content" => "hidden",
],
    "limbo" => [ 
        "type" => "location",
        "create" => 0,
        "showtooltip" => 0,
        "name" => clienttranslate("Limbo"),
        "scope" => "global",
        "counter" => "hidden",
        "content" => "hidden",
],
    "tableau" => [ 
        "type" => "location",
        "create" => 0,
        "showtooltip" => 0,
        "name" => clienttranslate("Player Area"),
        "location" => "players_panels",
        "scope" => "player",
        "counter" => "hidden",
        "content" => "public",
],
    "hand" => [ 
        "type" => "location",
        "create" => 0,
        "showtooltip" => 0,
        "name" => clienttranslate("Player Hand"),
        "location" => "players_panels",
        "scope" => "player",
        "counter" => "hidden",
        "content" => "private",
],
    "supply" => [ 
        "type" => "location",
        "create" => 0,
        "showtooltip" => 0,
        "name" => clienttranslate("Supply"),
        "location" => "supply",
        "scope" => "global",
        "counter" => "public",
        "content" => "public",
],
    "guild_black" => [ 
        "type" => "location",
        "create" => 0,
        "showtooltip" => 0,
        "name" => clienttranslate("Black Guild (Science)"),
        "location" => "mainarea",
        "scope" => "global",
        "counter" => "hidden",
        "content" => "public",
],
    "guild_blue" => [ 
        "type" => "location",
        "create" => 0,
        "showtooltip" => 0,
        "name" => clienttranslate("Blue Guild (Exploration)"),
        "location" => "mainarea",
        "scope" => "global",
        "counter" => "hidden",
        "content" => "public",
],
    "guild_yellow" => [ 
        "type" => "location",
        "create" => 0,
        "showtooltip" => 0,
        "name" => clienttranslate("Yellow Guild (Trade)"),
        "location" => "mainarea",
        "scope" => "global",
        "counter" => "hidden",
        "content" => "public",
],
            /* --- gen php end loc_material --- */
            /* --- gen php begin op_material --- */
    "Op_nop" => [ 
        "type" => "nop",
        "name" => clienttranslate("None"),
],
// #fake name
    "Op_barrier" => [ 
        "type" => "barrier",
        "name" => clienttranslate("None"),
],
    "Op_savepoint" => [ 
        "type" => "savepoint",
        "name" => clienttranslate("None"),
],
    "Op_or" => [ 
        "type" => "or",
        "name" => clienttranslate("Choice"),
],
    "Op_unique" => [ 
        "type" => "unique",
        "name" => clienttranslate("Unique Choice"),
],
    "Op_order" => [ 
        "type" => "order",
        "name" => clienttranslate("Choose Order"),
],
    "Op_seq" => [ 
        "type" => "seq",
        "name" => clienttranslate("Sequence"),
],
    "Op_gain" => [ 
        "type" => "gain",
        "name" => clienttranslate("Gain"),
],
    "Op_pay" => [ 
        "type" => "pay",
        "name" => clienttranslate("Pay"),
],
    "Op_paygain" => [ 
        "type" => "paygain",
        "name" => clienttranslate("Trade"),
],
    "Op_turn" => [ 
        "type" => "turn",
        "name" => clienttranslate("Turn"),
],
    "Op_turnconf" => [ 
        "type" => "turnconf",
        "name" => clienttranslate("Confirm Turn"),
],
    "Op_rest" => [ 
        "type" => "rest",
        "name" => clienttranslate("Rest"),
],
    "Op_placeWorker" => [ 
        "type" => "placeWorker",
        "name" => clienttranslate("Place Worker"),
],
    "Op_placeDie" => [ 
        "type" => "placeDie",
        "name" => clienttranslate("Place Die"),
],
    "Op_spendInfBlue" => [ 
        "type" => "spendInfBlue",
        "name" => clienttranslate("Spend Blue Influence for Ship"),
],
    "Op_spendInfYellow" => [ 
        "type" => "spendInfYellow",
        "name" => clienttranslate("Spend Yellow Influence to Modify Dice"),
],
    "Op_spendInfBlack" => [ 
        "type" => "spendInfBlack",
        "name" => clienttranslate("Spend Black Influence for Extra Journal"),
],
    "Op_cardSpace" => [ 
        "type" => "cardSpace",
        "name" => clienttranslate("Acquire Space Card"),
],
    "Op_cardFolk" => [ 
        "type" => "cardFolk",
        "name" => clienttranslate("Acquire Townsfolk Card"),
],
    "Op_cardWater" => [ 
        "type" => "cardWater",
        "name" => clienttranslate("Acquire Water Card"),
],
    "Op_cardLand" => [ 
        "type" => "cardLand",
        "name" => clienttranslate("Acquire Land Card"),
],
    "Op_cardDraw" => [ 
        "type" => "cardDraw",
        "name" => clienttranslate("Draw and Pick Card"),
],
    "Op_cardInsp" => [ 
        "type" => "cardInsp",
        "name" => clienttranslate("Acquire Inspiration Card"),
],
    "Op_cardInteract" => [ 
        "type" => "cardInteract",
        "name" => clienttranslate("Card Interaction"),
],
    "Op_ai_cardInteract" => [ 
        "type" => "ai_cardInteract",
        "name" => clienttranslate("AI Card Interaction"),
],
    "Op_upgBlack" => [ 
        "type" => "upgBlack",
        "name" => clienttranslate("Acquire Space Upgrade"),
],
    "Op_upgBlue" => [ 
        "type" => "upgBlue",
        "name" => clienttranslate("Acquire Water Upgrade"),
],
    "Op_upgGreen" => [ 
        "type" => "upgGreen",
        "name" => clienttranslate("Acquire Basic Upgrade"),
],
    "Op_upgPink" => [ 
        "type" => "upgPink",
        "name" => clienttranslate("Gain Special Upgrade"),
],
    "Op_upgYellow" => [ 
        "type" => "upgYellow",
        "name" => clienttranslate("Acquire Land Upgrade"),
],
    "Op_journal" => [ 
        "type" => "journal",
        "name" => clienttranslate("Journal"),
],
    "Op_pickWorker" => [ 
        "type" => "pickWorker",
        "name" => clienttranslate("Pick a Worker"),
],
    "Op_reroll" => [ 
        "type" => "reroll",
        "name" => clienttranslate("Refresh a Die"),
],
    "Op_ship" => [ 
        "type" => "ship",
        "name" => clienttranslate("Ship"),
],
    "Op_camel" => [ 
        "type" => "camel",
        "name" => clienttranslate("Camel"),
],
    "Op_telescope" => [ 
        "type" => "telescope",
        "name" => clienttranslate("Telescope"),
],
    "Op_pigeon" => [ 
        "type" => "pigeon",
        "name" => clienttranslate("Pigeon"),
],
    "Op_infAny" => [ 
        "type" => "infAny",
        "name" => clienttranslate("Influence on any Guild"),
],
    "Op_infBlue" => [ 
        "type" => "infBlue",
        "name" => clienttranslate("Influence on Blue"),
],
    "Op_infYellow" => [ 
        "type" => "infYellow",
        "name" => clienttranslate("Influence on Yellow"),
],
    "Op_infBlack" => [ 
        "type" => "infBlack",
        "name" => clienttranslate("Influence on Black"),
],
    "Op_infCard" => [ 
        "type" => "infCard",
        "name" => clienttranslate("Influence on Card"),
],
    "Op_infMove" => [ 
        "type" => "infMove",
        "name" => clienttranslate("Move Influence"),
],
    "Op_diceMod" => [ 
        "type" => "diceMod",
        "name" => clienttranslate("Modify Dice by +/- 1"),
],
    "Op_dicePlus" => [ 
        "type" => "dicePlus",
        "name" => clienttranslate("Modify Dice by +1"),
],
    "Op_diceMinus" => [ 
        "type" => "diceMinus",
        "name" => clienttranslate("Modify Dice by -1"),
],
    "Op_newDie" => [ 
        "type" => "newDie",
        "name" => clienttranslate("Gain Die"),
],
    "Op_jtile" => [ 
        "type" => "jtile",
        "name" => clienttranslate("Journal Tile Bonus"),
],
    "Op_pickGreen" => [ 
        "type" => "pickGreen",
        "name" => clienttranslate("Pick Green Worker"),
],
    "Op_food" => [ 
        "class" => "Op_gain",
        "type" => "food",
        "name" => clienttranslate("Gain Provision"),
],
    "Op_coin" => [ 
        "class" => "Op_gain",
        "type" => "coin",
        "name" => clienttranslate("Gain Silver"),
],
    "Op_n_food" => [ 
        "class" => "Op_pay",
        "type" => "n_food",
        "name" => clienttranslate("Pay Provision"),
],
    "Op_n_coin" => [ 
        "class" => "Op_pay",
        "type" => "n_coin",
        "name" => clienttranslate("Pay Silver"),
],
    "Op_n_infBlue" => [ 
        "type" => "n_infBlue",
        "name" => clienttranslate("Pay Blue Influence"),
],
    "Op_n_infYellow" => [ 
        "type" => "n_infYellow",
        "name" => clienttranslate("Pay Yellow Influence"),
],
    "Op_n_infBlack" => [ 
        "type" => "n_infBlack",
        "name" => clienttranslate("Pay Black Influence"),
],
    "Op_finalScoring" => [ 
        "type" => "finalScoring",
        "name" => clienttranslate("Final Scoring"),
],
// #ai ops
    "Op_ai_turn" => [ 
        "type" => "ai_turn",
        "name" => clienttranslate("AI Turn"),
],
    "Op_ai_rest" => [ 
        "type" => "ai_rest",
        "name" => clienttranslate("AI Rest"),
],
    "Op_ai_cardLand" => [ 
        "class" => "Op_ai_cardBase",
        "type" => "ai_cardLand",
        "name" => clienttranslate("AI Acquire Land Card"),
],
    "Op_ai_cardWater" => [ 
        "class" => "Op_ai_cardBase",
        "type" => "ai_cardWater",
        "name" => clienttranslate("AI Acquire Water Card"),
],
    "Op_ai_cardSpace" => [ 
        "class" => "Op_ai_cardBase",
        "type" => "ai_cardSpace",
        "name" => clienttranslate("AI Acquire Space Card"),
],
    "Op_ai_cardFolk" => [ 
        "class" => "Op_ai_cardBase",
        "type" => "ai_cardFolk",
        "name" => clienttranslate("AI Acquire Townsfolk Card"),
],
    "Op_ai_cardInsp" => [ 
        "class" => "Op_ai_cardBase",
        "type" => "ai_cardInsp",
        "name" => clienttranslate("AI Acquire Inspiration Card"),
],
    "Op_ai_upgAny" => [ 
        "type" => "ai_upgAny",
        "name" => clienttranslate("AI Acquire Upgrade Tile"),
],
    "Op_ai_upgPink" => [ 
        "type" => "ai_upgPink",
        "name" => clienttranslate("AI Acquire Pink Upgrade Tile"),
],
    "Op_ai_journal" => [ 
        "type" => "ai_journal",
        "name" => clienttranslate("AI Journal"),
],
    "Op_ai_shuffle" => [ 
        "type" => "ai_shuffle",
        "name" => clienttranslate("AI Shuffle"),
],
    "Op_ai_res" => [ 
        "type" => "ai_res",
        "name" => clienttranslate("AI Resource Track"),
],
    "Op_ai_placeWorker" => [ 
        "type" => "ai_placeWorker",
        "name" => clienttranslate("AI Place Worker"),
],
    "Op_ai_pickWorker" => [ 
        "type" => "ai_pickWorker",
        "name" => clienttranslate("AI Pick Worker"),
],
    "Op_ai_infCard" => [ 
        "type" => "ai_infCard",
        "name" => clienttranslate("AI Influence on Card"),
],
    "Op_ai_focusAction" => [ 
        "type" => "ai_focusAction",
        "name" => clienttranslate("AI Focus Action"),
],
    "Op_ai_comet" => [ 
        "type" => "ai_comet",
        "name" => clienttranslate("AI Comet Track"),
],
            /* --- gen php end op_material --- */
            /* --- gen php begin token_material --- */
// # create is one of the numbers
// # 0 - do not create token
// # 1 - the token with id $id will be created, count must be set to 1 if used
// # 2 - the token with id "${id}_{INDEX}" will be created, using count starting from 1
// # 3 - the token with id "${id}_{COLOR}_{INDEX}" will be created, using count, per player
// # 4 - the token with id "${id}_{COLOR}" for each player will be created, count must be 1
// # 5 - the token with id "${id}_{INDEX}_{COLOR}" for each player will be created
// # 6 - custom placeholders
// #12  Workers 4 of each 3 colors
    "worker_blue" => [ 
        "name" => clienttranslate("Blue Worker"),
        "count" => 4,
        "type" => "worker wooden",
        "create" => 2,
        "location" => "limbo",
],
    "worker_yellow" => [ 
        "name" => clienttranslate("Yellow Worker"),
        "count" => 4,
        "type" => "worker wooden",
        "create" => 2,
        "location" => "limbo",
],
    "worker_green" => [ 
        "name" => clienttranslate("Green Worker"),
        "count" => 4,
        "type" => "worker wooden",
        "create" => 2,
        "location" => "limbo",
],
// #player counters
    "tracker_coin" => [ 
        "name" => clienttranslate("Silver"),
        "count" => 1,
        "type" => "tracker coin",
        "create" => 4,
        "location" => "miniboard_{COLOR}",
],
    "tracker_food" => [ 
        "name" => clienttranslate("Provision"),
        "count" => 1,
        "type" => "tracker food",
        "create" => 4,
        "location" => "miniboard_{COLOR}",
],
    "marker" => [ 
        "name" => clienttranslate("Player marker"),
        "count" => 1,
        "type" => "marker wooden",
        "create" => 4,
        "location" => "tableau_{COLOR}",
],
    "influence" => [ 
        "name" => clienttranslate("Influence"),
        "count" => 15,
        "type" => "influence wooden",
        "create" => 3,
        "location" => "tableau_{COLOR}",
],
    "dice" => [ 
        "name" => clienttranslate("Player die"),
        "count" => 5,
        "type" => "dice wooden",
        "create" => 3,
        "location" => "tableau_{COLOR}",
],
    "used_inf_blue" => [ 
        "count" => 1,
        "type" => "flag",
        "create" => 4,
        "location" => "limbo",
],
    "used_inf_yellow" => [ 
        "count" => 1,
        "type" => "flag",
        "create" => 4,
        "location" => "limbo",
],
    "used_inf_black" => [ 
        "count" => 1,
        "type" => "flag",
        "create" => 4,
        "location" => "limbo",
],
    "tracker_comet" => [ 
        "name" => clienttranslate("AI Comet Track"),
        "count" => 0,
        "type" => "tracker tracker_comet",
        "create" => 4,
        "location" => "tableau_{COLOR}",
],
    "tracker_res" => [ 
        "name" => clienttranslate("AI Resource Track"),
        "count" => 0,
        "type" => "tracker tracker_res",
        "create" => 4,
        "location" => "tableau_{COLOR}",
],
    "tracker_vp" => [ 
        "name" => clienttranslate("AI VP"),
        "count" => 0,
        "type" => "tracker tracker_vp",
        "create" => 4,
        "location" => "miniboard_{COLOR}",
],
// #journal tiles
    "jtile" => [ 
        "name" => clienttranslate("Journal Tile"),
        "count" => 10,
        "type" => "jtile tile",
        "create" => 2,
        "location" => "deck_jtile",
],
    "upg" => [ 
        "name" => clienttranslate("Upgrade Tile"),
        "count" => 10,
        "type" => "upg tile",
        "create" => 2,
        "location" => "supply",
],
// #cards
// #36 Townsfolk Cards 36 Space Cards 36 Land Cards 16 Inspiration Cards 36 Water Cards 6 Scheme Cards
    "card_folk" => [ 
        "create" => 0,
        "name" => clienttranslate("Townsfolk"),
        "count" => 36,
        "type" => "card card_folk",
        "location" => "deck_folk",
],
    "card_space" => [ 
        "create" => 0,
        "name" => clienttranslate("Space"),
        "count" => 36,
        "type" => "card card_space",
        "location" => "deck_space",
],
    "card_land" => [ 
        "create" => 0,
        "name" => clienttranslate("Land"),
        "count" => 36,
        "type" => "card card_land",
        "location" => "deck_land",
],
    "card_water" => [ 
        "create" => 0,
        "name" => clienttranslate("Water"),
        "count" => 36,
        "type" => "card card_water",
        "location" => "deck_water",
],
    "card_insp" => [ 
        "create" => 0,
        "name" => clienttranslate("Inspiration"),
        "count" => 16,
        "type" => "card card_insp",
        "location" => "deck_insp",
],
    "card_scheme" => [ 
        "create" => 0,
        "name" => clienttranslate("Scheme"),
        "count" => 6,
        "type" => "card card_scheme",
        "location" => "deck_scheme",
],
    "card_home" => [ 
        "create" => 0,
        "name" => clienttranslate("Home"),
],
// #boards
    "mainboard_1" => [ 
        "create" => 1,
        "count" => 1,
        "type" => "mainboard_x left",
        "location" => "mainboardall",
],
    "mainboard_2" => [ 
        "create" => 1,
        "count" => 1,
        "type" => "mainboard_x middle",
        "location" => "mainboardall",
],
    "mainboard_3" => [ 
        "create" => 1,
        "count" => 1,
        "type" => "mainboard_x right",
        "location" => "mainboardall",
],
    "game_stage" => [ 
        "create" => 1,
        "count" => 1,
        "type" => "stage",
        "location" => "limbo",
],
    "pboard" => [ 
        "create" => 4,
        "name" => clienttranslate("Player Board"),
        "count" => 1,
        "type" => "pboard",
        "location" => "tableau_{COLOR}",
],
// #names of the upgrades
    "upg_green" => [ 
        "name" => clienttranslate("Basic Upgrade Tile"),
],
    "upg_blue" => [ 
        "name" => clienttranslate("Water Upgrade Tile"),
],
    "upg_yellow" => [ 
        "name" => clienttranslate("Land Upgrade Tile"),
],
    "upg_black" => [ 
        "name" => clienttranslate("Space Upgrade Tile"),
],
    "upg_pink" => [ 
        "name" => clienttranslate("Special Upgrade Tile"),
],
// #names of the stats
    "game_vp_tags" => [ 
        "name" => clienttranslate("VP from Primary Tags (City, Vista, Harbour, Open Water)"),
],
    "game_vp_sets" => [ 
        "name" => clienttranslate("VP from Tag Sets"),
],
    "game_vp_space" => [ 
        "name" => clienttranslate("VP from Space Cards"),
],
    "game_vp_insp" => [ 
        "name" => clienttranslate("VP from Inspiration Cards"),
],
    "game_vp_caravan" => [ 
        "name" => clienttranslate("VP from Caravan Upgrade Tiles"),
],
    "game_vp_guilds" => [ 
        "name" => clienttranslate("VP from Guild Majorities"),
],
    "game_vp_total" => [ 
        "name" => clienttranslate("VP total"),
],
    "game_vp_tag_Vista" => [ 
        "name" => clienttranslate("Vista Tags"),
],
    "game_vp_tag_Harbour" => [ 
        "name" => clienttranslate("Harbour Tags"),
],
    "game_vp_tag_City" => [ 
        "name" => clienttranslate("City Tags"),
],
    "game_vp_tag_Sea" => [ 
        "name" => clienttranslate("Open Water Tags"),
],
    "game_vp_ai_folk" => [ 
        "name" => clienttranslate("AI VP from Townsfolk Cards"),
],
    "game_vp_ai_cards" => [ 
        "name" => clienttranslate("AI VP from Land and Water Cards"),
],
    "game_vp_ai_space" => [ 
        "name" => clienttranslate("AI VP from Space Cards"),
],
    "game_vp_ai_insp" => [ 
        "name" => clienttranslate("AI VP from Inspiration Cards"),
],
    "game_vp_ai_caravan" => [ 
        "name" => clienttranslate("AI VP from Caravan Upgrade Tiles"),
],
    "game_vp_ai_guilds" => [ 
        "name" => clienttranslate("AI VP from Guild Majorities"),
],
    "game_vp_ai_total" => [ 
        "name" => clienttranslate("AI VP total"),
],
// #tags
    "tag_Harbour" => [ 
        "type" => "wicon_harbour",
        "name" => clienttranslate("Harbour"),
],
    "tag_Sea" => [ 
        "type" => "wicon_sea",
        "name" => clienttranslate("Open Water"),
],
    "tag_City" => [ 
        "type" => "wicon_city",
        "name" => clienttranslate("City"),
],
    "tag_Vista" => [ 
        "type" => "wicon_vista",
        "name" => clienttranslate("Vista"),
],
    "tag_Observatory" => [ 
        "type" => "wicon_observatory",
        "name" => clienttranslate("Observatory"),
],
    "tag_Book" => [ 
        "type" => "wicon_book",
        "name" => clienttranslate("Book"),
],
    "tag_Comet" => [ 
        "type" => "wicon_comet",
        "name" => clienttranslate("Comet"),
],
    "tag_Stars" => [ 
        "type" => "wicon_stars",
        "name" => clienttranslate("Stars"),
],
    "tag_Planet" => [ 
        "type" => "wicon_planet",
        "name" => clienttranslate("Planet"),
],
    "tag_Sun" => [ 
        "type" => "wicon_sun",
        "name" => clienttranslate("Sun"),
],
    "tag_Moon" => [ 
        "type" => "wicon_moon",
        "name" => clienttranslate("Moon"),
],
    "tag_Library" => [ 
        "type" => "wicon_book",
        "name" => clienttranslate("Library"),
],
    "tag_card_space" => [ 
        "type" => "wicon_card_space",
        "name" => clienttranslate("Space Card"),
],
    "tag_card_water" => [ 
        "type" => "wicon_card_water",
        "name" => clienttranslate("Water Card"),
],
    "tag_card_land" => [ 
        "type" => "wicon_card_land",
        "name" => clienttranslate("Land Card"),
],
    "tag_card_folk" => [ 
        "type" => "wicon_card_folk",
        "name" => clienttranslate("Townsfolk Card"),
],
    "tag_card_insp" => [ 
        "type" => "wicon_card_insp",
        "name" => clienttranslate("Inspiration Card"),
],
    "tag_upg_green" => [ 
        "type" => "wicon_upg_green",
        "name" => clienttranslate("Basic Upgrade"),
],
    "tag_upg_black" => [ 
        "type" => "wicon_upg_black",
        "name" => clienttranslate("Space Upgrade"),
],
    "tag_upg_yellow" => [ 
        "type" => "wicon_upg_yellow",
        "name" => clienttranslate("Land Upgrade"),
],
    "tag_upg_blue" => [ 
        "type" => "wicon_upg_blue",
        "name" => clienttranslate("Water Upgrade"),
],
    "tag_upg_any" => [ 
        "type" => "wicon_upg_any",
        "name" => clienttranslate("Any Upgrade"),
],
// #reasons and assets
    "caravanBonus" => [ 
        "name" => clienttranslate("Caravan Placement Bonus"),
],
    "coinDis" => [ 
        "name" => clienttranslate("Silver Discount"),
],
    "foodDis" => [ 
        "name" => clienttranslate("Provision Discount"),
],
    "vp" => [ 
        "name" => clienttranslate("VP"),
],
    "space" => [ 
        "name" => clienttranslate("Space"),
],
    "land" => [ 
        "name" => clienttranslate("Land"),
],
    "water" => [ 
        "name" => clienttranslate("Water"),
],
    "folk" => [ 
        "name" => clienttranslate("Townsfolk"),
],
    "mainarea" => [ 
        "name" => clienttranslate("Main Board"),
],
// #ui elements
    "wicon_die_1" => [ 
        "type" => "dice wicon wicon_die_1",
        "name" => clienttranslate("Die 1"),
        "count" => 1,
],
    "wicon_die_2" => [ 
        "type" => "dice wicon wicon_die_2",
        "name" => clienttranslate("Die 2"),
        "count" => 2,
],
    "wicon_die_3" => [ 
        "type" => "dice wicon wicon_die_3",
        "name" => clienttranslate("Die 3"),
        "count" => 3,
],
    "wicon_die_4" => [ 
        "type" => "dice wicon wicon_die_4",
        "name" => clienttranslate("Die 4"),
        "count" => 4,
],
    "wicon_die_5" => [ 
        "type" => "dice wicon wicon_die_5",
        "name" => clienttranslate("Die 5"),
        "count" => 5,
],
    "wicon_die_6" => [ 
        "type" => "dice wicon wicon_die_6",
        "name" => clienttranslate("Die 6"),
        "count" => 6,
],
// #icons
// # Destination/Location icons
    "wicon_city" => [ 
],
    "wicon_harbour" => [ 
],
    "wicon_book" => [ 
],
    "wicon_observatory" => [ 
],
    "wicon_vista" => [ 
],
    "wicon_sea" => [ 
],
    "wicon_water" => [ 
],
    "wicon_land" => [ 
],
    "wicon_comet" => [ 
],
    "wicon_comet_up" => [ 
],
    "wicon_stars" => [ 
],
    "wicon_planet" => [ 
],
    "wicon_moon" => [ 
],
    "wicon_sun" => [ 
],
    "wicon_space" => [ 
],
    "wicon_folk" => [ 
],
    "wicon_vp" => [ 
],
// # Upgrade icons
    "wicon_upg_green" => [ 
],
    "wicon_upg_black" => [ 
],
    "wicon_upg_yellow" => [ 
],
    "wicon_upg_blue" => [ 
],
    "wicon_upg_pink" => [ 
],
    "wicon_upg_any" => [ 
],
    "wicon_upg_green_free" => [ 
],
    "wicon_upg_black_free" => [ 
],
    "wicon_upg_yellow_free" => [ 
],
    "wicon_upg_blue_free" => [ 
],
// # Cards and assets
    "wicon_card_space" => [ 
],
    "wicon_card_water" => [ 
],
    "wicon_card_land" => [ 
],
    "wicon_card_folk" => [ 
],
    "wicon_card_insp" => [ 
],
    "wicon_camel" => [ 
],
    "wicon_ship" => [ 
],
    "wicon_pigeon" => [ 
],
    "wicon_telescope" => [ 
],
// # Influence icons
    "wicon_inf_blue" => [ 
],
    "wicon_inf_black" => [ 
],
    "wicon_inf_yellow" => [ 
],
    "wicon_inf_any" => [ 
],
    "wicon_inf_move" => [ 
],
    "wicon_inf_blue_pay" => [ 
],
    "wicon_inf_black_pay" => [ 
],
    "wicon_inf_yellow_pay" => [ 
],
// # Dice and action icons
    "wicon_pick_worker" => [ 
],
    "wicon_reroll" => [ 
],
    "wicon_dice_mod" => [ 
],
    "wicon_journal" => [ 
],
    "wicon_rest1" => [ 
],
    "wicon_rest" => [ 
],
    "wicon_coin" => [ 
],
    "wicon_food" => [ 
],
            /* --- gen php end token_material --- */

            /* --- gen php begin card_material --- */
    "card_home_1" => [ 
        "create" => 4,
        "type" => "card card_home",
        "location" => "tableau_{COLOR}",
        "num" => 1,
        "tags" => "Book Observatory",
],
    "card_home_2" => [ 
        "create" => 4,
        "type" => "card card_home",
        "location" => "tableau_{COLOR}",
        "num" => 2,
        "d" => "camel",
        "dr" => "2n_food:cardLand,coin",
        "tags" => "City",
        "nom" => "Capital City",
        "todr" => "Pay 2 Provisions to acquire a Land Card and gain Silver",
        "state"=>-1,
],
    "card_home_3" => [ 
        "create" => 4,
        "type" => "card card_home",
        "location" => "tableau_{COLOR}",
        "num" => 3,
        "d" => "ship",
        "dr" => "2n_food:cardWater",
        "tags" => "Harbour",
        "nom" => "Capital Harbour",
        "todr" => "Pay 2 Provisions to acquire a Water Card",
        "state"=>1,
],
    "card_home_10" => [ 
        "create" => 4,
        "type" => "card card_home card_slot",
        "location" => "tableau_{COLOR}",
        "num" => 10,
        "d" => "telescope",
        "dr" => "(cardSpace/upgBlack)",
        "nom" => "Capital Observatory",
        "todr" => "Acquire a Space Card or Space Upgrade",
],
    "card_home_11" => [ 
        "create" => 4,
        "type" => "card card_home card_slot",
        "location" => "tableau_{COLOR}",
        "num" => 11,
        "d" => "pigeon",
        "dr" => "(journal/(infAny,pickWorker))",
        "nom" => "Capital Library",
        "todr" => "Journal or Piack a Worker and Gain any Influence",
],
    "card_home_12" => [ 
        "create" => 4,
        "type" => "card card_home card_slot",
        "location" => "tableau_{COLOR}",
        "num" => 12,
        "d" => "any",
        "dr" => "(cardFolk/2food)",
        "nom" => "Capital Market",
        "todr" => "Acquire a Townsfolk Card or Gain 2 Provisions",
],
    "card_home_13" => [ 
        "create" => 4,
        "type" => "card card_home card_slot",
        "location" => "tableau_{COLOR}",
        "num" => 13,
        "d" => "any",
        "dr" => "(upgGreen/2coin)",
        "nom" => "Capital Reserve",
        "todr" => "Acquire a Basic Upgrade or Gain 2 Silver",
],
            /* --- gen php end card_material --- */
            /* --- gen php begin cardfolk_material --- */
// # 114|1|(coin/food):(pickWorker,reroll)|Vista|Protector||Pay 1 Silver or 1 Provision: Pick a Worker and Refresh a Die
// # 115|2|food:2coin|Observatory|Wanderer||Pay 1 Provision: Gain 2 Silver
    "card_folk_116" => [ 
        "create" => 1,
        "num" => 116,
        "cost" => 1,
        "tags" => "Harbour",
        "nom" => clienttranslate("Adventurer"),
        "da" => "ship",
        "tooltip" => clienttranslate("Ship"),
],
    "card_folk_117" => [ 
        "create" => 1,
        "num" => 117,
        "cost" => 0,
        "tags" => "Observatory",
        "nom" => clienttranslate("Astronomer"),
        "da" => "telescope",
        "tooltip" => clienttranslate("Telescope"),
],
    "card_folk_118" => [ 
        "create" => 1,
        "num" => 118,
        "cost" => 2,
        "dr" => "pickWorker/infCard",
        "tags" => "Vista",
        "nom" => clienttranslate("Champion"),
        "tooltip" => clienttranslate("Pick a Worker or Place Influence on a Card"),
],
    "card_folk_121" => [ 
        "create" => 1,
        "num" => 121,
        "cost" => 2,
        "tags" => "City",
        "nom" => clienttranslate("Envoy"),
        "da" => "camel,pigeon",
        "tooltip" => clienttranslate("Camel and Pigeon"),
],
    "card_folk_122" => [ 
        "create" => 1,
        "num" => 122,
        "cost" => 2,
        "tags" => "Harbour",
        "nom" => clienttranslate("Envoy"),
        "da" => "ship,pigeon",
        "tooltip" => clienttranslate("Ship and Pigeon"),
],
    "card_folk_123" => [ 
        "create" => 1,
        "num" => 123,
        "cost" => 2,
        "dr" => "coin",
        "tags" => "City",
        "nom" => clienttranslate("Farmer"),
        "tooltip" => clienttranslate("Gain Silver"),
],
    "card_folk_124" => [ 
        "create" => 1,
        "num" => 124,
        "cost" => 2,
        "dr" => "food",
        "tags" => "City",
        "nom" => clienttranslate("Farmer"),
        "tooltip" => clienttranslate("Gain Provision"),
],
    "card_folk_125" => [ 
        "create" => 1,
        "num" => 125,
        "cost" => 2,
        "dr" => "coin",
        "tags" => "Harbour",
        "nom" => clienttranslate("Fisherman"),
        "tooltip" => clienttranslate("Gain Silver"),
],
    "card_folk_126" => [ 
        "create" => 1,
        "num" => 126,
        "cost" => 2,
        "dr" => "food",
        "tags" => "Harbour",
        "nom" => clienttranslate("Fisherman"),
        "tooltip" => clienttranslate("Gain Provision"),
],
    "card_folk_130" => [ 
        "create" => 1,
        "num" => 130,
        "cost" => 0,
        "dr" => "2diceMod",
        "tags" => "Vista",
        "nom" => clienttranslate("Hunter"),
        "tooltip" => clienttranslate("Modify Dice twice"),
],
    "card_folk_131" => [ 
        "create" => 1,
        "num" => 131,
        "cost" => 2,
        "dr" => "ship,ship",
        "tags" => "Harbour",
        "nom" => clienttranslate("Invader"),
        "tooltip" => clienttranslate("Gain 2 Ships"),
],
    "card_folk_132" => [ 
        "create" => 1,
        "num" => 132,
        "cost" => 2,
        "dr" => "pickWorker/infCard",
        "tags" => "City Harbour",
        "nom" => clienttranslate("Mercenary"),
        "tooltip" => clienttranslate("Pick a Worker or Place Influence on a Card"),
],
    "card_folk_133" => [ 
        "create" => 1,
        "num" => 133,
        "cost" => 2,
        "dr" => "coin",
        "tags" => "Vista",
        "nom" => clienttranslate("Merchant"),
        "tooltip" => clienttranslate("Gain Silver"),
],
    "card_folk_134" => [ 
        "create" => 1,
        "num" => 134,
        "cost" => 2,
        "dr" => "coin",
        "tags" => "Vista",
        "nom" => clienttranslate("Merchant"),
        "tooltip" => clienttranslate("Gain Silver"),
],
    "card_folk_135" => [ 
        "create" => 1,
        "num" => 135,
        "cost" => 2,
        "dr" => "food",
        "tags" => "Vista",
        "nom" => clienttranslate("Merchant"),
        "tooltip" => clienttranslate("Gain Provision"),
],
    "card_folk_136" => [ 
        "create" => 1,
        "num" => 136,
        "cost" => 2,
        "dr" => "food",
        "tags" => "Vista",
        "nom" => clienttranslate("Merchant"),
        "tooltip" => clienttranslate("Gain Provision"),
],
    "card_folk_137" => [ 
        "create" => 1,
        "num" => 137,
        "cost" => 1,
        "tags" => "City Harbour",
        "nom" => clienttranslate("Messenger"),
        "da" => "pigeon",
        "tooltip" => clienttranslate("Pigeon"),
],
    "card_folk_138" => [ 
        "create" => 1,
        "num" => 138,
        "cost" => 1,
        "dr" => "infCard/infMove/diceMod",
        "tags" => "City Harbour",
        "nom" => clienttranslate("Rogue"),
        "tooltip" => clienttranslate("Place Influence on a Card or Move Influence or Modify Dice"),
],
    "card_folk_139" => [ 
        "create" => 1,
        "num" => 139,
        "cost" => 1,
        "dr" => "infBlue/infMove",
        "tags" => "City Harbour",
        "nom" => clienttranslate("Scholar"),
        "tooltip" => clienttranslate("Place an Influence in Blue Guild or Move Influence"),
],
    "card_folk_140" => [ 
        "create" => 1,
        "num" => 140,
        "cost" => 1,
        "dr" => "infYellow/infMove",
        "tags" => "City Harbour",
        "nom" => clienttranslate("Scholar"),
        "tooltip" => clienttranslate("Place an Influence in Yellow Guild or Move Influence"),
],
    "card_folk_141" => [ 
        "create" => 1,
        "num" => 141,
        "cost" => 2,
        "dr" => "infBlack/infMove",
        "tags" => "Book Observatory",
        "nom" => clienttranslate("Scholar"),
        "tooltip" => clienttranslate("Place an Influence in Black Guild or Move Influence"),
],
    "card_folk_142" => [ 
        "create" => 1,
        "num" => 142,
        "cost" => 1,
        "dr" => "infBlue/infMove",
        "tags" => "Book Observatory",
        "nom" => clienttranslate("Scribe"),
        "tooltip" => clienttranslate("Place an Influence in Blue Guild or Move Influence"),
],
    "card_folk_143" => [ 
        "create" => 1,
        "num" => 143,
        "cost" => 1,
        "dr" => "infYellow/infMove",
        "tags" => "Book Observatory",
        "nom" => clienttranslate("Scribe"),
        "tooltip" => clienttranslate("Place an Influence in Yellow Guild or Move Influence"),
],
    "card_folk_144" => [ 
        "create" => 1,
        "num" => 144,
        "cost" => 2,
        "dr" => "infBlack/infMove",
        "tags" => "Vista",
        "nom" => clienttranslate("Scribe"),
        "tooltip" => clienttranslate("Place an Influence in Black Guild or Move Influence"),
],
    "card_folk_145" => [ 
        "create" => 1,
        "num" => 145,
        "cost" => 2,
        "tags" => "City",
        "nom" => clienttranslate("Stargazer"),
        "da" => "telescope,camel",
        "tooltip" => clienttranslate("Telescope, Camel"),
],
    "card_folk_149" => [ 
        "create" => 1,
        "num" => 149,
        "cost" => 1,
        "tags" => "City",
        "nom" => clienttranslate("Vagrant"),
        "da" => "camel",
        "tooltip" => clienttranslate("Camel"),
],
    "card_folk_150" => [ 
        "create" => 1,
        "num" => 150,
        "cost" => 1,
        "dr" => "infCard/infMove/diceMod",
        "tags" => "Vista",
        "nom" => clienttranslate("Vigilante"),
        "tooltip" => clienttranslate("Place Influence on a Card or Move Influence or Modify Dice"),
],
    "card_folk_119" => [ 
        "create" => 1,
        "rest" => 1,
        "num" => 119,
        "cost" => 1,
        "dr" => "infMove,food",
        "tags" => "Sea",
        "nom" => clienttranslate("Enforcer"),
        "tooltip" => clienttranslate("Move Influence and Gain Provision"),
],
    "card_folk_120" => [ 
        "create" => 1,
        "rest" => 1,
        "num" => 120,
        "cost" => 1,
        "dr" => "infMove,food",
        "tags" => "Sea",
        "nom" => clienttranslate("Enforcer"),
        "tooltip" => clienttranslate("Move Influence and Gain Provision"),
],
    "card_folk_127" => [ 
        "create" => 1,
        "rest" => 1,
        "num" => 127,
        "cost" => 3,
        "dr" => "food,coin",
        "tags" => "Sea",
        "nom" => clienttranslate("Guardian"),
        "tooltip" => clienttranslate("Gain Provision and Gain Silver"),
],
    "card_folk_128" => [ 
        "create" => 1,
        "rest" => 1,
        "num" => 128,
        "cost" => 3,
        "dr" => "2coin",
        "tags" => "Sea",
        "nom" => clienttranslate("Guardian"),
        "tooltip" => clienttranslate("Gain 2 Silver"),
],
    "card_folk_129" => [ 
        "create" => 1,
        "rest" => 1,
        "num" => 129,
        "cost" => 3,
        "dr" => "2food",
        "tags" => "Sea",
        "nom" => clienttranslate("Guardian"),
        "tooltip" => clienttranslate("Gain 2 Provision"),
],
    "card_folk_146" => [ 
        "create" => 1,
        "rest" => 1,
        "num" => 146,
        "cost" => 1,
        "dr" => "infBlue/infMove",
        "tags" => "Sea",
        "nom" => clienttranslate("Translator"),
        "tooltip" => clienttranslate("Place an Influence in Blue Guild or Move Influence"),
],
    "card_folk_147" => [ 
        "create" => 1,
        "rest" => 1,
        "num" => 147,
        "cost" => 1,
        "dr" => "infYellow/infMove",
        "tags" => "Sea",
        "nom" => clienttranslate("Translator"),
        "tooltip" => clienttranslate("Place an Influence in Yellow Guild or Move Influence"),
],
    "card_folk_148" => [ 
        "create" => 1,
        "rest" => 1,
        "num" => 148,
        "cost" => 2,
        "dr" => "infBlack/infMove",
        "tags" => "Sea",
        "nom" => clienttranslate("Translator"),
        "tooltip" => clienttranslate("Place an Influence in Black Guild or Move Influence"),
],
    "card_folk_151" => [ 
        "create" => 1,
        "rest" => 1,
        "num" => 151,
        "cost" => 0,
        "dr" => "pickWorker/infCard",
        "tags" => "Sea",
        "nom" => clienttranslate("Warrior"),
        "tooltip" => clienttranslate("Pick a Worker or Move Influence"),
],
            /* --- gen php end cardfolk_material --- */
            /* --- gen php begin cardland_material --- */
// # Row 1-3: City (1-18)
    "card_land_1" => [ 
        "create" => 1,
        "num" => 1,
        "r" => "infYellow",
        "d" => "camel",
        "dr" => "upgYellow/(infYellow,infYellow)",
        "tags" => "City",
        "tor" => clienttranslate("Gain Yellow influence"),
        "todr" => clienttranslate("Acquire a Land upgrade or gain 2 Yellow influence"),
],
    "card_land_2" => [ 
        "create" => 1,
        "num" => 2,
        "r" => "infYellow",
        "d" => "camel",
        "dr" => "upgYellow/(infYellow,infYellow)",
        "tags" => "City",
        "tor" => clienttranslate("Gain Yellow influence"),
        "todr" => clienttranslate("Acquire a Land upgrade or gain 2 Yellow influence"),
],
    "card_land_3" => [ 
        "create" => 1,
        "num" => 3,
        "r" => "upgGreen(free)",
        "d" => "camel",
        "dr" => "upgGreen,food",
        "tags" => "City",
        "tor" => clienttranslate("Acquire a free Basic upgrade"),
        "todr" => clienttranslate("Acquire a Basic upgrade and gain 1 Provision"),
],
    "card_land_4" => [ 
        "create" => 1,
        "num" => 4,
        "r" => "infAny",
        "d" => "camel",
        "dr" => "upgGreen,infAny",
        "tags" => "City",
        "tor" => clienttranslate("Gain Influence on any guild"),
        "todr" => clienttranslate("Acquire a Basic upgrade and gain Influence on any guild"),
],
    "card_land_5" => [ 
        "create" => 1,
        "num" => 5,
        "r" => "pickWorker",
        "d" => "camel",
        "dr" => "2n_food:cardLand,pickWorker",
        "tags" => "City",
        "tor" => clienttranslate("Pick a Worker"),
        "todr" => clienttranslate("Pay 2 Provisions to acquire a Land card, then pick a Worker"),
],
    "card_land_6" => [ 
        "create" => 1,
        "num" => 6,
        "r" => "infYellow",
        "d" => "camel",
        "dr" => "2n_food:cardLand,infYellow",
        "tags" => "City",
        "tor" => clienttranslate("Gain Yellow influence"),
        "todr" => clienttranslate("Pay 2 Provisions to acquire a Land card, then gain Yellow influence"),
],
    "card_land_7" => [ 
        "create" => 1,
        "num" => 7,
        "r" => "infBlue",
        "d" => "camel",
        "dr" => "cardFolk,infBlue",
        "tags" => "City",
        "tor" => clienttranslate("Gain Blue influence"),
        "todr" => clienttranslate("Acquire a Townsfolk card and gain Blue influence"),
],
    "card_land_8" => [ 
        "create" => 1,
        "num" => 8,
        "r" => "infCard",
        "d" => "camel",
        "dr" => "cardFolk,infCard",
        "tags" => "City",
        "tor" => clienttranslate("Gain Influence on any card"),
        "todr" => clienttranslate("Acquire a Townsfolk card and gain Influence on any card"),
],
    "card_land_9" => [ 
        "create" => 1,
        "num" => 9,
        "d" => "telescope,camel",
        "dr" => "cardSpace,infBlue",
        "tags" => "City Observatory",
        "tor" => clienttranslate("Acquire a Space card and gain Blue influence"),
],
    "card_land_10" => [ 
        "create" => 1,
        "num" => 10,
        "d" => "telescope,camel",
        "dr" => "cardSpace,infYellow",
        "tags" => "City Observatory",
        "todr" => clienttranslate("Acquire a Space card and gain Yellow influence"),
],
    "card_land_11" => [ 
        "create" => 1,
        "num" => 11,
        "d" => "telescope,camel",
        "dr" => "upgBlack/(infBlack,infBlack)",
        "tags" => "City Observatory",
        "todr" => clienttranslate("Acquire a Space upgrade or gain 2 Black influence"),
],
    "card_land_12" => [ 
        "create" => 1,
        "num" => 12,
        "d" => "telescope,camel",
        "dr" => "upgBlack/(infBlack,infBlack)",
        "tags" => "City Observatory",
        "todr" => clienttranslate("Acquire a Space upgrade or gain 2 Black influence"),
],
    "card_land_13" => [ 
        "create" => 1,
        "num" => 13,
        "d" => "telescope,camel",
        "dr" => "cardSpace,infBlack",
        "tags" => "City Observatory",
        "todr" => clienttranslate("Acquire a Space card and gain Black influence"),
],
    "card_land_14" => [ 
        "create" => 1,
        "num" => 14,
        "d" => "telescope,camel",
        "dr" => "cardSpace(dis)",
        "tags" => "City Observatory",
        "todr" => clienttranslate("Acquire a discounted Space card"),
],
    "card_land_15" => [ 
        "create" => 1,
        "num" => 15,
        "r" => "infAny",
        "d" => "camel",
        "dr" => "cardFolk,journal",
        "tags" => "City Book",
        "tor" => clienttranslate("Gain Influence on any guild"),
        "todr" => clienttranslate("Acquire a Townsfolk card and gain Journal"),
],
    "card_land_16" => [ 
        "create" => 1,
        "num" => 16,
        "d" => "pigeon",
        "dr" => "infYellow,journal",
        "tags" => "City Book",
        "todr" => clienttranslate("Gain Yellow influence and Journal"),
],
    "card_land_17" => [ 
        "create" => 1,
        "num" => 17,
        "d" => "pigeon",
        "dr" => "infBlue,journal",
        "tags" => "City Book",
        "todr" => clienttranslate("Gain Blue influence and Journal"),
],
    "card_land_18" => [ 
        "create" => 1,
        "num" => 18,
        "d" => "telescope,pigeon",
        "dr" => "infBlack,journal",
        "tags" => "City Book Observatory",
        "todr" => clienttranslate("Gain Black influence and Journal"),
],
// # Row 4-6: Vista (19-37)
    "card_land_19" => [ 
        "create" => 1,
        "num" => 19,
        "r" => "infCard,infCard",
        "dr" => "infCard",
        "tags" => "Vista",
        "trig" => "Stars",
        "tor" => clienttranslate("Gain 2 Influence on any card"),
        "todr" => clienttranslate("Gain Influence on any card"),
],
    "card_land_20" => [ 
        "create" => 1,
        "num" => 20,
        "r" => "coin",
        "dr" => "food",
        "tags" => "Vista",
        "trig" => "Stars",
        "tor" => clienttranslate("Gain 1 Silver"),
        "todr" => clienttranslate("Gain 1 Provision"),
],
    "card_land_21" => [ 
        "create" => 1,
        "num" => 21,
        "r" => "coin",
        "dr" => "coin",
        "tags" => "Vista",
        "trig" => "Sea",
        "tor" => clienttranslate("Gain 1 Silver"),
        "todr" => clienttranslate("Gain 1 Silver"),
],
    "card_land_22" => [ 
        "create" => 1,
        "num" => 22,
        "r" => "infBlue",
        "dr" => "infBlue/infMove",
        "tags" => "Vista",
        "trig" => "Sea",
        "tor" => clienttranslate("Gain Blue influence"),
        "todr" => clienttranslate("Gain Blue influence or move Influence"),
],
    "card_land_23" => [ 
        "create" => 1,
        "num" => 23,
        "r" => "upgGreen(free)",
        "dr" => "coin/diceMod",
        "tags" => "Vista",
        "trig" => "Comet",
        "tor" => clienttranslate("Acquire a free Basic upgrade"),
        "todr" => clienttranslate("Gain 1 Silver or modify dice by +/- 1"),
],
    "card_land_24" => [ 
        "create" => 1,
        "num" => 24,
        "r" => "upgGreen(free)",
        "dr" => "food/infMove",
        "tags" => "Vista",
        "trig" => "Comet",
        "tor" => clienttranslate("Acquire a free Basic upgrade"),
        "todr" => clienttranslate("Gain 1 Provision or move Influence"),
],
    "card_land_25" => [ 
        "create" => 1,
        "num" => 25,
        "r" => "reroll",
        "dr" => "coin",
        "tags" => "Vista",
        "trig" => "Vista",
        "tor" => clienttranslate("Refresh a die"),
        "todr" => clienttranslate("Gain 1 Silver"),
],
    "card_land_26" => [ 
        "create" => 1,
        "num" => 26,
        "r" => "reroll",
        "dr" => "infBlue/infMove",
        "tags" => "Vista",
        "trig" => "Vista",
        "tor" => clienttranslate("Refresh a die"),
        "todr" => clienttranslate("Gain Blue influence or move Influence"),
],
    "card_land_27" => [ 
        "create" => 1,
        "num" => 27,
        "r" => "2coin",
        "dr" => "food",
        "tags" => "Vista",
        "trig" => "Planet/Sun/Moon",
        "tor" => clienttranslate("Gain 2 Silver"),
        "todr" => clienttranslate("Gain 1 Provision"),
],
    "card_land_28" => [ 
        "create" => 1,
        "num" => 28,
        "r" => "2coin",
        "dr" => "pickWorker",
        "tags" => "Vista",
        "trig" => "Planet/Sun/Moon",
        "tor" => clienttranslate("Gain 2 Silver"),
        "todr" => clienttranslate("Pick a Worker"),
],
    "card_land_29" => [ 
        "create" => 1,
        "num" => 29,
        "r" => "reroll",
        "dr" => "coin",
        "tags" => "Vista",
        "trig" => "City",
        "tor" => clienttranslate("Refresh a die"),
        "todr" => clienttranslate("Gain 1 Silver"),
],
    "card_land_30" => [ 
        "create" => 1,
        "num" => 30,
        "r" => "reroll",
        "dr" => "infYellow/infMove",
        "tags" => "Vista",
        "trig" => "City",
        "tor" => clienttranslate("Refresh a die"),
        "todr" => clienttranslate("Gain Yellow influence or move Influence"),
],
    "card_land_31" => [ 
        "create" => 1,
        "num" => 31,
        "r" => "2coin",
        "dr" => "diceMod,infMove",
        "tags" => "Vista",
        "trig" => "card_folk",
        "tor" => clienttranslate("Gain 2 Silver"),
        "todr" => clienttranslate("Modify dice by +/- 1 and move Influence"),
],
    "card_land_32" => [ 
        "create" => 1,
        "num" => 32,
        "r" => "infCard",
        "dr" => "infCard",
        "tags" => "Vista",
        "trig" => "card_folk",
        "tor" => clienttranslate("Gain Influence on any card"),
        "todr" => clienttranslate("Gain Influence on any card"),
],
    "card_land_33" => [ 
        "create" => 1,
        "num" => 33,
        "r" => "coin",
        "dr" => "coin",
        "tags" => "Vista",
        "trig" => "Harbour",
        "tor" => clienttranslate("Gain 1 Silver"),
        "todr" => clienttranslate("Gain 1 Silver"),
],
    "card_land_34" => [ 
        "create" => 1,
        "num" => 34,
        "r" => "infYellow",
        "dr" => "infYellow/infMove",
        "tags" => "Vista",
        "trig" => "Harbour",
        "tor" => clienttranslate("Gain Yellow influence"),
        "todr" => clienttranslate("Gain Yellow influence or move Influence"),
],
    "card_land_35" => [ 
        "create" => 1,
        "num" => 35,
        "r" => "reroll",
        "dr" => "journal",
        "tags" => "Vista",
        "trig" => "Observatory/Book",
        "tor" => clienttranslate("Refresh a die"),
        "todr" => clienttranslate("Gain Journal"),
],
    "card_land_36" => [ 
        "create" => 1,
        "num" => 36,
        "r" => "reroll",
        "dr" => "food/coin",
        "tags" => "Vista",
        "trig" => "Observatory/Book",
        "tor" => clienttranslate("Refresh a die"),
        "todr" => clienttranslate("Gain 1 Provision or 1 Silver"),
],
            /* --- gen php end cardland_material --- */
            /* --- gen php begin cardwater_material --- */
// # Water Cards (39-76) - 6x7 sprite
// # 39||2telescope|4n_coin:cardSpace(draw)|Harbour Observatory|xx__|_xxx||Pay 4 Silver to draw and acquire a Space card
// # 40|cardLand(draw1)|||Sea|___|___x|Draw and acquire 1 Land card|
    "card_water_41" => [ 
        "create" => 1,
        "num" => 41,
        "d" => "pigeon",
        "dr" => "food,journal",
        "tags" => "Harbour Book",
        "c1" => "__x_",
        "c2" => "bx_x",
        "todr" => clienttranslate("Gain 1 Provision and Journal"),
],
    "card_water_42" => [ 
        "create" => 1,
        "num" => 42,
        "d" => "pigeon",
        "dr" => "coin,journal",
        "tags" => "Harbour Book",
        "c1" => "__x_",
        "c2" => "u_xx",
        "todr" => clienttranslate("Gain 1 Silver and Journal"),
],
    "card_water_43" => [ 
        "create" => 1,
        "num" => 43,
        "d" => "pigeon",
        "dr" => "infCard,journal",
        "tags" => "Harbour Book",
        "c1" => "x__x",
        "c2" => "yxxx",
        "todr" => clienttranslate("Gain Influence on any card and Journal"),
],
    "card_water_44" => [ 
        "create" => 1,
        "num" => 44,
        "d" => "ship,ship",
        "dr" => "1n_food:cardWater",
        "tags" => "Harbour Book",
        "c1" => "_xx_",
        "c2" => "yx__",
        "todr" => clienttranslate("Pay 1 Provision to acquire a Water card"),
],
    "card_water_45" => [ 
        "create" => 1,
        "num" => 45,
        "d" => "ship",
        "dr" => "2n_food:cardWater,coin",
        "tags" => "Harbour",
        "c1" => "_x_x",
        "c2" => "ux_x",
        "todr" => clienttranslate("Pay 2 Provisions to acquire a Water card, then gain Silver"),
],
    "card_water_46" => [ 
        "create" => 1,
        "num" => 46,
        "d" => "ship",
        "dr" => "2n_food:(cardWater+pickWorker)",
        "tags" => "Harbour",
        "c1" => "_x_x",
        "c2" => "__xx",
        "todr" => clienttranslate("Pay 2 Provisions to acquire a Water card, then pick a Worker"),
],
    "card_water_47" => [ 
        "create" => 1,
        "num" => 47,
        "d" => "ship",
        "dr" => "upgBlue/(infBlue,infBlue)",
        "tags" => "Harbour",
        "c1" => "xx__",
        "c2" => "u_x_",
        "todr" => clienttranslate("Acquire a Water upgrade or gain 2 Blue influence"),
],
    "card_water_48" => [ 
        "create" => 1,
        "num" => 48,
        "d" => "ship",
        "dr" => "upgBlue/(infBlue,infBlue)",
        "tags" => "Harbour",
        "c1" => "__xx",
        "c2" => "yx__",
        "todr" => clienttranslate("Acquire a Water upgrade or gain 2 Blue influence"),
],
    "card_water_49" => [ 
        "create" => 1,
        "num" => 49,
        "d" => "ship",
        "dr" => "reroll,diceMod,food",
        "tags" => "Harbour",
        "c1" => "_xx_",
        "c2" => "uxx_",
        "todr" => clienttranslate("Refresh a die, modify dice by +/- 1, then gain 1 Provision"),
],
    "card_water_50" => [ 
        "create" => 1,
        "num" => 50,
        "d" => "ship,ship",
        "dr" => "pickWorker,reroll,infCard",
        "tags" => "Harbour",
        "c1" => "__xx",
        "c2" => "u__x",
        "todr" => clienttranslate("Pick a Worker, refresh a die, then gain Influence on any card"),
],
    "card_water_51" => [ 
        "create" => 1,
        "num" => 51,
        "d" => "ship,ship",
        "dr" => "infYellow,infBlack,coin",
        "tags" => "Harbour",
        "c1" => "xx__",
        "c2" => "__xx",
        "todr" => clienttranslate("Gain Yellow influence, Black influence, and 1 Silver"),
],
    "card_water_52" => [ 
        "create" => 1,
        "num" => 52,
        "d" => "ship,ship",
        "dr" => "infBlue,infBlack,food",
        "tags" => "Harbour",
        "c1" => "x_x_",
        "c2" => "yx__",
        "todr" => clienttranslate("Gain Blue influence, Black influence, and 1 Provision"),
],
    "card_water_53" => [ 
        "create" => 1,
        "num" => 53,
        "d" => "ship,ship",
        "dr" => "cardFolk(free)",
        "tags" => "Harbour",
        "c1" => "x___",
        "c2" => "_xx_",
        "todr" => clienttranslate("Acquire a free Townsfolk card"),
],
    "card_water_54" => [ 
        "create" => 1,
        "num" => 54,
        "d" => "ship",
        "dr" => "food,cardFolk",
        "tags" => "Harbour",
        "c1" => "_x_x",
        "c2" => "_xx_",
        "todr" => clienttranslate("Acquire a Townsfolk card and gain Provision"),
],
    "card_water_55" => [ 
        "create" => 1,
        "num" => 55,
        "d" => "ship,ship",
        "dr" => "4food",
        "tags" => "Harbour",
        "c1" => "x_x_",
        "c2" => "__xx",
        "todr" => clienttranslate("Gain 4 Provisions"),
],
    "card_water_56" => [ 
        "create" => 1,
        "num" => 56,
        "d" => "ship,ship",
        "dr" => "4coin",
        "tags" => "Harbour",
        "c1" => "_x_x",
        "c2" => "__xx",
        "todr" => clienttranslate("Gain 4 Silver"),
],
    "card_water_57" => [ 
        "create" => 1,
        "num" => 57,
        "d" => "ship",
        "dr" => "infCard,2coin",
        "tags" => "Harbour",
        "c1" => "_x_x",
        "c2" => "bxxx",
        "todr" => clienttranslate("Gain Influence on any card and gain 2 Silver"),
],
    "card_water_58" => [ 
        "create" => 1,
        "num" => 58,
        "d" => "ship",
        "dr" => "pickWorker,2food",
        "tags" => "Harbour",
        "c1" => "xx__",
        "c2" => "_x_x",
        "todr" => clienttranslate("Pick a Worker and gain 2 Provisions"),
],
    "card_water_59" => [ 
        "create" => 1,
        "num" => 59,
        "r" => "infBlack,coin,food,infCard",
        "tags" => "Sea",
        "c1" => "_x__",
        "c2" => "bxxx",
        "tor" => clienttranslate("Gain Black influence, 1 Silver, 1 Provision, and Influence on any card"),
],
    "card_water_60" => [ 
        "create" => 1,
        "num" => 60,
        "r" => "2coin,reroll",
        "tags" => "Sea",
        "c1" => "__xx",
        "c2" => "_xx_",
        "tor" => clienttranslate("Gain 2 Silver and refresh a die"),
],
    "card_water_61" => [ 
        "create" => 1,
        "num" => 61,
        "r" => "upgBlack(free)",
        "tags" => "Sea",
        "c1" => "___x",
        "c2" => "u___",
        "tor" => clienttranslate("Acquire a free Space upgrade"),
],
    "card_water_62" => [ 
        "create" => 1,
        "num" => 62,
        "r" => "upgGreen(free),diceMod",
        "tags" => "Sea",
        "c1" => "x__x",
        "c2" => "b_x_",
        "tor" => clienttranslate("Acquire a free Basic upgrade and modify dice by +/- 1"),
],
    "card_water_63" => [ 
        "create" => 1,
        "num" => 63,
        "r" => "upgYellow(free)",
        "tags" => "Sea",
        "c1" => "_x__",
        "c2" => "_x__",
        "tor" => clienttranslate("Acquire a free Land upgrade"),
],
    "card_water_64" => [ 
        "create" => 1,
        "num" => 64,
        "r" => "upgBlue(free)",
        "tags" => "Sea",
        "c1" => "__x_",
        "c2" => "__x_",
        "tor" => clienttranslate("Acquire a free Water upgrade"),
],
    "card_water_65" => [ 
        "create" => 1,
        "num" => 65,
        "r" => "3food",
        "tags" => "Sea",
        "c1" => "__xx",
        "c2" => "_xx_",
        "tor" => clienttranslate("Gain 3 Provisions"),
],
    "card_water_66" => [ 
        "create" => 1,
        "num" => 66,
        "r" => "3coin",
        "tags" => "Sea",
        "c1" => "xx__",
        "c2" => "_xx_",
        "tor" => clienttranslate("Gain 3 Silver"),
],
    "card_water_67" => [ 
        "create" => 1,
        "num" => 67,
        "r" => "cardFolk(free)",
        "tags" => "Sea",
        "c1" => "x___",
        "c2" => "_x__",
        "tor" => clienttranslate("Acquire a free Townsfolk card"),
],
    "card_water_68" => [ 
        "create" => 1,
        "num" => 68,
        "r" => "cardInsp",
        "tags" => "Sea",
        "c1" => "____",
        "c2" => "_x__",
        "tor" => clienttranslate("Acquire an Inspiration card"),
],
    "card_water_69" => [ 
        "create" => 1,
        "num" => 69,
        "r" => "infBlack,journal",
        "tags" => "Sea",
        "c1" => "_x__",
        "c2" => "_x_x",
        "tor" => clienttranslate("Gain Black influence and Journal"),
],
    "card_water_70" => [ 
        "create" => 1,
        "num" => 70,
        "r" => "journal",
        "tags" => "Sea",
        "c1" => "__x_",
        "c2" => "ux__",
        "tor" => clienttranslate("Gain Journal"),
],
    "card_water_71" => [ 
        "create" => 1,
        "num" => 71,
        "r" => "pickWorker,reroll",
        "tags" => "Sea",
        "c1" => "x__x",
        "c2" => "y__x",
        "tor" => clienttranslate("Pick a Worker and refresh a die"),
],
    "card_water_72" => [ 
        "create" => 1,
        "num" => 72,
        "r" => "pickWorker,pickWorker",
        "tags" => "Sea",
        "c1" => "_x__",
        "c2" => "b___",
        "tor" => clienttranslate("Pick 2 Workers"),
],
    "card_water_73" => [ 
        "create" => 1,
        "num" => 73,
        "r" => "(infCard,infCard)/(infMove,infMove)",
        "tags" => "Sea",
        "c1" => "_xx_",
        "c2" => "_x_x",
        "tor" => clienttranslate("Gain 2 Influence on any card or move 2 Influence"),
],
    "card_water_74" => [ 
        "create" => 1,
        "num" => 74,
        "r" => "infBlue,infBlue",
        "tags" => "Sea",
        "c1" => "__x_",
        "c2" => "yx_x",
        "tor" => clienttranslate("Gain 2 Blue influence"),
],
    "card_water_75" => [ 
        "create" => 1,
        "num" => 75,
        "r" => "infYellow,infYellow",
        "tags" => "Sea",
        "c1" => "_x__",
        "c2" => "u_xx",
        "tor" => clienttranslate("Gain 2 Yellow influence"),
],
    "card_water_76" => [ 
        "create" => 1,
        "num" => 76,
        "r" => "infBlack,infBlack",
        "tags" => "Sea",
        "c1" => "___x",
        "c2" => "_xx_",
        "tor" => clienttranslate("Gain 2 Black influence"),
],
            /* --- gen php end cardwater_material --- */

            /* --- gen php begin upg_material --- */
// #p: position on the mainboad
    "upg_yellow_1" => [ 
        "type" => "upg upg_yellow",
        "w" => 2,
        "h" => 1,
        "num" => 1,
        "t" => "yellow",
        "r" => "dicePlus",
        "r2" => "camel",
        "name" => clienttranslate("Land Upgrade Camel Plus"),
        "p" => 1,
],
    "upg_yellow_2" => [ 
        "type" => "upg upg_yellow",
        "w" => 2,
        "h" => 1,
        "num" => 2,
        "t" => "yellow",
        "r" => "camel",
        "r2" => "diceMinus",
        "name" => clienttranslate("Land Upgrade Camel Minus"),
        "p" => 1,
],
    "upg_yellow_3" => [ 
        "type" => "upg upg_yellow",
        "w" => 2,
        "h" => 1,
        "num" => 3,
        "t" => "yellow",
        "r" => "pigeon",
        "r2" => "dicePlus",
        "name" => clienttranslate("Land Upgrade Pigeon Plus"),
        "p" => 2,
],
    "upg_yellow_4" => [ 
        "type" => "upg upg_yellow",
        "w" => 2,
        "h" => 1,
        "num" => 4,
        "t" => "yellow",
        "r" => "diceMinus",
        "r2" => "pigeon",
        "name" => clienttranslate("Land Upgrade Pigeon Minus"),
        "p" => 2,
],
    "upg_yellow_5" => [ 
        "type" => "upg upg_yellow",
        "w" => 2,
        "h" => 1,
        "num" => 5,
        "t" => "yellow",
        "r" => "vp",
        "r2" => "coinDis",
        "vp" => 1,
        "name" => clienttranslate("Land Upgrade Discount Right"),
        "p" => 3,
],
    "upg_yellow_6" => [ 
        "type" => "upg upg_yellow",
        "w" => 2,
        "h" => 1,
        "num" => 6,
        "t" => "yellow",
        "r" => "coinDis",
        "r2" => "vp",
        "vp" => 1,
        "name" => clienttranslate("Land Upgrade Discount Left"),
        "p" => 3,
],
    "upg_blue_7" => [ 
        "type" => "upg upg_blue",
        "w" => 2,
        "h" => 1,
        "num" => 7,
        "t" => "blue",
        "r" => "dicePlus",
        "r2" => "ship",
        "name" => clienttranslate("Water Upgrade Ship Plus"),
        "p" => 1,
],
    "upg_blue_8" => [ 
        "type" => "upg upg_blue",
        "w" => 2,
        "h" => 1,
        "num" => 8,
        "t" => "blue",
        "r" => "ship",
        "r2" => "diceMinus",
        "name" => clienttranslate("Water Upgrade Ship Minus"),
        "p" => 1,
],
    "upg_blue_9" => [ 
        "type" => "upg upg_blue",
        "w" => 2,
        "h" => 1,
        "num" => 9,
        "t" => "blue",
        "r" => "ship",
        "r2" => "pigeon",
        "name" => clienttranslate("Water Upgrade Ship Pigeon"),
        "p" => 2,
],
    "upg_blue_10" => [ 
        "type" => "upg upg_blue",
        "w" => 2,
        "h" => 1,
        "num" => 10,
        "t" => "blue",
        "r" => "pigeon",
        "r2" => "ship",
        "name" => clienttranslate("Water Upgrade Pigeon Ship"),
        "p" => 2,
],
    "upg_blue_11" => [ 
        "type" => "upg upg_blue",
        "w" => 2,
        "h" => 1,
        "num" => 11,
        "t" => "blue",
        "r" => "coinDis",
        "r2" => "dicePlus",
        "name" => clienttranslate("Water Upgrade Discount Plus"),
        "p" => 3,
],
    "upg_blue_12" => [ 
        "type" => "upg upg_blue",
        "w" => 2,
        "h" => 1,
        "num" => 12,
        "t" => "blue",
        "r" => "diceMinus",
        "r2" => "coinDis",
        "name" => clienttranslate("Water Upgrade Discount Minus"),
        "p" => 3,
],
    "upg_black_20" => [ 
        "type" => "upg upg_black",
        "w" => 1,
        "h" => 2,
        "num" => 20,
        "t" => "black",
        "r" => "ship,pigeon,camel",
        "name" => clienttranslate("Space Upgrade Trio"),
        "p" => 3,
],
    "upg_black_21" => [ 
        "type" => "upg upg_black",
        "w" => 1,
        "h" => 2,
        "num" => 21,
        "t" => "black",
        "r" => "diceMod,vp",
        "vp" => 3,
        "name" => clienttranslate("Space Upgrade Dice Mod"),
        "p" => 2,
],
    "upg_black_22" => [ 
        "type" => "upg upg_black",
        "w" => 1,
        "h" => 2,
        "num" => 22,
        "t" => "black",
        "r" => "telescope,foodDis",
        "name" => clienttranslate("Space Upgrade Telescope"),
        "p" => 1,
],
    "upg_green_31" => [ 
        "type" => "upg upg_green",
        "w" => 1,
        "h" => 1,
        "num" => 31,
        "t" => "green",
        "r" => "camel",
        "vp" => 1,
        "name" => clienttranslate("Basic Upgrade Camel"),
        "p" => 4,
],
    "upg_green_32" => [ 
        "type" => "upg upg_green",
        "w" => 1,
        "h" => 1,
        "num" => 32,
        "t" => "green",
        "r" => "ship",
        "vp" => 1,
        "name" => clienttranslate("Basic Upgrade Ship"),
        "p" => 2,
],
    "upg_green_33" => [ 
        "type" => "upg upg_green",
        "w" => 1,
        "h" => 1,
        "num" => 33,
        "t" => "green",
        "r" => "telescope",
        "vp" => 1,
        "name" => clienttranslate("Basic Upgrade Telescope"),
        "p" => 1,
],
    "upg_green_34" => [ 
        "type" => "upg upg_green",
        "w" => 1,
        "h" => 1,
        "num" => 34,
        "t" => "green",
        "r" => "pigeon",
        "vp" => 1,
        "name" => clienttranslate("Basic Upgrade Pigeon"),
        "p" => 3,
],
    "upg_pink_40" => [ 
        "type" => "upg upg_pink",
        "w" => 1,
        "h" => 1,
        "num" => 40,
        "t" => "pink",
        "tags" => "Vista",
        "vp" => 1,
        "name" => clienttranslate("Special Upgrade Vista"),
        "p" => 5,
],
    "upg_pink_41" => [ 
        "type" => "upg upg_pink",
        "w" => 1,
        "h" => 1,
        "num" => 41,
        "t" => "pink",
        "tags" => "City",
        "vp" => 1,
        "name" => clienttranslate("Special Upgrade City"),
        "p" => 4,
],
    "upg_pink_42" => [ 
        "type" => "upg upg_pink",
        "w" => 1,
        "h" => 1,
        "num" => 42,
        "t" => "pink",
        "tags" => "Sea",
        "vp" => 1,
        "name" => clienttranslate("Special Upgrade Sea"),
        "p" => 1,
],
    "upg_pink_43" => [ 
        "type" => "upg upg_pink",
        "w" => 1,
        "h" => 1,
        "num" => 43,
        "t" => "pink",
        "tags" => "Harbour",
        "vp" => 1,
        "name" => clienttranslate("Special Upgrade Harbour"),
        "p" => 2,
],
    "upg_pink_44" => [ 
        "type" => "upg upg_pink",
        "w" => 1,
        "h" => 1,
        "num" => 44,
        "t" => "pink",
        "tags" => "Observatory",
        "vp" => 5,
        "name" => clienttranslate("Special Upgrade Observatory"),
        "p" => 8,
],
    "upg_pink_45" => [ 
        "type" => "upg upg_pink",
        "w" => 1,
        "h" => 1,
        "num" => 45,
        "t" => "pink",
        "tags" => "Book",
        "vp" => 5,
        "name" => clienttranslate("Special Upgrade Book"),
        "p" => 3,
],
    "upg_pink_46" => [ 
        "type" => "upg upg_pink",
        "w" => 1,
        "h" => 1,
        "num" => 46,
        "t" => "pink",
        "tags" => "Planet",
        "vp" => 3,
        "name" => clienttranslate("Special Upgrade Planet"),
        "p" => 6,
],
    "upg_pink_47" => [ 
        "type" => "upg upg_pink",
        "w" => 1,
        "h" => 1,
        "num" => 47,
        "t" => "pink",
        "tags" => "Stars",
        "vp" => 5,
        "name" => clienttranslate("Special Upgrade Stars"),
        "p" => 7,
],
    "upg_pink_48" => [ 
        "type" => "upg upg_pink",
        "w" => 1,
        "h" => 1,
        "num" => 48,
        "t" => "pink",
        "tags" => "Comet Comet",
        "vp" => 4,
        "name" => clienttranslate("Special Upgrade Double Comet"),
        "p" => 10,
],
    "upg_pink_49" => [ 
        "type" => "upg upg_pink",
        "w" => 1,
        "h" => 1,
        "num" => 49,
        "t" => "pink",
        "tags" => "Comet",
        "vp" => 5,
        "name" => clienttranslate("Special Upgrade Comet"),
        "p" => 9,
],
            /* --- gen php end upg_material --- */
            /* --- gen php begin action_material --- */
// # Land card worker actions (positions 1-4)
    "action_land_1" => [ 
        "type" => "action",
        "ctype" => "land",
        "num" => 1,
        "r" => "cardFolk,diceMod",
        "name" => clienttranslate("Recruit"),
],
    "action_land_2" => [ 
        "type" => "action",
        "ctype" => "land",
        "num" => 2,
        "r" => "2coin,infYellow",
        "name" => clienttranslate("Yellow Influence"),
],
    "action_land_3" => [ 
        "type" => "action",
        "ctype" => "land",
        "num" => 3,
        "r" => "upgYellow",
        "name" => clienttranslate("Yellow Upgrade"),
],
    "action_land_4" => [ 
        "type" => "action",
        "ctype" => "land",
        "num" => 4,
        "r" => "3n_food:3cardDraw(land)",
        "name" => clienttranslate("Explore"),
],
// # Water card worker actions (positions 1-4)
    "action_water_1" => [ 
        "type" => "action",
        "ctype" => "water",
        "num" => 1,
        "r" => "reroll,diceMod,diceMod",
        "name" => clienttranslate("Organize"),
],
    "action_water_2" => [ 
        "type" => "action",
        "ctype" => "water",
        "num" => 2,
        "r" => "2food,infBlue",
        "name" => clienttranslate("Blue Influence"),
],
    "action_water_3" => [ 
        "type" => "action",
        "ctype" => "water",
        "num" => 3,
        "r" => "upgBlue",
        "name" => clienttranslate("Blue Upgrade"),
],
    "action_water_4" => [ 
        "type" => "action",
        "ctype" => "water",
        "num" => 4,
        "r" => "3n_food:3cardDraw(water)",
        "name" => clienttranslate("Voyage"),
],
// # Folk card worker actions (positions 1-4)
    "action_folk_1" => [ 
        "type" => "action",
        "ctype" => "folk",
        "num" => 1,
        "r" => "cardSpace(dis)",
        "name" => clienttranslate("Stargaze"),
],
    "action_folk_2" => [ 
        "type" => "action",
        "ctype" => "folk",
        "num" => 2,
        "r" => "infYellow,infBlue,infBlack",
        "name" => clienttranslate("Network"),
],
    "action_folk_3" => [ 
        "type" => "action",
        "ctype" => "folk",
        "num" => 3,
        "r" => "upgGreen(free)",
        "name" => clienttranslate("Base Upgrade"),
],
    "action_folk_4" => [ 
        "type" => "action",
        "ctype" => "folk",
        "num" => 4,
        "r" => "journal,food",
        "name" => clienttranslate("Journal"),
],
// # Inspiration card worker actions (positions 1-4)
    "action_insp_4" => [ 
        "type" => "action",
        "ctype" => "insp",
        "num" => 4,
        "r" => "2food",
        "name" => clienttranslate("Forage"),
],
    "action_insp_3" => [ 
        "type" => "action",
        "ctype" => "insp",
        "num" => 3,
        "r" => "food,infAny",
        "name" => clienttranslate("Network"),
],
    "action_insp_2" => [ 
        "type" => "action",
        "ctype" => "insp",
        "num" => 2,
        "r" => "coin,reroll",
        "name" => clienttranslate("Barter"),
],
    "action_insp_1" => [ 
        "type" => "action",
        "ctype" => "insp",
        "num" => 1,
        "r" => "2coin",
        "name" => clienttranslate("Earn"),
],
            /* --- gen php end action_material --- */

            /* --- gen php begin cardspace_material --- */
// # Space cards (77-113)
// # Comets
    "card_space_77" => [ 
        "create" => 1,
        "num" => 77,
        "r" => "journal",
        "vpexp" => "win_Comet?4:3",
        "tags" => "Comet",
        "tor" => "Journal",
        "tovp" => "If you have more Comet tags than each opponent: 4 VP, otherwise 3 VP",
        "nom" => clienttranslate("Chronicler's Comet"),
],
    "card_space_78" => [ 
        "create" => 1,
        "num" => 78,
        "r" => "journal",
        "vpexp" => "win_Comet?4:3",
        "tags" => "Comet",
        "tor" => "Journal",
        "tovp" => "If you have more Comet tags than each opponent: 4 VP, otherwise 3 VP",
        "nom" => clienttranslate("Chronicler's Comet"),
],
    "card_space_79" => [ 
        "create" => 1,
        "num" => 79,
        "r" => "reroll",
        "vpexp" => "win_Comet?4:2",
        "tags" => "Comet Comet",
        "tor" => "Refresh a Die",
        "tovp" => "If you have more Comet tags than each opponent: 4 VP, otherwise 2 VP",
        "nom" => clienttranslate("Blazing Trail"),
],
    "card_space_80" => [ 
        "create" => 1,
        "num" => 80,
        "r" => "reroll",
        "vpexp" => "win_Comet?4:2",
        "tags" => "Comet Comet",
        "tor" => "Refresh a Die",
        "tovp" => "If you have more Comet tags than each opponent: 4 VP, otherwise 2 VP",
        "nom" => clienttranslate("Blazing Trail"),
],
    "card_space_81" => [ 
        "create" => 1,
        "num" => 81,
        "r" => "reroll",
        "vpexp" => "win_Comet?4:2",
        "tags" => "Comet Comet",
        "tor" => "Refresh a Die",
        "tovp" => "If you have more Comet tags than each opponent: 4 VP, otherwise 2 VP",
        "nom" => clienttranslate("Blazing Trail"),
],
    "card_space_82" => [ 
        "create" => 1,
        "num" => 82,
        "vpexp" => "win_Comet?4:1",
        "tags" => "Comet Comet Comet",
        "tovp" => "If you have more Comet tags than each opponent: 4 VP, otherwise 1 VP",
        "nom" => clienttranslate("Great Omen"),
],
    "card_space_83" => [ 
        "create" => 1,
        "num" => 83,
        "vpexp" => "win_Comet?4:1",
        "tags" => "Comet Comet Comet",
        "tovp" => "If you have more Comet tags than each opponent: 4 VP, otherwise 1 VP",
        "nom" => clienttranslate("Great Omen"),
],
    "card_space_84" => [ 
        "create" => 1,
        "num" => 84,
        "vpexp" => "win_Comet?4:1",
        "tags" => "Comet Comet Comet",
        "tovp" => "If you have more Comet tags than each opponent: 4 VP, otherwise 1 VP",
        "nom" => clienttranslate("Great Omen"),
],
// # Planets
    "card_space_86" => [ 
        "create" => 1,
        "num" => 86,
        "r" => "infYellow",
        "vpexp" => "1+tag_Planet",
        "tags" => "Planet",
        "tor" => "Place an Influence in Yellow Guild",
        "tovp" => "1 VP + 1 VP per Planet tag",
        "nom" => clienttranslate("Jupiter"),
],
    "card_space_87" => [ 
        "create" => 1,
        "num" => 87,
        "r" => "food",
        "vpexp" => "1+tag_Planet",
        "tags" => "Planet",
        "tor" => "Gain Provision",
        "tovp" => "1 VP + 1 VP per Planet tag",
        "nom" => clienttranslate("Mars"),
],
    "card_space_88" => [ 
        "create" => 1,
        "num" => 88,
        "r" => "infCard",
        "vpexp" => "1+tag_Planet",
        "tags" => "Planet",
        "tor" => "Place Influence on a Card",
        "tovp" => "1 VP + 1 VP per Planet tag",
        "nom" => clienttranslate("Mercury"),
],
    "card_space_89" => [ 
        "create" => 1,
        "num" => 89,
        "r" => "infBlack",
        "vpexp" => "1+tag_Planet",
        "tags" => "Planet",
        "tor" => "Place an Influence in Black Guild",
        "tovp" => "1 VP + 1 VP per Planet tag",
        "nom" => clienttranslate("Saturn"),
],
    "card_space_90" => [ 
        "create" => 1,
        "num" => 90,
        "r" => "infBlue",
        "vpexp" => "1+tag_Planet",
        "tags" => "Planet",
        "tor" => "Place an Influence in Blue Guild",
        "tovp" => "1 VP + 1 VP per Planet tag",
        "nom" => clienttranslate("Venus"),
],
// # Stars
    "card_space_91" => [ 
        "create" => 1,
        "num" => 91,
        "r" => "infCard",
        "vpexp" => "min(tag_Planet,tag_Comet,tag_Stars)*3",
        "tags" => "Stars",
        "tor" => "Place Influence on a Card",
        "tovp" => "3 VP per set of Planet, Comet, and Stars tags",
        "nom" => clienttranslate("Celestial Triad"),
],
    "card_space_92" => [ 
        "create" => 1,
        "num" => 92,
        "vpexp" => "tag_City",
        "tags" => "Stars",
        "tovp" => "1 VP per City tag",
        "nom" => clienttranslate("City Light"),
],
    "card_space_93" => [ 
        "create" => 1,
        "num" => 93,
        "vpexp" => "tag_City",
        "tags" => "Stars",
        "tovp" => "1 VP per City tag",
        "nom" => clienttranslate("City Light"),
],
    "card_space_94" => [ 
        "create" => 1,
        "num" => 94,
        "vpexp" => "tag_Harbour",
        "tags" => "Stars",
        "tovp" => "1 VP per Harbour tag",
        "nom" => clienttranslate("Harbor Beacon"),
],
    "card_space_95" => [ 
        "create" => 1,
        "num" => 95,
        "vpexp" => "tag_Harbour",
        "tags" => "Stars",
        "tovp" => "1 VP per Harbour tag",
        "nom" => clienttranslate("Harbor Beacon"),
],
    "card_space_96" => [ 
        "create" => 1,
        "num" => 96,
        "r" => "cardInsp",
        "vpexp" => "tag_card_insp",
        "tags" => "Stars",
        "tor" => "Acquire Inspiration Card",
        "tovp" => "1 VP per Inspiration card",
        "nom" => clienttranslate("Muse's Light"),
],
    "card_space_97" => [ 
        "create" => 1,
        "num" => 97,
        "r" => "infBlack,infMove",
        "vpexp" => "1+(inf_black/2)",
        "tags" => "Stars",
        "tor" => "Place an Influence in Black Guild, then move any influence",
        "tovp" => "1 VP + 1 VP per 2 influence on Black Guild",
        "nom" => clienttranslate("Shadow Guide"),
],
    "card_space_98" => [ 
        "create" => 1,
        "num" => 98,
        "r" => "infBlue,infMove",
        "vpexp" => "1+(inf_blue/2)",
        "tags" => "Stars",
        "tor" => "Place an Influence in Blue Guild, then move any influence",
        "tovp" => "1 VP + 1 VP per 2 influence on Blue Guild",
        "nom" => clienttranslate("Tide Navigator"),
],
    "card_space_99" => [ 
        "create" => 1,
        "num" => 99,
        "r" => "infYellow,infMove",
        "vpexp" => "1+(inf_yellow/2)",
        "tags" => "Stars",
        "tor" => "Place an Influence in Yellow Guild, then move any influence",
        "tovp" => "1 VP + 1 VP per 2 influence on Yellow Guild",
        "nom" => clienttranslate("Path Finder"),
],
    "card_space_100" => [ 
        "create" => 1,
        "num" => 100,
        "r" => "food",
        "vpexp" => "1+tag_Book",
        "tags" => "Stars",
        "tor" => "Gain Provision",
        "tovp" => "1 VP + 1 VP per Book tag",
        "nom" => clienttranslate("Scholar's Star"),
],
    "card_space_101" => [ 
        "create" => 1,
        "num" => 101,
        "r" => "food",
        "vpexp" => "1+tag_Observatory",
        "tags" => "Stars",
        "tor" => "Gain Provision",
        "tovp" => "1 VP + 1 VP per Observatory tag",
        "nom" => clienttranslate("Observer's Eye"),
],
    "card_space_102" => [ 
        "create" => 1,
        "num" => 102,
        "vpexp" => "tag_Sea",
        "tags" => "Stars",
        "tovp" => "1 VP per Open Water tag",
        "nom" => clienttranslate("Ocean Star"),
],
    "card_space_103" => [ 
        "create" => 1,
        "num" => 103,
        "vpexp" => "tag_Sea",
        "tags" => "Stars",
        "tovp" => "1 VP per Open Water tag",
        "nom" => clienttranslate("Ocean Star"),
],
    "card_space_104" => [ 
        "create" => 1,
        "num" => 104,
        "r" => "cardFolk(free)",
        "vpexp" => "min(tag_card_folk,tag_card_space,tag_card_land,tag_card_water)",
        "tags" => "Stars",
        "tor" => "Acquire Townsfolk card (free)",
        "tovp" => "1 VP per set of Townsfolk, Space, Land, and Water cards",
        "nom" => clienttranslate("Collector's Star"),
],
    "card_space_105" => [ 
        "create" => 1,
        "num" => 105,
        "r" => "infCard",
        "vpexp" => "tag_Stars",
        "tags" => "Stars",
        "tor" => "Place Influence on a Card",
        "tovp" => "1 VP per Stars tag",
        "nom" => clienttranslate("Constellation"),
],
    "card_space_106" => [ 
        "create" => 1,
        "num" => 106,
        "r" => "infBlack",
        "vpexp" => "1+tag_upg_black",
        "tags" => "Stars",
        "tor" => "Place an Influence in Black Guild",
        "tovp" => "1 VP + 1 VP per Space upgrade tile",
        "nom" => clienttranslate("Night Compass"),
],
    "card_space_107" => [ 
        "create" => 1,
        "num" => 107,
        "r" => "infBlue",
        "vpexp" => "1+tag_upg_blue",
        "tags" => "Stars",
        "tor" => "Place an Influence in Blue Guild",
        "tovp" => "1 VP + 1 VP per Water upgrade tile",
        "nom" => clienttranslate("Sea Compass"),
],
    "card_space_108" => [ 
        "create" => 1,
        "num" => 108,
        "r" => "cardFolk(free)",
        "vpexp" => "1+tag_upg_green",
        "tags" => "Stars",
        "tor" => "Acquire Townsfolk card (free)",
        "tovp" => "1 VP + 1 VP per Basic upgrade tile",
        "nom" => clienttranslate("Land Guide"),
],
    "card_space_109" => [ 
        "create" => 1,
        "num" => 109,
        "r" => "infYellow",
        "vpexp" => "1+tag_upg_yellow",
        "tags" => "Stars",
        "tor" => "Place an Influence in Yellow Guild",
        "tovp" => "1 VP + 1 VP per Land upgrade tile",
        "nom" => clienttranslate("Earth Compass"),
],
    "card_space_110" => [ 
        "create" => 1,
        "num" => 110,
        "vpexp" => "tag_Vista",
        "tags" => "Stars",
        "tovp" => "1 VP per Vista tag",
        "nom" => clienttranslate("Vista Gleam"),
],
    "card_space_111" => [ 
        "create" => 1,
        "num" => 111,
        "vpexp" => "tag_Vista",
        "tags" => "Stars",
        "tovp" => "1 VP per Vista tag",
        "nom" => clienttranslate("Vista Gleam"),
],
// #Moon and Sun
    "card_space_85" => [ 
        "create" => 1,
        "num" => 85,
        "r" => "infBlue",
        "vpexp" => "tag_Sun?7:3",
        "tags" => "Moon",
        "tor" => "Place an Influence in Blue Guild",
        "tovp" => "If you have a Sun tag: 7 VP, otherwise 3 VP",
        "nom" => clienttranslate("Moon"),
],
    "card_space_112" => [ 
        "create" => 1,
        "num" => 112,
        "r" => "infYellow",
        "vpexp" => "tag_Moon?7:3",
        "tags" => "Sun",
        "tor" => "Place an Influence in Yellow Guild",
        "tovp" => "If you have a Moon tag: 7 VP, otherwise 3 VP",
        "nom" => clienttranslate("Sun"),
],
// # 113|upgPink|1|Comet|Gain special upgrade tile|1 VP|Rare Fragment
// # Home card
    "card_space_1" => [ 
        "create" => 0,
        "num" => 1,
        "vpexp" => "min(tag_upg_any,tag_card_folk)",
        "tags" => "Stars",
        "tovp" => "1 VP per set of Basic upgrade and Townsfolk",
        "nom" => clienttranslate("Capital Sky"),
],
            /* --- gen php end cardspace_material --- */

            /* --- gen php begin cardinsp_material --- */
// # Inspiration cards (1-18)
    "card_insp_1" => [ 
        "create" => 1,
        "num" => 1,
        "collect" => "tag_Comet",
        "goal" => 6,
        "tooltip" => clienttranslate("Have at least 6 Comet tags"),
        "nom" => "Comet Wish",
],
    "card_insp_2" => [ 
        "create" => 1,
        "num" => 2,
        "collect" => "tag_Stars",
        "goal" => 5,
        "tooltip" => clienttranslate("Have at least 5 Stars tags, including starting tag"),
        "nom" => "Starlight",
],
    "card_insp_3" => [ 
        "create" => 1,
        "num" => 3,
        "collect" => "tag_Planet",
        "goal" => 3,
        "tooltip" => clienttranslate("Have at least 3 Planet tags"),
        "nom" => "Planetfall",
],
    "card_insp_4" => [ 
        "create" => 1,
        "num" => 4,
        "collect" => "tag_card_folk",
        "goal" => 6,
        "tooltip" => clienttranslate("Have at least 6 Townsfolk cards, including starting card"),
        "nom" => "Town Square",
],
    "card_insp_5" => [ 
        "create" => 1,
        "num" => 5,
        "collect" => "tag_Library",
        "goal" => 4,
        "tooltip" => clienttranslate("Have at least 4 Library tags, including starting tag"),
        "nom" => "Curiosity",
],
    "card_insp_6" => [ 
        "create" => 1,
        "num" => 6,
        "collect" => "tag_Vista",
        "goal" => 4,
        "tooltip" => clienttranslate("Have at least 4 Vista tags"),
        "nom" => "Grand View",
],
    "card_insp_7" => [ 
        "create" => 1,
        "num" => 7,
        "collect" => "tag_Sea",
        "goal" => 4,
        "tooltip" => clienttranslate("Have at least 4 Open Water tags"),
        "nom" => "Open Seas",
],
    "card_insp_8" => [ 
        "create" => 1,
        "num" => 8,
        "collect" => "tag_Harbour",
        "goal" => 5,
        "tooltip" => clienttranslate("Have at least 5 Harbour tags, including starting tag"),
        "nom" => "Safe Harbors",
],
    "card_insp_9" => [ 
        "create" => 1,
        "num" => 9,
        "collect" => "min(tag_upg_green,tag_upg_black)",
        "goal" => 2,
        "tooltip" => clienttranslate("Have at least two Basic upgrades and two Space upgrades"),
        "nom" => "Dual Study",
],
    "card_insp_10" => [ 
        "create" => 1,
        "num" => 10,
        "collect" => "tag_upg_blue",
        "goal" => 3,
        "tooltip" => clienttranslate("Have at least 3 Water upgrades"),
        "nom" => "Sea Mastery",
],
    "card_insp_11" => [ 
        "create" => 1,
        "num" => 11,
        "collect" => "tag_upg_yellow",
        "goal" => 3,
        "tooltip" => clienttranslate("Have at least 3 Land upgrades"),
        "nom" => "Land Mastery",
],
    "card_insp_12" => [ 
        "create" => 1,
        "num" => 12,
        "collect" => "inf_yellow+inf_blue+inf_black",
        "goal" => 10,
        "tooltip" => clienttranslate("Have at least 10 combined influence in all guilds"),
        "nom" => "Eminence",
],
    "card_insp_13" => [ 
        "create" => 1,
        "num" => 13,
        "collect" => "tag_Observatory",
        "goal" => 4,
        "tooltip" => clienttranslate("Have at least 4 Observatory tags, including starting tag"),
        "nom" => "Starwatch",
],
    "card_insp_14" => [ 
        "create" => 1,
        "num" => 14,
        "collect" => "tag_City",
        "goal" => 5,
        "tooltip" => clienttranslate("Have at least 5 City tags, including starting tag"),
        "nom" => "Metropolis",
],
    "card_insp_15" => [ 
        "create" => 1,
        "num" => 15,
        "collect" => "tracker_food",
        "goal" => 6,
        "tooltip" => clienttranslate("Have at least 6 Provisions"),
        "nom" => "Stockpile",
],
    "card_insp_15" => [ 
        "create" => 1,
        "num" => 15,
        "collect" => "tracker_coin",
        "goal" => 6,
        "tooltip" => clienttranslate("Have at least 6 Silver"),
        "nom" => "Treasure Mountains",
],
    "card_insp_17" => [ 
        "create" => 1,
        "num" => 17,
        "collect" => "tag_card_land",
        "goal" => 8,
        "tooltip" => clienttranslate("Have at least 8 Land cards, including starting card"),
        "nom" => "New Horizons",
],
    "card_insp_18" => [ 
        "create" => 1,
        "num" => 18,
        "collect" => "tag_card_water",
        "goal" => 8,
        "tooltip" => clienttranslate("Have at least 8 Water cards, including starting card"),
        "nom" => "Odyssey",
],
            /* --- gen php end cardinsp_material --- */

            /* --- gen php begin journal_material --- */
// #Journal nodes/positions
    "jpos_0" => [ 
        "location" => "mainboard_1",
        "num" => 0,
        "conn" => "10,15",
],
    "jpos_10" => [ 
        "location" => "mainboard_1",
        "num" => 10,
        "conn" => "20,23",
        "r" => "jtile,pickGreen",
        "gw" => 1,
],
    "jpos_15" => [ 
        "location" => "mainboard_1",
        "num" => 15,
        "conn" => "23,27",
        "r" => "jtile,pickGreen",
],
    "jpos_20" => [ 
        "location" => "mainboard_1",
        "num" => 20,
        "conn" => "40,32",
        "r" => "cardInsp,newDie",
],
    "jpos_23" => [ 
        "location" => "mainboard_1",
        "num" => 23,
        "conn" => "32,36",
        "r" => "cardInsp,newDie",
],
    "jpos_27" => [ 
        "location" => "mainboard_1",
        "num" => 27,
        "conn" => "36,47",
        "r" => "cardInsp,newDie",
],
    "jpos_32" => [ 
        "location" => "mainboard_1",
        "num" => 32,
        "conn" => "40,43",
        "r" => "jtile,pickGreen",
        "gw" => 2,
],
    "jpos_36" => [ 
        "location" => "mainboard_1",
        "num" => 36,
        "conn" => "43,47",
        "r" => "jtile,pickGreen",
],
    "jpos_40" => [ 
        "location" => "mainboard_2",
        "num" => 40,
        "conn" => 50,
        "r" => "upgPink",
],
    "jpos_43" => [ 
        "location" => "mainboard_2",
        "num" => 43,
        "conn" => "50,55",
        "r" => "upgPink",
],
    "jpos_47" => [ 
        "location" => "mainboard_2",
        "num" => 47,
        "conn" => 55,
        "r" => "upgPink",
],
    "jpos_50" => [ 
        "location" => "mainboard_2",
        "num" => 50,
        "conn" => "60,63",
        "r" => "jtile,pickGreen",
        "gw" => 3,
],
    "jpos_55" => [ 
        "location" => "mainboard_2",
        "num" => 55,
        "conn" => "63,67",
        "r" => "jtile,pickGreen",
],
    "jpos_60" => [ 
        "location" => "mainboard_2",
        "num" => 60,
        "conn" => "80,72",
        "r" => "cardInsp,newDie",
],
    "jpos_63" => [ 
        "location" => "mainboard_2",
        "num" => 63,
        "conn" => "72,76",
        "r" => "cardInsp,newDie",
],
    "jpos_67" => [ 
        "location" => "mainboard_2",
        "num" => 67,
        "conn" => "76,87",
        "r" => "cardInsp,newDie",
],
    "jpos_72" => [ 
        "location" => "mainboard_2",
        "num" => 72,
        "conn" => "80,83",
        "r" => "jtile,pickGreen",
        "gw" => 4,
],
    "jpos_76" => [ 
        "location" => "mainboard_2",
        "num" => 76,
        "conn" => "83,87",
        "r" => "jtile,pickGreen",
],
    "jpos_80" => [ 
        "location" => "mainboard_2",
        "num" => 80,
        "conn" => 90,
        "r" => "upgPink",
],
    "jpos_83" => [ 
        "location" => "mainboard_2",
        "num" => 83,
        "conn" => "90,95",
        "r" => "upgPink",
],
    "jpos_87" => [ 
        "location" => "mainboard_2",
        "num" => 87,
        "conn" => 95,
        "r" => "upgPink",
],
    "jpos_90" => [ 
        "location" => "mainboard_2",
        "num" => 90,
        "conn" => "100,102,103",
        "r" => "jtile",
],
    "jpos_95" => [ 
        "location" => "mainboard_2",
        "num" => 95,
        "conn" => "103,106,107",
        "r" => "jtile",
],
    "jpos_100" => [ 
        "location" => "mainboard_3",
        "num" => 100,
        "r" => "infBlack,infBlack,(upgPink/cardInsp)",
],
    "jpos_102" => [ 
        "location" => "mainboard_3",
        "num" => 102,
        "r" => "infAny,infAny",
],
    "jpos_103" => [ 
        "location" => "mainboard_3",
        "num" => 103,
        "r" => "infYellow,infYellow,(upgPink/cardInsp)",
],
    "jpos_106" => [ 
        "location" => "mainboard_3",
        "num" => 106,
        "r" => "infAny,infAny",
],
    "jpos_107" => [ 
        "location" => "mainboard_3",
        "num" => 107,
        "r" => "infBlue,infBlue,(upgPink/cardInsp)",
],
// #Journal tiles
    "jtile_1" => [ 
        "num" => 1,
        "r" => "upgGreen(free)",
],
    "jtile_2" => [ 
        "num" => 2,
        "r" => "infYellow,infYellow",
],
    "jtile_3" => [ 
        "num" => 3,
        "r" => "infBlue,infBlue",
],
    "jtile_4" => [ 
        "num" => 4,
        "r" => "cardFolk(free)",
],
    "jtile_5" => [ 
        "num" => 5,
        "r" => "food,coin",
],
    "jtile_6" => [ 
        "num" => 6,
        "r" => "coin,coin",
],
    "jtile_7" => [ 
        "num" => 7,
        "r" => "pickWorker",
],
    "jtile_8" => [ 
        "num" => 8,
        "r" => "food,food",
],
    "jtile_9" => [ 
        "num" => 9,
        "r" => "infCard,infCard",
],
    "jtile_10" => [ 
        "num" => 10,
        "r" => "infYellow,infBlue",
],
// #Connection requirements side A
    "jconn_0_10_0" => [ 
        "location" => "mainboard_1",
        "name" => clienttranslate("Upper"),
        "num" => 0,
        "conn" => 10,
        "r" => "true",
        "gw" => 1,
],
    "jconn_0_15_0" => [ 
        "location" => "mainboard_1",
        "name" => clienttranslate("Lower"),
        "num" => 0,
        "conn" => 15,
        "r" => "true",
        "gw" => 1,
],
    "jconn_10_20_0" => [ 
        "location" => "mainboard_1",
        "num" => 10,
        "conn" => 20,
        "r" => "tag_City",
        "gw" => 2,
],
    "jconn_10_23_0" => [ 
        "location" => "mainboard_1",
        "num" => 10,
        "conn" => 23,
        "r" => "tag_Vista",
        "gw" => 1,
],
    "jconn_15_23_0" => [ 
        "location" => "mainboard_1",
        "num" => 15,
        "conn" => 23,
        "r" => "tag_Sea",
        "gw" => 1,
],
    "jconn_15_27_0" => [ 
        "location" => "mainboard_1",
        "num" => 15,
        "conn" => 27,
        "r" => "tag_Harbour",
        "gw" => 2,
],
    "jconn_20_40_0" => [ 
        "location" => "mainboard_1",
        "num" => 20,
        "conn" => 40,
        "r" => "tag_upg_black",
        "gw" => 1,
],
    "jconn_20_32_0" => [ 
        "location" => "mainboard_1",
        "num" => 20,
        "conn" => 32,
        "r" => "tag_Observatory",
        "gw" => 2,
],
    "jconn_23_32_0" => [ 
        "location" => "mainboard_1",
        "num" => 23,
        "conn" => 32,
        "r" => "tag_Stars",
        "gw" => 2,
],
    "jconn_23_36_0" => [ 
        "location" => "mainboard_1",
        "num" => 23,
        "conn" => 36,
        "r" => "tag_card_folk",
        "gw" => 2,
],
    "jconn_27_36_0" => [ 
        "location" => "mainboard_1",
        "num" => 27,
        "conn" => 36,
        "r" => "tag_Book",
        "gw" => 2,
],
    "jconn_27_47_0" => [ 
        "location" => "mainboard_1",
        "num" => 27,
        "conn" => 47,
        "r" => "tag_Planet",
        "gw" => 1,
],
    "jconn_32_40_0" => [ 
        "location" => "mainboard_2",
        "num" => 32,
        "conn" => 40,
        "r" => "tag_upg_green",
        "gw" => 1,
],
    "jconn_32_43_0" => [ 
        "location" => "mainboard_2",
        "num" => 32,
        "conn" => 43,
        "r" => "tag_Book",
        "gw" => 2,
],
    "jconn_36_43_0" => [ 
        "location" => "mainboard_2",
        "num" => 36,
        "conn" => 43,
        "r" => "tag_Stars",
        "gw" => 2,
],
    "jconn_36_47_0" => [ 
        "location" => "mainboard_2",
        "num" => 36,
        "conn" => 47,
        "r" => "tag_Observatory",
        "gw" => 2,
],
    "jconn_40_50_0" => [ 
        "location" => "mainboard_2",
        "num" => 40,
        "conn" => 50,
        "r" => "Op_n_infBlack",
],
    "jconn_43_50_0" => [ 
        "location" => "mainboard_2",
        "num" => 43,
        "conn" => 50,
        "r" => "tag_Planet",
        "gw" => 1,
],
    "jconn_43_55_0" => [ 
        "location" => "mainboard_2",
        "num" => 43,
        "conn" => 55,
        "r" => "tag_upg_yellow",
        "gw" => 2,
],
    "jconn_47_55_0" => [ 
        "location" => "mainboard_2",
        "num" => 47,
        "conn" => 55,
        "r" => "Op_n_infBlack",
],
    "jconn_50_60_0" => [ 
        "location" => "mainboard_2",
        "num" => 50,
        "conn" => 60,
        "r" => "tag_Harbour",
        "gw" => 3,
],
    "jconn_50_63_0" => [ 
        "location" => "mainboard_2",
        "num" => 50,
        "conn" => 63,
        "r" => "tag_upg_blue",
        "gw" => 2,
],
    "jconn_55_63_0" => [ 
        "location" => "mainboard_2",
        "num" => 55,
        "conn" => 63,
        "r" => "tag_Comet",
        "gw" => 3,
],
    "jconn_55_67_0" => [ 
        "location" => "mainboard_2",
        "num" => 55,
        "conn" => 67,
        "r" => "tag_City",
        "gw" => 3,
],
    "jconn_60_80_0" => [ 
        "location" => "mainboard_2",
        "num" => 60,
        "conn" => 80,
        "r" => "tag_Comet",
        "gw" => 6,
],
    "jconn_60_72_0" => [ 
        "location" => "mainboard_2",
        "num" => 60,
        "conn" => 72,
        "r" => "tag_card_folk",
        "gw" => 4,
],
    "jconn_63_72_0" => [ 
        "location" => "mainboard_2",
        "num" => 63,
        "conn" => 72,
        "r" => "tag_City",
        "gw" => 4,
],
    "jconn_63_76_0" => [ 
        "location" => "mainboard_2",
        "num" => 63,
        "conn" => 76,
        "r" => "tag_Harbour",
        "gw" => 4,
],
    "jconn_67_76_0" => [ 
        "location" => "mainboard_2",
        "num" => 67,
        "conn" => 76,
        "r" => "tag_card_folk",
        "gw" => 4,
],
    "jconn_67_87_0" => [ 
        "location" => "mainboard_2",
        "num" => 67,
        "conn" => 87,
        "r" => "tag_Stars",
        "gw" => 4,
],
    "jconn_72_80_0" => [ 
        "location" => "mainboard_3",
        "num" => 72,
        "conn" => 80,
        "r" => "tag_Book",
        "gw" => 3,
],
    "jconn_72_83_0" => [ 
        "location" => "mainboard_3",
        "num" => 72,
        "conn" => 83,
        "r" => "tag_Comet",
        "gw" => 6,
],
    "jconn_76_83_0" => [ 
        "location" => "mainboard_3",
        "num" => 76,
        "conn" => 83,
        "r" => "tag_Stars",
        "gw" => 4,
],
    "jconn_76_87_0" => [ 
        "location" => "mainboard_3",
        "num" => 76,
        "conn" => 87,
        "r" => "tag_Sea",
        "gw" => 3,
],
    "jconn_80_90_0" => [ 
        "location" => "mainboard_3",
        "num" => 80,
        "conn" => 90,
        "r" => "tag_upg_any",
        "gw" => 7,
],
    "jconn_83_90_0" => [ 
        "location" => "mainboard_3",
        "num" => 83,
        "conn" => 90,
        "r" => "tag_Vista",
        "gw" => 3,
],
    "jconn_83_95_0" => [ 
        "location" => "mainboard_3",
        "num" => 83,
        "conn" => 95,
        "r" => "tag_Observatory",
        "gw" => 3,
],
    "jconn_87_95_0" => [ 
        "location" => "mainboard_3",
        "num" => 87,
        "conn" => 95,
        "r" => "tag_upg_any",
        "gw" => 7,
],
    "jconn_90_100_0" => [ 
        "location" => "mainboard_3",
        "num" => 90,
        "conn" => 100,
        "r" => "tag_Observatory",
        "gw" => 4,
],
    "jconn_90_102_0" => [ 
        "location" => "mainboard_3",
        "num" => 90,
        "conn" => 102,
        "r" => "Op_n_infBlack",
],
    "jconn_90_103_0" => [ 
        "location" => "mainboard_3",
        "num" => 90,
        "conn" => 103,
        "r" => "tag_Sea",
        "gw" => 4,
],
    "jconn_95_103_0" => [ 
        "location" => "mainboard_3",
        "num" => 95,
        "conn" => 103,
        "r" => "tag_Book",
        "gw" => 4,
],
    "jconn_95_106_0" => [ 
        "location" => "mainboard_3",
        "num" => 95,
        "conn" => 106,
        "r" => "Op_n_infBlack",
],
    "jconn_95_107_0" => [ 
        "location" => "mainboard_3",
        "num" => 95,
        "conn" => 107,
        "r" => "tag_Vista",
        "gw" => 4,
],
// #Connection requirements side B
    "jconn_0_10_1" => [ 
        "location" => "mainboard_1",
        "name" => clienttranslate("Upper"),
        "num" => 0,
        "conn" => 10,
        "r" => "true",
        "gw" => 1,
],
    "jconn_0_15_1" => [ 
        "location" => "mainboard_1",
        "name" => clienttranslate("Lower"),
        "num" => 0,
        "conn" => 15,
        "r" => "true",
        "gw" => 1,
],
    "jconn_10_20_1" => [ 
        "location" => "mainboard_1",
        "num" => 10,
        "conn" => 20,
        "r" => "tag_card_folk",
        "gw" => 2,
],
    "jconn_10_23_1" => [ 
        "location" => "mainboard_1",
        "num" => 10,
        "conn" => 23,
        "r" => "tag_upg_yellow",
        "gw" => 1,
],
    "jconn_15_23_1" => [ 
        "location" => "mainboard_1",
        "num" => 15,
        "conn" => 23,
        "r" => "tag_upg_blue",
        "gw" => 1,
],
    "jconn_15_27_1" => [ 
        "location" => "mainboard_1",
        "num" => 15,
        "conn" => 27,
        "r" => "tag_upg_green",
        "gw" => 2,
],
    "jconn_20_40_1" => [ 
        "location" => "mainboard_1",
        "num" => 20,
        "conn" => 40,
        "r" => "tag_Comet",
        "gw" => 1,
],
    "jconn_20_32_1" => [ 
        "location" => "mainboard_1",
        "num" => 20,
        "conn" => 32,
        "r" => "tag_City",
        "gw" => 2,
],
    "jconn_23_32_1" => [ 
        "location" => "mainboard_1",
        "num" => 23,
        "conn" => 32,
        "r" => "tag_Vista",
        "gw" => 1,
],
    "jconn_23_36_1" => [ 
        "location" => "mainboard_1",
        "num" => 23,
        "conn" => 36,
        "r" => "tag_Sea",
        "gw" => 1,
],
    "jconn_27_36_1" => [ 
        "location" => "mainboard_1",
        "num" => 27,
        "conn" => 36,
        "r" => "tag_Harbour",
        "gw" => 2,
],
    "jconn_27_47_1" => [ 
        "location" => "mainboard_1",
        "num" => 27,
        "conn" => 47,
        "r" => "tag_Stars",
        "gw" => 3,
],
    "jconn_32_40_1" => [ 
        "location" => "mainboard_2",
        "num" => 32,
        "conn" => 40,
        "r" => "tag_Planet",
        "gw" => 1,
],
    "jconn_32_43_1" => [ 
        "location" => "mainboard_2",
        "num" => 32,
        "conn" => 43,
        "r" => "tag_Harbour",
        "gw" => 2,
],
    "jconn_36_43_1" => [ 
        "location" => "mainboard_2",
        "num" => 36,
        "conn" => 43,
        "r" => "tag_City",
        "gw" => 2,
],
    "jconn_36_47_1" => [ 
        "location" => "mainboard_2",
        "num" => 36,
        "conn" => 47,
        "r" => "tag_Comet",
        "gw" => 1,
],
    "jconn_40_50_1" => [ 
        "location" => "mainboard_2",
        "num" => 40,
        "conn" => 50,
        "r" => "Op_n_infBlue,Op_n_infYellow",
],
    "jconn_43_50_1" => [ 
        "location" => "mainboard_2",
        "num" => 43,
        "conn" => 50,
        "r" => "tag_Sea",
        "gw" => 2,
],
    "jconn_43_55_1" => [ 
        "location" => "mainboard_2",
        "num" => 43,
        "conn" => 55,
        "r" => "tag_Vista",
        "gw" => 2,
],
    "jconn_47_55_1" => [ 
        "location" => "mainboard_2",
        "num" => 47,
        "conn" => 55,
        "r" => "Op_n_infBlue,Op_n_infYellow",
],
    "jconn_50_60_1" => [ 
        "location" => "mainboard_2",
        "num" => 50,
        "conn" => 60,
        "r" => "tag_card_folk",
        "gw" => 3,
],
    "jconn_50_63_1" => [ 
        "location" => "mainboard_2",
        "num" => 50,
        "conn" => 63,
        "r" => "tag_upg_yellow",
        "gw" => 2,
],
    "jconn_55_63_1" => [ 
        "location" => "mainboard_2",
        "num" => 55,
        "conn" => 63,
        "r" => "tag_upg_blue",
        "gw" => 2,
],
    "jconn_55_67_1" => [ 
        "location" => "mainboard_2",
        "num" => 55,
        "conn" => 67,
        "r" => "tag_card_folk",
        "gw" => 3,
],
    "jconn_60_80_1" => [ 
        "location" => "mainboard_2",
        "num" => 60,
        "conn" => 80,
        "r" => "tag_upg_black",
        "gw" => 2,
],
    "jconn_60_72_1" => [ 
        "location" => "mainboard_2",
        "num" => 60,
        "conn" => 72,
        "r" => "tag_Observatory",
        "gw" => 2,
],
    "jconn_63_72_1" => [ 
        "location" => "mainboard_2",
        "num" => 63,
        "conn" => 72,
        "r" => "tag_Sea",
        "gw" => 3,
],
    "jconn_63_76_1" => [ 
        "location" => "mainboard_2",
        "num" => 63,
        "conn" => 76,
        "r" => "tag_Book",
        "gw" => 2,
],
    "jconn_67_76_1" => [ 
        "location" => "mainboard_2",
        "num" => 67,
        "conn" => 76,
        "r" => "tag_Vista",
        "gw" => 3,
],
    "jconn_67_87_1" => [ 
        "location" => "mainboard_2",
        "num" => 67,
        "conn" => 87,
        "r" => "max(tag_Sun,tag_Moon)",
        "gw" => 1,
],
    "jconn_72_80_1" => [ 
        "location" => "mainboard_3",
        "num" => 72,
        "conn" => 80,
        "r" => "tag_Vista",
        "gw" => 3,
],
    "jconn_72_83_1" => [ 
        "location" => "mainboard_3",
        "num" => 72,
        "conn" => 83,
        "r" => "tag_City",
        "gw" => 4,
],
    "jconn_76_83_1" => [ 
        "location" => "mainboard_3",
        "num" => 76,
        "conn" => 83,
        "r" => "tag_Harbour",
        "gw" => 4,
],
    "jconn_76_87_1" => [ 
        "location" => "mainboard_3",
        "num" => 76,
        "conn" => 87,
        "r" => "tag_Sea",
        "gw" => 3,
],
    "jconn_80_90_1" => [ 
        "location" => "mainboard_3",
        "num" => 80,
        "conn" => 90,
        "r" => "Op_n_infBlue,Op_n_infBlack",
],
    "jconn_83_90_1" => [ 
        "location" => "mainboard_3",
        "num" => 83,
        "conn" => 90,
        "r" => "tag_card_folk",
        "gw" => 5,
],
    "jconn_83_95_1" => [ 
        "location" => "mainboard_3",
        "num" => 83,
        "conn" => 95,
        "r" => "tag_upg_any",
        "gw" => 7,
],
    "jconn_87_95_1" => [ 
        "location" => "mainboard_3",
        "num" => 87,
        "conn" => 95,
        "r" => "Op_n_infYellow,Op_n_infBlack",
],
    "jconn_90_100_1" => [ 
        "location" => "mainboard_3",
        "num" => 90,
        "conn" => 100,
        "r" => "tag_Stars",
        "gw" => 5,
],
    "jconn_90_102_1" => [ 
        "location" => "mainboard_3",
        "num" => 90,
        "conn" => 102,
        "r" => "Op_n_infBlack",
],
    "jconn_90_103_1" => [ 
        "location" => "mainboard_3",
        "num" => 90,
        "conn" => 103,
        "r" => "tag_City",
        "gw" => 5,
],
    "jconn_95_103_1" => [ 
        "location" => "mainboard_3",
        "num" => 95,
        "conn" => 103,
        "r" => "tag_Harbour",
        "gw" => 5,
],
    "jconn_95_106_1" => [ 
        "location" => "mainboard_3",
        "num" => 95,
        "conn" => 106,
        "r" => "Op_n_infBlack",
],
    "jconn_95_107_1" => [ 
        "location" => "mainboard_3",
        "num" => 95,
        "conn" => 107,
        "r" => "tag_Comet",
        "gw" => 7,
],
            /* --- gen php end journal_material --- */

            /* --- gen php begin caravan_material --- */
// #Player board randomized Bonuses
    "pbonus_1_0" => [ 
        "num" => 1,
        "conn" => 0,
],
    "pbonus_1_1" => [ 
        "num" => 1,
        "conn" => 1,
],
    "pbonus_1_2" => [ 
        "num" => 1,
        "conn" => 2,
        "r" => "coin",
],
    "pbonus_1_3" => [ 
        "num" => 1,
        "conn" => 3,
        "r" => "food",
],
    "pbonus_1_4" => [ 
        "num" => 1,
        "conn" => 4,
],
    "pbonus_1_5" => [ 
        "num" => 1,
        "conn" => 5,
],
    "pbonus_1_6" => [ 
        "num" => 1,
        "conn" => 6,
],
    "pbonus_1_7" => [ 
        "num" => 1,
        "conn" => 7,
        "r" => "infMove",
],
    "pbonus_1_8" => [ 
        "num" => 1,
        "conn" => 8,
],
    "pbonus_1_9" => [ 
        "num" => 1,
        "conn" => 9,
],
    "pbonus_1_10" => [ 
        "num" => 1,
        "conn" => 10,
        "r" => "food",
],
    "pbonus_1_11" => [ 
        "num" => 1,
        "conn" => 11,
],
    "pbonus_1_12" => [ 
        "num" => 1,
        "conn" => 12,
        "r" => "food",
],
    "pbonus_1_13" => [ 
        "num" => 1,
        "conn" => 13,
],
    "pbonus_1_14" => [ 
        "num" => 1,
        "conn" => 14,
        "r" => "infCard",
],
    "pbonus_1_15" => [ 
        "num" => 1,
        "conn" => 15,
        "r" => "reroll",
],
    "pbonus_1_16" => [ 
        "num" => 1,
        "conn" => 16,
],
    "pbonus_1_17" => [ 
        "num" => 1,
        "conn" => 17,
        "r" => "coin",
],
    "pbonus_2_0" => [ 
        "num" => 2,
        "conn" => 0,
],
    "pbonus_2_1" => [ 
        "num" => 2,
        "conn" => 1,
],
    "pbonus_2_2" => [ 
        "num" => 2,
        "conn" => 2,
        "r" => "food",
],
    "pbonus_2_3" => [ 
        "num" => 2,
        "conn" => 3,
],
    "pbonus_2_4" => [ 
        "num" => 2,
        "conn" => 4,
        "r" => "infMove",
],
    "pbonus_2_5" => [ 
        "num" => 2,
        "conn" => 5,
],
    "pbonus_2_6" => [ 
        "num" => 2,
        "conn" => 6,
        "r" => "coin",
],
    "pbonus_2_7" => [ 
        "num" => 2,
        "conn" => 7,
],
    "pbonus_2_8" => [ 
        "num" => 2,
        "conn" => 8,
        "r" => "infCard",
],
    "pbonus_2_9" => [ 
        "num" => 2,
        "conn" => 9,
],
    "pbonus_2_10" => [ 
        "num" => 2,
        "conn" => 10,
        "r" => "food",
],
    "pbonus_2_11" => [ 
        "num" => 2,
        "conn" => 11,
],
    "pbonus_2_12" => [ 
        "num" => 2,
        "conn" => 12,
],
    "pbonus_2_13" => [ 
        "num" => 2,
        "conn" => 13,
        "r" => "food",
],
    "pbonus_2_14" => [ 
        "num" => 2,
        "conn" => 14,
],
    "pbonus_2_15" => [ 
        "num" => 2,
        "conn" => 15,
        "r" => "coin",
],
    "pbonus_2_16" => [ 
        "num" => 2,
        "conn" => 16,
],
    "pbonus_2_17" => [ 
        "num" => 2,
        "conn" => 17,
        "r" => "reroll",
],
    "pbonus_3_0" => [ 
        "num" => 3,
        "conn" => 0,
],
    "pbonus_3_1" => [ 
        "num" => 3,
        "conn" => 1,
        "r" => "reroll",
],
    "pbonus_3_2" => [ 
        "num" => 3,
        "conn" => 2,
],
    "pbonus_3_3" => [ 
        "num" => 3,
        "conn" => 3,
        "r" => "infCard",
],
    "pbonus_3_4" => [ 
        "num" => 3,
        "conn" => 4,
        "r" => "food",
],
    "pbonus_3_5" => [ 
        "num" => 3,
        "conn" => 5,
],
    "pbonus_3_6" => [ 
        "num" => 3,
        "conn" => 6,
],
    "pbonus_3_7" => [ 
        "num" => 3,
        "conn" => 7,
],
    "pbonus_3_8" => [ 
        "num" => 3,
        "conn" => 8,
        "r" => "food",
],
    "pbonus_3_9" => [ 
        "num" => 3,
        "conn" => 9,
],
    "pbonus_3_10" => [ 
        "num" => 3,
        "conn" => 10,
],
    "pbonus_3_11" => [ 
        "num" => 3,
        "conn" => 11,
],
    "pbonus_3_12" => [ 
        "num" => 3,
        "conn" => 12,
        "r" => "infMove",
],
    "pbonus_3_13" => [ 
        "num" => 3,
        "conn" => 13,
        "r" => "coin",
],
    "pbonus_3_14" => [ 
        "num" => 3,
        "conn" => 14,
],
    "pbonus_3_15" => [ 
        "num" => 3,
        "conn" => 15,
        "r" => "coin",
],
    "pbonus_3_16" => [ 
        "num" => 3,
        "conn" => 16,
],
    "pbonus_3_17" => [ 
        "num" => 3,
        "conn" => 17,
        "r" => "food",
],
    "pbonus_4_0" => [ 
        "num" => 4,
        "conn" => 0,
],
    "pbonus_4_1" => [ 
        "num" => 4,
        "conn" => 1,
        "r" => "coin",
],
    "pbonus_4_2" => [ 
        "num" => 4,
        "conn" => 2,
],
    "pbonus_4_3" => [ 
        "num" => 4,
        "conn" => 3,
        "r" => "reroll",
],
    "pbonus_4_4" => [ 
        "num" => 4,
        "conn" => 4,
        "r" => "food",
],
    "pbonus_4_5" => [ 
        "num" => 4,
        "conn" => 5,
],
    "pbonus_4_6" => [ 
        "num" => 4,
        "conn" => 6,
        "r" => "food",
],
    "pbonus_4_7" => [ 
        "num" => 4,
        "conn" => 7,
],
    "pbonus_4_8" => [ 
        "num" => 4,
        "conn" => 8,
],
    "pbonus_4_9" => [ 
        "num" => 4,
        "conn" => 9,
],
    "pbonus_4_10" => [ 
        "num" => 4,
        "conn" => 10,
],
    "pbonus_4_11" => [ 
        "num" => 4,
        "conn" => 11,
        "r" => "infCard",
],
    "pbonus_4_12" => [ 
        "num" => 4,
        "conn" => 12,
],
    "pbonus_4_13" => [ 
        "num" => 4,
        "conn" => 13,
        "r" => "infMove",
],
    "pbonus_4_14" => [ 
        "num" => 4,
        "conn" => 14,
        "r" => "food",
],
    "pbonus_4_15" => [ 
        "num" => 4,
        "conn" => 15,
],
    "pbonus_4_16" => [ 
        "num" => 4,
        "conn" => 16,
        "r" => "coin",
],
    "pbonus_4_17" => [ 
        "num" => 4,
        "conn" => 17,
],
// #AI board randomized Bonuses
    "aibonus_1_0" => [ 
        "num" => 1,
        "conn" => 0,
        "r" => "ai_cardSpace",
],
    "aibonus_1_1" => [ 
        "num" => 1,
        "conn" => 1,
        "r" => "ai_comet",
],
    "aibonus_1_2" => [ 
        "num" => 1,
        "conn" => 2,
        "r" => "infBlack",
],
    "aibonus_1_3" => [ 
        "num" => 1,
        "conn" => 3,
        "r" => "ai_comet",
],
    "aibonus_1_4" => [ 
        "num" => 1,
        "conn" => 4,
        "r" => "ai_cardSpace",
],
    "aibonus_1_5" => [ 
        "num" => 1,
        "conn" => 5,
        "r" => "ai_comet",
],
    "aibonus_1_6" => [ 
        "num" => 1,
        "conn" => 6,
        "r" => "ai_cardSpace",
],
    "aibonus_1_7" => [ 
        "num" => 1,
        "conn" => 7,
],
    "aibonus_1_8" => [ 
        "num" => 1,
        "conn" => 8,
        "r" => "ai_cardSpace",
],
    "aibonus_1_9" => [ 
        "num" => 1,
        "conn" => 9,
],
    "aibonus_1_10" => [ 
        "num" => 1,
        "conn" => 10,
        "r" => "ai_cardFolk",
],
    "aibonus_1_11" => [ 
        "num" => 1,
        "conn" => 11,
],
    "aibonus_1_12" => [ 
        "num" => 1,
        "conn" => 12,
        "r" => "ai_cardSpace",
],
    "aibonus_1_13" => [ 
        "num" => 1,
        "conn" => 13,
],
    "aibonus_1_14" => [ 
        "num" => 1,
        "conn" => 14,
        "r" => "ai_cardWater",
],
    "aibonus_1_15" => [ 
        "num" => 1,
        "conn" => 15,
],
    "aibonus_1_16" => [ 
        "num" => 1,
        "conn" => 16,
        "r" => "infBlack",
],
    "aibonus_1_17" => [ 
        "num" => 1,
        "conn" => 17,
],
    "aibonus_1_18" => [ 
        "num" => 1,
        "conn" => 18,
        "r" => "ai_cardLand",
],
    "aibonus_1_19" => [ 
        "num" => 1,
        "conn" => 19,
],
    "aibonus_1_20" => [ 
        "num" => 1,
        "conn" => 20,
        "r" => "ai_cardSpace",
],
    "aibonus_2_0" => [ 
        "num" => 2,
        "conn" => 0,
        "r" => "ai_cardWater",
],
    "aibonus_2_1" => [ 
        "num" => 2,
        "conn" => 1,
        "r" => "infBlack",
],
    "aibonus_2_2" => [ 
        "num" => 2,
        "conn" => 2,
        "r" => "ai_cardSpace",
],
    "aibonus_2_3" => [ 
        "num" => 2,
        "conn" => 3,
        "r" => "infYellow",
],
    "aibonus_2_4" => [ 
        "num" => 2,
        "conn" => 4,
        "r" => "cardInsp",
],
    "aibonus_2_5" => [ 
        "num" => 2,
        "conn" => 5,
        "r" => "infBlue",
],
    "aibonus_2_6" => [ 
        "num" => 2,
        "conn" => 6,
        "r" => "ai_cardSpace",
],
    "aibonus_2_7" => [ 
        "num" => 2,
        "conn" => 7,
        "r" => "ai_comet",
],
    "aibonus_2_8" => [ 
        "num" => 2,
        "conn" => 8,
        "r" => "ai_cardLand",
],
    "aibonus_2_9" => [ 
        "num" => 2,
        "conn" => 9,
],
    "aibonus_2_10" => [ 
        "num" => 2,
        "conn" => 10,
        "r" => "infBlack",
],
    "aibonus_2_11" => [ 
        "num" => 2,
        "conn" => 11,
],
    "aibonus_2_12" => [ 
        "num" => 2,
        "conn" => 12,
        "r" => "ai_cardWater",
],
    "aibonus_2_13" => [ 
        "num" => 2,
        "conn" => 13,
        "r" => "ai_comet",
],
    "aibonus_2_14" => [ 
        "num" => 2,
        "conn" => 14,
        "r" => "ai_cardSpace",
],
    "aibonus_2_15" => [ 
        "num" => 2,
        "conn" => 15,
],
    "aibonus_2_16" => [ 
        "num" => 2,
        "conn" => 16,
        "r" => "ai_cardWater",
],
    "aibonus_2_17" => [ 
        "num" => 2,
        "conn" => 17,
],
    "aibonus_2_18" => [ 
        "num" => 2,
        "conn" => 18,
        "r" => "ai_cardSpace",
],
    "aibonus_2_19" => [ 
        "num" => 2,
        "conn" => 19,
],
    "aibonus_2_20" => [ 
        "num" => 2,
        "conn" => 20,
        "r" => "ai_cardLand",
],
    "aibonus_3_0" => [ 
        "num" => 3,
        "conn" => 0,
        "r" => "ai_cardWater",
],
    "aibonus_3_1" => [ 
        "num" => 3,
        "conn" => 1,
        "r" => "infBlue",
],
    "aibonus_3_2" => [ 
        "num" => 3,
        "conn" => 2,
        "r" => "cardInsp",
],
    "aibonus_3_3" => [ 
        "num" => 3,
        "conn" => 3,
        "r" => "infYellow",
],
    "aibonus_3_4" => [ 
        "num" => 3,
        "conn" => 4,
        "r" => "ai_cardLand",
],
    "aibonus_3_5" => [ 
        "num" => 3,
        "conn" => 5,
        "r" => "infBlack",
],
    "aibonus_3_6" => [ 
        "num" => 3,
        "conn" => 6,
        "r" => "infAny",
],
    "aibonus_3_7" => [ 
        "num" => 3,
        "conn" => 7,
        "r" => "ai_infCard",
],
    "aibonus_3_8" => [ 
        "num" => 3,
        "conn" => 8,
        "r" => "ai_cardSpace",
],
    "aibonus_3_9" => [ 
        "num" => 3,
        "conn" => 9,
        "r" => "ai_comet",
],
    "aibonus_3_10" => [ 
        "num" => 3,
        "conn" => 10,
        "r" => "cardInsp",
],
    "aibonus_3_11" => [ 
        "num" => 3,
        "conn" => 11,
        "r" => "ai_comet",
],
    "aibonus_3_12" => [ 
        "num" => 3,
        "conn" => 12,
        "r" => "ai_cardSpace",
],
    "aibonus_3_13" => [ 
        "num" => 3,
        "conn" => 13,
        "r" => "ai_infCard",
],
    "aibonus_3_14" => [ 
        "num" => 3,
        "conn" => 14,
        "r" => "infBlack",
],
    "aibonus_3_15" => [ 
        "num" => 3,
        "conn" => 15,
        "r" => "ai_infCard",
],
    "aibonus_3_16" => [ 
        "num" => 3,
        "conn" => 16,
        "r" => "ai_cardSpace",
],
    "aibonus_3_17" => [ 
        "num" => 3,
        "conn" => 17,
        "r" => "ai_infCard",
],
    "aibonus_3_18" => [ 
        "num" => 3,
        "conn" => 18,
        "r" => "cardInsp",
],
    "aibonus_3_19" => [ 
        "num" => 3,
        "conn" => 19,
        "r" => "ai_infCard",
],
    "aibonus_3_20" => [ 
        "num" => 3,
        "conn" => 20,
        "r" => "infBlack",
],
    "aibonus_4_0" => [ 
        "num" => 4,
        "conn" => 0,
        "r" => "ai_infCard",
],
    "aibonus_4_1" => [ 
        "num" => 4,
        "conn" => 1,
        "r" => "ai_comet",
],
    "aibonus_4_2" => [ 
        "num" => 4,
        "conn" => 2,
        "r" => "ai_cardSpace",
],
    "aibonus_4_3" => [ 
        "num" => 4,
        "conn" => 3,
        "r" => "ai_cardFolk",
],
    "aibonus_4_4" => [ 
        "num" => 4,
        "conn" => 4,
        "r" => "ai_infCard",
],
    "aibonus_4_5" => [ 
        "num" => 4,
        "conn" => 5,
        "r" => "ai_comet",
],
    "aibonus_4_6" => [ 
        "num" => 4,
        "conn" => 6,
        "r" => "cardInsp",
],
    "aibonus_4_7" => [ 
        "num" => 4,
        "conn" => 7,
],
    "aibonus_4_8" => [ 
        "num" => 4,
        "conn" => 8,
        "r" => "ai_cardSpace",
],
    "aibonus_4_9" => [ 
        "num" => 4,
        "conn" => 9,
],
    "aibonus_4_10" => [ 
        "num" => 4,
        "conn" => 10,
        "r" => "infBlack",
],
    "aibonus_4_11" => [ 
        "num" => 4,
        "conn" => 11,
],
    "aibonus_4_12" => [ 
        "num" => 4,
        "conn" => 12,
        "r" => "ai_cardSpace",
],
    "aibonus_4_13" => [ 
        "num" => 4,
        "conn" => 13,
],
    "aibonus_4_14" => [ 
        "num" => 4,
        "conn" => 14,
        "r" => "ai_cardFolk",
],
    "aibonus_4_15" => [ 
        "num" => 4,
        "conn" => 15,
],
    "aibonus_4_16" => [ 
        "num" => 4,
        "conn" => 16,
        "r" => "ai_cardLand",
],
    "aibonus_4_17" => [ 
        "num" => 4,
        "conn" => 17,
        "r" => "ai_comet",
],
    "aibonus_4_18" => [ 
        "num" => 4,
        "conn" => 18,
        "r" => "ai_cardWater",
],
    "aibonus_4_19" => [ 
        "num" => 4,
        "conn" => 19,
],
    "aibonus_4_20" => [ 
        "num" => 4,
        "conn" => 20,
        "r" => "infBlack",
],
            /* --- gen php end caravan_material --- */

            /* --- gen php begin scheme_material --- */
// # 6 Scheme Cards for Solo AI
// # t: blue or red
// # c: silver value (0-2) - how far AI moves on Resource Track
// # r1: first action AI attempts (primary)
// # r2: second/fallback action if first is impossible r2 is also used on rest: AI acquires based on this
// # p: special (pink) upgrade tile priority
// # comet: 1 if card has comet icon (checked on rest), 0 otherwise
// # Blue cards
    "card_scheme_1" => [ 
        "create" => 1,
        "type" => "card card_scheme",
        "location" => "deck_scheme",
        "num" => 1,
        "t" => "blue",
        "c" => 2,
        "r1" => "ai_placeWorker(green)",
        "r2" => "ai_focusAction",
        "p" => 6,
        "comet" => 1,
        "nom" => "Worker or Focus",
],
    "card_scheme_2" => [ 
        "create" => 1,
        "type" => "card card_scheme",
        "location" => "deck_scheme",
        "num" => 2,
        "t" => "blue",
        "c" => 0,
        "r1" => "ai_placeWorker(green/blue)",
        "r2" => "infBlue,infYellow,infBlack",
        "p" => 8,
        "comet" => 1,
        "nom" => "Worker or Influence",
],
    "card_scheme_3" => [ 
        "create" => 1,
        "type" => "card card_scheme",
        "location" => "deck_scheme",
        "num" => 3,
        "t" => "blue",
        "c" => 0,
        "r1" => "ai_placeWorker(green/yellow)",
        "r2" => "ai_upgAny",
        "p" => 10,
        "comet" => 0,
        "nom" => "Worker or Upgrade",
],
// # Red cards
    "card_scheme_4" => [ 
        "create" => 1,
        "type" => "card card_scheme",
        "location" => "deck_scheme",
        "num" => 4,
        "t" => "red",
        "c" => 1,
        "r1" => "2n_infBlue:ai_cardWater",
        "r2" => "infBlue,ai_upgAny",
        "p" => 5,
        "comet" => 1,
        "nom" => "Buy Water or Upgrade",
],
    "card_scheme_5" => [ 
        "create" => 1,
        "type" => "card card_scheme",
        "location" => "deck_scheme",
        "num" => 5,
        "t" => "red",
        "c" => 2,
        "r1" => "2n_infBlack:ai_cardSpace",
        "r2" => "infBlack,ai_cardFolk",
        "p" => 3,
        "comet" => 1,
        "nom" => "Buy Space or Buy Townsfolk",
],
    "card_scheme_6" => [ 
        "create" => 1,
        "type" => "card card_scheme",
        "location" => "deck_scheme",
        "num" => 6,
        "t" => "red",
        "c" => 1,
        "r1" => "2n_infYellow:ai_cardLand",
        "r2" => "infYellow,infYellow,ai_infCard",
        "p" => 1,
        "comet" => 0,
        "nom" => "Buy Land or Influence",
],
// #res track
// #t: priority color
// #c: inspiration card order
    "spot_res_0" => [ 
        "create" => 0,
        "type" => "spot spot_res",
        "num" => 0,
        "t" => "black",
        "c" => 2,
],
    "spot_res_1" => [ 
        "create" => 0,
        "type" => "spot spot_res",
        "num" => 1,
        "t" => "black",
        "c" => 2,
],
    "spot_res_2" => [ 
        "create" => 0,
        "type" => "spot spot_res",
        "num" => 2,
        "t" => "black",
        "c" => 2,
],
    "spot_res_3" => [ 
        "create" => 0,
        "type" => "spot spot_res",
        "num" => 3,
        "t" => "blue",
        "c" => 3,
],
    "spot_res_4" => [ 
        "create" => 0,
        "type" => "spot spot_res",
        "num" => 4,
        "t" => "blue",
        "c" => 4,
],
    "spot_res_5" => [ 
        "create" => 0,
        "type" => "spot spot_res",
        "num" => 5,
        "t" => "yellow",
        "c" => 4,
],
    "spot_res_6" => [ 
        "create" => 0,
        "type" => "spot spot_res",
        "num" => 6,
        "t" => "yellow",
        "c" => 4,
],
    "spot_res_7" => [ 
        "create" => 0,
        "type" => "spot spot_res",
        "num" => 7,
        "t" => "green",
        "c" => 3,
],
// #AU boards
// #t: focus action
// #r1: rest action
// #r2: res tracker bonus
    "aiboard_1" => [ 
        "create" => 0,
        "num" => 1,
        "t" => "ai_cardSpace",
        "r1" => "ai_cardSpace",
        "r2" => "ai_comet",
        "nom" => "Aida the Stargazer",
],
    "aiboard_2" => [ 
        "create" => 0,
        "num" => 2,
        "t" => "ai_upgAny",
        "r1" => "ai_upgAny",
        "r2" => "infYellow",
        "nom" => "Aida the Inventor",
],
    "aiboard_3" => [ 
        "create" => 0,
        "num" => 3,
        "t" => "ai_cardFolk",
        "r1" => "ai_cardFolk",
        "r2" => "ai_cardFolk",
        "nom" => "Aida the Mayor",
],
    "aiboard_4" => [ 
        "create" => 0,
        "num" => 4,
        "t" => "ai_journal",
        "r1" => "ai_infCard",
        "r2" => "infBlack",
        "nom" => "Aida the Wayfarer",
],
            /* --- gen php end scheme_material --- */
        ];
    }

    public function get(): array {
        return $this->token_types;
    }

    /**
     * This has to be called from "initTable" method of game which is when db is conected but action is not started yet
     */
    public function adjustMaterial(Game $game) {
        if ($this->adjusted) {
            return $this->token_types;
        }
        $this->adjusted = true;
        // ... do something reading number or palyer of game options with material

        return $this->token_types;
    }

    function getRulesFor($token_id, $field = "r", $default = "") {
        $tt = $this->token_types;
        $key = $token_id;
        while ($key) {
            $data = $tt[$key] ?? null;
            if ($data) {
                if ($field === "*") {
                    $data["_key"] = $key;
                    return $data;
                }
                $value = $data[$field] ?? null;
                if ($value !== null) {
                    return $value;
                }
            }
            $new_key = $this->getPartsPrefix($key, -1);
            if ($new_key == $key) {
                break;
            }
            $key = $new_key;
        }
        //$this->systemAssertTrue("bad token $token_id for rule $field", false);
        return $default;
    }

    /** Find stuff in material file */
    function find(string $field, ?string $value, bool $ignorecase = true) {
        foreach ($this->token_types as $key => $rules) {
            $cur = $rules[$field] ?? null;
            if ($cur == $value) {
                return $key;
            }
            if ($ignorecase && is_string($cur) && strcasecmp($cur, $value) == 0) {
                return $key;
            }
        }
        return null;
    }
    function findByName(string $value, bool $ignorecase = true) {
        return $this->find("name", $value, $ignorecase);
    }

    /**
     * Return $i parts of string (part is chunk separated by _
     * I.e.
     * getPartsPrefix("a_b_c",2)=="a_b"
     *
     * If $i is negative - it will means how much remove from tail, i.e
     * getPartsPrefix("a_b_c",-1)=="a_b"
     */
    static function getPartsPrefix($haystack, $i) {
        $parts = explode("_", $haystack);
        $len = count($parts);
        if ($i < 0) {
            $i = $len + $i;
        }
        if ($i <= 0) {
            return "";
        }
        for (; $i < $len; $i++) {
            unset($parts[$i]);
        }
        return implode("_", $parts);
    }

    /**
     * Set rules for a token (used for testing)
     */
    function setRulesFor(string $token_id, array $rules): void {
        if (!isset($this->token_types[$token_id])) {
            $this->token_types[$token_id] = [];
        }
        $this->token_types[$token_id] = array_merge($this->token_types[$token_id], $rules);
    }
}
