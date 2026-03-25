<?php

/**
 * Compare operation names (from getOpName) with tooltip text in Material.
 * Outputs tab-separated: id, rule field, op name, tooltip, match
 * Usage: APP_GAMEMODULE_PATH=~/git/bga-sharedcode/misc/ php8.4 misc/other/check_tooltips.php >  tooltip_check.csv
 */

error_reporting(E_ALL & ~E_WARNING);

if (!defined("APP_GAMEMODULE_PATH")) {
    define("APP_GAMEMODULE_PATH", getenv("APP_GAMEMODULE_PATH"));
}
require_once APP_GAMEMODULE_PATH . "/php/stubs/BgaFrameworkStubs.php";
require_once __DIR__ . "/../../phptests/_autoload.php";

use Tests\GameUT;

$game = new GameUT();
$game->init();

function flattenOpName(string $rule, $game): string {
    $op = $game->machine->instanciateOperation($rule, PCOLOR);
    $opName = $op->getOpName();
    if (is_array($opName)) {
        return GameUT::format_string_recursive($opName["log"], $opName["args"]);
    }
    return (string) $opName;
}

$material = $game->material;
$allTypes = $material->get();

$checks = [
    "card_folk_" => [["dr", "tooltip"]],
    "card_water_" => [["r", "tor"], ["dr", "todr"]],
    "card_land_" => [["r", "tor"], ["dr", "todr"]],
    "card_space_" => [["r", "tor"]],
    "card_home_" => [["dr", "todr"]],
    "jtile_" => [["r", "tooltip"]],
];

echo "id|field|rule|op_name|tooltip|match\n";

foreach ($checks as $prefix => $fieldPairs) {
    foreach ($allTypes as $key => $rules) {
        if (!str_starts_with($key, $prefix)) {
            continue;
        }
        foreach ($fieldPairs as [$ruleField, $tooltipField]) {
            $rule = $material->getRulesFor($key, $ruleField, "");
            $tooltip = $material->getRulesFor($key, $tooltipField, "");
            if ($rule === "" || $tooltip === "") {
                continue;
            }

            $opName = "";
            if ($rule !== "") {
                try {
                    $opName = flattenOpName($rule, $game);
                } catch (\Throwable $e) {
                    $opName = "ERROR: " . $e->getMessage();
                }
            }

            $normOp = strtolower(trim(preg_replace("/\s+/", " ", $opName)));
            $normTt = strtolower(trim(preg_replace("/\s+/", " ", $tooltip)));
            $match = $normOp === $normTt ? "OK" : "DIFF";

            echo "$key|$ruleField|$rule|$opName|$tooltip|$match\n";
        }
    }
}
