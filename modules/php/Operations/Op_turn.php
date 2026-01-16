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

namespace Bga\Games\wayfarers\Operations;

use Bga\Games\wayfarers\Material;
use Bga\Games\wayfarers\OpCommon\Operation;

class Op_turn extends Operation {
    public function auto(): bool {
        if ($this->getLocation() === null) {
            $this->game->customUndoSavepoint($this->getPlayerId(), 1);
        }
        return false;
    }
    function getDiceSlots() {
        $owner = $this->getOwner();

        $cards = $this->game->tokens->getTokensOfTypeInLocationWithChildren("card", "tableau_$owner");
        $slots = [];
        foreach ($cards as $card => $info) {
            if ($this->game->getRulesFor($card, "d")) {
                $slots[$card] = [
                    "key" => $card,
                    "location" => $card,
                    "state" => count($info["children"] ?? []),
                ];
            }
        }
        return $slots;
    }

    function getDice() {
        $owner = $this->getOwner();
        $cards = $this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_$owner");
        return $cards;
    }

    public function getPossibleMoves() {
        $loc = $this->getLocation();
        $res = [];
        if ($loc) {
            $player_dice = $this->getDice();

            foreach ($player_dice as $key => $slot) {
                $res[$key] = ["q" => 0];
            }
        } else {
            $slots = $this->getDiceSlots();

            foreach ($slots as $key => $slot) {
                $state = $slot["state"];
                $res[$key] = ["q" => $state == 0 ? Material::RET_OK : Material::ERR_OCCUPIED];
            }
        }
        $res["rest"] = ["q" => 0, "name" => clienttranslate("Rest")];
        return $res;
    }
    public function getUiArgs() {
        return ["buttons" => false];
    }

    public function getLocation() {
        return $this->getDataField("loc", null);
    }
    function resolve(): void {
        $loc = $this->getLocation();
        $owner = $this->getOwner();
        if ($loc == null) {
            $selected = $this->getCheckedArg();
            if ($selected === "rest") {
                $this->queue("rest");
                $this->queue("turn"); // XXX pick next player
                return;
            }
            $this->queue($this->getType(), $owner, ["loc" => $selected]);
            return;
        }
        $tool = $this->getCheckedArg();
        $state = $this->game->tokens->db->getTokenState($tool);
        $this->game->tokens->dbSetTokenLocation($tool, $loc, 0, clienttranslate('${player_name} places die ${num} onto ${token_name}'), [
            "num" => $state,
        ]);
        $r = $this->game->getRulesFor($loc, "dr");

        $this->game->systemAssert("parent rule empty '$loc'", $r);
        $this->queue($r);
        $this->queue("turn"); // XXX pick next player
    }

    public function getPrompt() {
        $loc = $this->getLocation();
        $owner = $this->getOwner();
        if ($loc == null) {
            return clienttranslate("Select an action");
        }
        return clienttranslate("Select a die");
    }
}
