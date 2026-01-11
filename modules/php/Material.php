<?php

declare(strict_types=1);

namespace Bga\Games\wayfarers;

class Material {
    const MA_OK = 0;
    const MA_ERR_COST = 1;
    const MA_ERR_PREREQ = 2;
    const MA_ERR_OCCUPIED = 3;
    const MA_ERR_MAX = 4;
    const MA_ERR_NOT_ENOUGH = 5;
    const MA_ERR_NOT_APPLICABLE = 6;

    private array $token_types;
    private bool $adjusted = false;
    public function __construct() {
        $this->token_types = [
            // #error codes - MANUAL ENTRY
            "err_0" => [
                //
                "code" => Material::MA_OK,
                "type" => "err",
                "name" => clienttranslate("Ok"),
            ],
            "err_1" => [
                //
                "code" => Material::MA_ERR_COST,
                "type" => "err",
                "name" => clienttranslate("Insufficient Resources"),
            ],

            "err_2" => [
                //
                "code" => Material::MA_ERR_PREREQ,
                "type" => "err",
                "name" => clienttranslate("Prerequisites are not fullfilled"),
            ],
            "err_3" => [
                //
                "code" => Material::MA_ERR_OCCUPIED,
                "type" => "err",
                "name" => clienttranslate("Location is occupied"),
            ],
            "err_4" => [
                //
                "code" => Material::MA_ERR_MAX,
                "type" => "err",
                "name" => clienttranslate("Maximum capacity is reached"),
            ],
            "err_5" => [
                //
                "code" => Material::MA_ERR_NOT_ENOUGH,
                "type" => "err",
                "name" => clienttranslate("Not enough"),
            ],
            "err_6" => [
                //
                "code" => Material::MA_ERR_NOT_APPLICABLE,
                "type" => "err",
                "name" => clienttranslate("Not applicable"),
            ],

            /* --- gen php begin loc_material --- */
            "deck_folk" => [
                "type" => "location",
                "showtooltip" => 0,
                "create" => 0,
                "name" => clienttranslate("Townfolk Cards"),
                "location" => "supply",
                "scope" => "global",
                "counter" => "public",
                "content" => "hidden",
            ],
            "deck_land" => [
                "type" => "location",
                "showtooltip" => 0,
                "create" => 0,
                "name" => clienttranslate("Land Cards"),
                "location" => "supply",
                "scope" => "global",
                "counter" => "public",
                "content" => "hidden",
            ],
            "deck_water" => [
                "type" => "location",
                "showtooltip" => 0,
                "create" => 0,
                "name" => clienttranslate("Water Cards"),
                "location" => "supply",
                "scope" => "global",
                "counter" => "public",
                "content" => "hidden",
            ],
            "deck_space" => [
                "type" => "location",
                "showtooltip" => 0,
                "create" => 0,
                "name" => clienttranslate("Space Cards"),
                "location" => "supply",
                "scope" => "global",
                "counter" => "public",
                "content" => "hidden",
            ],
            "deck_insp" => [
                "type" => "location",
                "showtooltip" => 0,
                "create" => 0,
                "name" => clienttranslate("Inspiration Cards"),
                "location" => "supply",
                "scope" => "global",
                "counter" => "public",
                "content" => "hidden",
            ],
            "limbo" => [
                "type" => "location",
                "showtooltip" => 0,
                "create" => 0,
                "name" => clienttranslate("Limbo"),
                "scope" => "global",
                "counter" => "hidden",
                "content" => "hidden",
            ],
            "tableau" => [
                "type" => "location",
                "showtooltip" => 0,
                "create" => 0,
                "name" => clienttranslate("Player Area"),
                "location" => "players_panels",
                "scope" => "player",
                "counter" => "hidden",
                "content" => "public",
            ],
            "hand" => [
                "type" => "location",
                "showtooltip" => 0,
                "create" => 0,
                "name" => clienttranslate("Player Hand"),
                "location" => "players_panels",
                "scope" => "player",
                "counter" => "hidden",
                "content" => "private",
            ],
            "supply" => [
                "type" => "location",
                "showtooltip" => 0,
                "create" => 0,
                "name" => clienttranslate("Supply"),
                "location" => "supply",
                "scope" => "global",
                "counter" => "public",
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
    "Op_cardBlack" => [ 
        "type" => "cardBlack",
        "name" => clienttranslate("Gain Space Card"),
],
    "Op_cardGreen" => [ 
        "type" => "cardGreen",
        "name" => clienttranslate("Gain Townfolk Card"),
],
// #cardBlue|Gain Water Card
// #cardYellow|Gain Land Card
    "Op_gainCard" => [ 
        "type" => "gainCard",
        "name" => clienttranslate("Gain Card"),
],
    "Op_upgBlack" => [ 
        "type" => "upgBlack",
        "name" => clienttranslate("Gain Black Upgrade"),
],
    "Op_upgGreen" => [ 
        "type" => "upgGreen",
        "name" => clienttranslate("Gain Green Upgrade"),
],
    "Op_journal" => [ 
        "type" => "journal",
        "name" => clienttranslate("Journal"),
],
    "Op_infAny" => [ 
        "type" => "infAny",
        "name" => clienttranslate("Place Influence in any Guide"),
],
    "Op_pickWorker" => [ 
        "type" => "pickWorker",
        "name" => clienttranslate("Pick a Worker"),
],
    "Op_food" => [ 
        "class" => "Op_gain",
        "type" => "food",
        "name" => clienttranslate("Gain Provisions"),
],
    "Op_coin" => [ 
        "class" => "Op_gain",
        "type" => "coin",
        "name" => clienttranslate("Gain Silver"),
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
        "name" => clienttranslate("Worker"),
        "count" => 4,
        "type" => "worker wooden",
        "create" => 2,
        "location" => "limbo",
],
    "worker_yellow" => [ 
        "name" => clienttranslate("Worker"),
        "count" => 4,
        "type" => "worker wooden",
        "create" => 2,
        "location" => "limbo",
],
    "worker_green" => [ 
        "name" => clienttranslate("Worker"),
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
        "name" => clienttranslate("Player marker"),
        "count" => 15,
        "type" => "influence wooden",
        "create" => 3,
        "location" => "tableau_{COLOR}",
],
    "dice" => [ 
        "name" => clienttranslate("Player dice"),
        "count" => 5,
        "type" => "dice wooden",
        "create" => 3,
        "location" => "tableau_{COLOR}",
],
// #journal tiles
    "jtile" => [ 
        "name" => clienttranslate("Journal Tile"),
        "count" => 10,
        "type" => "jtile tile",
        "create" => 2,
        "location" => "deck_jtile",
],
    "utile" => [ 
        "name" => clienttranslate("Upgrade Tile"),
        "count" => 10,
        "type" => "utile tile",
        "create" => 2,
        "location" => "supply",
],
// #cards
// #36 Townsfolk Cards 36 Space Cards 36 Land Cards 16 Inspiration Cards 36 Water Cards 6 Scheme Cards
// #create=0
// #card_folk|Townsfolk|36|card folk|2|deck_folk
// #create=1
    "card_space" => [ 
        "name" => clienttranslate("Space"),
        "count" => 36,
        "type" => "card space",
        "create" => 2,
        "location" => "deck_space",
],
    "card_land" => [ 
        "name" => clienttranslate("Land"),
        "count" => 36,
        "type" => "card land",
        "create" => 2,
        "location" => "deck_land",
],
    "card_water" => [ 
        "name" => clienttranslate("Water"),
        "count" => 36,
        "type" => "card water",
        "create" => 2,
        "location" => "deck_water",
],
    "card_insp" => [ 
        "name" => clienttranslate("Inspiration"),
        "count" => 16,
        "type" => "card insp",
        "create" => 2,
        "location" => "deck_insp",
],
// #boards
    "mainboard_1" => [ 
        "count" => 1,
        "type" => "mainboard_x left",
        "create" => 1,
        "location" => "mainboardall",
],
    "mainboard_2" => [ 
        "count" => 1,
        "type" => "mainboard_x middle",
        "create" => 1,
        "location" => "mainboardall",
],
    "mainboard_3" => [ 
        "count" => 1,
        "type" => "mainboard_x right",
        "create" => 1,
        "location" => "mainboardall",
],
            /* --- gen php end token_material --- */

            /* --- gen php begin card_material --- */
    "dslot_0_card_home_1" => [ 
        "create" => 4,
        "type" => "dslot",
        "t" => "home",
        "location" => "card_home_1_{COLOR}",
        "num" => 1,
        "r" => 0,
        "d" => "telescope",
        "dr" => "(cardBlack/upgBlack)",
        "name" => clienttranslate("Black Slot"),
],
    "dslot_1_card_home_1" => [ 
        "create" => 4,
        "type" => "dslot",
        "t" => "home",
        "location" => "card_home_1_{COLOR}",
        "num" => 1,
        "r" => 1,
        "d" => "bird",
        "dr" => "(journal/(pickWorker,infAny))",
        "name" => clienttranslate("Journal Slot"),
],
    "dslot_2_card_home_1" => [ 
        "create" => 4,
        "type" => "dslot",
        "t" => "home",
        "location" => "card_home_1_{COLOR}",
        "num" => 1,
        "r" => 2,
        "d" => "any",
        "dr" => "(cardGreen/2food)",
        "name" => clienttranslate("Food Slot"),
],
    "dslot_3_card_home_1" => [ 
        "create" => 4,
        "type" => "dslot",
        "t" => "home",
        "location" => "card_home_1_{COLOR}",
        "num" => 1,
        "r" => 3,
        "d" => "telescope",
        "dr" => "(upgGreen/2coin)",
        "name" => clienttranslate("Coin Slot"),
],
    "card_home_1" => [ 
        "create" => 4,
        "type" => "card card_home",
        "t" => "home",
        "location" => "tableau_{COLOR}",
        "num" => 1,
        "tags" => "Book Observatory",
],
    "card_home_2" => [ 
        "create" => 4,
        "type" => "card card_home",
        "t" => "home",
        "location" => "tableau_{COLOR}",
        "num" => 2,
        "d" => "camel",
        "dr" => "(2food:cardYellow,coin)",
        "tags" => "City",
],
    "card_home_3" => [ 
        "create" => 4,
        "type" => "card card_home",
        "t" => "home",
        "location" => "tableau_{COLOR}",
        "num" => 3,
        "d" => "ship",
        "dr" => "(2food:cardBlue)",
        "tags" => "Harbour",
],
    "card_folk_1" => [ 
        "create" => 4,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "tableau_{COLOR}",
        "num" => 1,
        "dr" => "journal,coin",
        "name" => clienttranslate("Townfolk"),
],
    "card_folk_114" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 114,
        "r" => 1,
        "d" => "*",
        "dr" => "(coin/food):(pickWorker,reroll)",
        "tags" => "Vista",
        "name" => clienttranslate("Protector"),
],
    "card_folk_115" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 115,
        "r" => 2,
        "d" => "*",
        "dr" => "food:2coin",
        "tags" => "Observatory",
        "name" => clienttranslate("Wanderer"),
],
    "card_folk_116" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 116,
        "r" => 1,
        "dr" => "ship",
        "tags" => "Harbour",
        "name" => clienttranslate("Adventurer"),
],
    "card_folk_117" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 117,
        "r" => 0,
        "dr" => "telescope",
        "tags" => "Observatory",
        "name" => clienttranslate("Astronomer"),
],
    "card_folk_118" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 118,
        "r" => 2,
        "dr" => "pickWorker/infCard",
        "tags" => "Vista",
        "name" => clienttranslate("Champion"),
],
    "card_folk_119" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 119,
        "r" => 1,
        "dr" => "infMove,food",
        "tags" => "Water",
        "name" => clienttranslate("Enforcer"),
],
    "card_folk_120" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 120,
        "r" => 1,
        "dr" => "infMove,food",
        "tags" => "Water",
        "name" => clienttranslate("Enforcer"),
],
    "card_folk_121" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 121,
        "r" => 2,
        "dr" => "camel,bird",
        "tags" => "City",
        "name" => clienttranslate("Envoy"),
],
    "card_folk_122" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 122,
        "r" => 2,
        "dr" => "ship,bird",
        "tags" => "City",
        "name" => clienttranslate("Envoy"),
],
    "card_folk_123" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 123,
        "r" => 2,
        "dr" => "coin",
        "tags" => "City",
        "name" => clienttranslate("Farmer"),
],
    "card_folk_124" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 124,
        "r" => 2,
        "dr" => "food",
        "tags" => "City",
        "name" => clienttranslate("Farmer"),
],
    "card_folk_125" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 125,
        "r" => 2,
        "dr" => "coin",
        "tags" => "Harbour",
        "name" => clienttranslate("Fisherman"),
],
    "card_folk_126" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 126,
        "r" => 2,
        "dr" => "food",
        "tags" => "Harbour",
        "name" => clienttranslate("Fisherman"),
],
    "card_folk_127" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 127,
        "r" => 3,
        "dr" => "food,coin",
        "tags" => "Water",
        "name" => clienttranslate("Guardian"),
],
    "card_folk_128" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 128,
        "r" => 3,
        "dr" => "2coin",
        "tags" => "Water",
        "name" => clienttranslate("Guardian"),
],
    "card_folk_129" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 129,
        "r" => 3,
        "dr" => "2food",
        "tags" => "Water",
        "name" => clienttranslate("Guardian"),
],
    "card_folk_130" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 130,
        "r" => 0,
        "dr" => "2diceMod",
        "tags" => "Vista",
        "name" => clienttranslate("Hunter"),
],
    "card_folk_131" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 131,
        "r" => 2,
        "dr" => "2ship",
        "tags" => "Harbour",
        "name" => clienttranslate("Invader"),
],
    "card_folk_132" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 132,
        "r" => 2,
        "dr" => "pickWorker/infCard",
        "tags" => "City Harbour",
        "name" => clienttranslate("Mercenary"),
],
    "card_folk_133" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 133,
        "r" => 2,
        "dr" => "coin",
        "tags" => "Vista",
        "name" => clienttranslate("Merchant"),
],
    "card_folk_134" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 134,
        "r" => 2,
        "dr" => "coin",
        "tags" => "Vista",
        "name" => clienttranslate("Merchant"),
],
    "card_folk_135" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 135,
        "r" => 2,
        "dr" => "food",
        "tags" => "Vista",
        "name" => clienttranslate("Merchant"),
],
    "card_folk_136" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 136,
        "r" => 2,
        "dr" => "food",
        "tags" => "Vista",
        "name" => clienttranslate("Merchant"),
],
    "card_folk_137" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 137,
        "r" => 1,
        "dr" => "bird",
        "tags" => "City Harbour",
        "name" => clienttranslate("Messenger"),
],
    "card_folk_138" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 138,
        "r" => 1,
        "dr" => "infCard/infMove/diceMod",
        "tags" => "City Harbour",
        "name" => clienttranslate("Rogue"),
],
    "card_folk_139" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 139,
        "r" => 1,
        "dr" => "infBlue/infMove",
        "tags" => "City Harbour",
        "name" => clienttranslate("Scholar"),
],
    "card_folk_140" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 140,
        "r" => 1,
        "dr" => "infYellow/infMove",
        "tags" => "City Harbour",
        "name" => clienttranslate("Scholar"),
],
    "card_folk_141" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 141,
        "r" => 2,
        "dr" => "infBlack/infMove",
        "tags" => "Book Observatory",
        "name" => clienttranslate("Scholar"),
],
    "card_folk_142" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 142,
        "r" => 1,
        "dr" => "infBlue/infMove",
        "tags" => "Book Observatory",
        "name" => clienttranslate("Scribe"),
],
    "card_folk_143" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 143,
        "r" => 1,
        "dr" => "infYellow/infMove",
        "tags" => "Book Observatory",
        "name" => clienttranslate("Scribe"),
],
    "card_folk_144" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 144,
        "r" => 2,
        "dr" => "infBlack/infMove",
        "tags" => "Vista",
        "name" => clienttranslate("Scribe"),
],
    "card_folk_145" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 145,
        "r" => 2,
        "dr" => "telescope,camel",
        "tags" => "City",
        "name" => clienttranslate("Stargazer"),
],
    "card_folk_146" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 146,
        "r" => 1,
        "dr" => "infBlue/infMove",
        "tags" => "Water",
        "name" => clienttranslate("Translator"),
],
    "card_folk_147" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 147,
        "r" => 1,
        "dr" => "infYellow/infMove",
        "tags" => "Water",
        "name" => clienttranslate("Translator"),
],
    "card_folk_148" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 148,
        "r" => 2,
        "dr" => "infBlack/infMove",
        "tags" => "Water",
        "name" => clienttranslate("Translator"),
],
    "card_folk_149" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 149,
        "r" => 1,
        "dr" => "camel",
        "tags" => "City",
        "name" => clienttranslate("Vagrant"),
],
    "card_folk_150" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 150,
        "r" => 1,
        "dr" => "infCard/infMove/diceMod",
        "tags" => "Vista",
        "name" => clienttranslate("Vigilante"),
],
    "card_folk_151" => [ 
        "create" => 1,
        "type" => "card card_folk folk",
        "t" => "folk",
        "location" => "deck_folk",
        "num" => 151,
        "r" => 0,
        "dr" => "pickWorker/infMove",
        "tags" => "Water",
        "name" => clienttranslate("Warrior"),
],
            /* --- gen php end card_material --- */
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
                return $data[$field] ?? $default;
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
}
