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
        "location" => "supply",
],
    "worker_yellow" => [ 
        "name" => clienttranslate("Worker"),
        "count" => 4,
        "type" => "worker wooden",
        "create" => 2,
        "location" => "supply",
],
    "worker_green" => [ 
        "name" => clienttranslate("Worker"),
        "count" => 4,
        "type" => "worker wooden",
        "create" => 2,
        "location" => "supply",
],
// #player counters
    "tracker_silver" => [ 
        "name" => clienttranslate("Silver"),
        "count" => 1,
        "type" => "tracker silver",
        "create" => 4,
        "location" => "miniboard_{COLOR}",
],
    "tracker_provision" => [ 
        "name" => clienttranslate("Provision"),
        "count" => 1,
        "type" => "tracker provision",
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
    "card_folk" => [ 
        "name" => clienttranslate("Townsfolk"),
        "count" => 36,
        "type" => "card folk",
        "create" => 2,
        "location" => "deck_folk",
],
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
        "type" => "card inspiration",
        "create" => 2,
        "location" => "deck_insp",
],
// #boards
    "mainboard_1" => [ 
        "count" => 1,
        "type" => "mainboard left",
        "create" => 1,
        "location" => "mainboard",
],
    "mainboard_2" => [ 
        "count" => 1,
        "type" => "mainboard middle",
        "create" => 1,
        "location" => "mainboard",
],
    "mainboard_3" => [ 
        "count" => 1,
        "type" => "mainboard right",
        "create" => 1,
        "location" => "mainboard",
],
            /* --- gen php end token_material --- */

            /* --- gen php begin setl_material --- */
            "card_setl_1_1" => [
                "create" => 4,
                "type" => "card setl",
                "location" => "deck_village",
                "num" => 1,
                "name" => clienttranslate("Fisherman"),
                "r" => "fish",
                "t" => 1,
                "tooltip" => clienttranslate("Gain fish"),
            ],
            /* --- gen php end setl_material --- */
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
