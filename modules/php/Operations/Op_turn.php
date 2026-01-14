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
        $all = $this->game->tokens->getTokensOfTypeInLocation("dslot");
        $cards = $this->game->tokens->getTokensOfTypeInLocation("card", "tableau_$owner");
        $player_dslots = array_filter($all, fn($rec) => array_key_exists($rec["location"], $cards));
        foreach ($cards as $card => $info) {
            if ($this->game->getRulesFor($card, "d")) {
                $dslot = "dslot_0_$card";
                if (array_key_exists($dslot, $player_dslots)) {
                    continue;
                }
                $player_dslots[$dslot] = [
                    "key" => $dslot,
                    "location" => $card,
                    "state" => 0,
                ];
            }
        }
        return $player_dslots;
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
            $player_dslots = $this->getDiceSlots();

            foreach ($player_dslots as $key => $slot) {
                $res[$key] = ["q" => 0];
            }
        }
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
            $this->queue($this->getType(), $owner, ["loc" => $this->getCheckedArg()]);
            return;
        }
        $tool = $this->getCheckedArg();
        $state = $this->game->tokens->db->getTokenState($tool);
        $this->game->tokens->dbSetTokenLocation($tool, $loc, 0, clienttranslate('${player_name} places die ${num} onto ${token_name}'), [
            "num" => $state,
        ]);
        $r = $this->game->getRulesFor($loc, "dr");
        // XXX
        if (!$r) {
            $i = strpos($loc, "card");
            $pcard = substr($loc, $i);
            $r = $this->game->getRulesFor($pcard, "dr");
            $this->game->systemAssert("parent rule empty '$pcard' '$loc'", $r);
        }
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
