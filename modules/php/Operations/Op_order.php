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

use Bga\Games\wayfarers\OpCommon\ComplexOperation;

class Op_order extends ComplexOperation {
    public function auto(): bool {
        $this->game->systemAssert("Op_order does not support counts", $this->getCount() == 1 && $this->getMinCount() == 1);
        // Auto-queue trivial delegates (order doesn't matter for them)
        $modified = false;
        foreach ($this->delegates as $i => $sub) {
            if ($sub->isTrivial()) {
                $this->queueOp($sub);
                unset($this->delegates[$i]);
                $modified = true;
            }
        }

        // If 0 or 1 non-trivial delegates remain, queue and auto-resolve
        if (count($this->delegates) <= 1) {
            foreach ($this->delegates as $sub) {
                $this->queueOp($sub);
            }
            return true;
        }

        if ($modified) {
            $this->queueOp($this);
            return true;
        }

        return false;
    }

    function resolve(): void {
        // this suppose to pick selected operation and push on top of stack, remaing choice if any stored back
        $target = $this->getCheckedArg();
        foreach ($this->delegates as $i => $arg) {
            if ("choice_$i" == $target) {
                $this->queueOp($arg);
                $arg->destroy();
                unset($this->delegates[$i]);
                break;
            }
        }
        if (count($this->delegates) > 0) {
            $this->queueOp($this);
        }

        return;
    }

    function getPossibleMoves() {
        $res = [];
        foreach ($this->delegates as $i => $sub) {
            $res["choice_$i"] = $this->paramInfo($sub);
        }
        return $res;
    }

    function getPrompt() {
        return clienttranslate("Choose order of operations");
    }
    function getDescription() {
        return clienttranslate('${actplayer} chooses order');
    }
    function getOperator() {
        return "+";
    }

    function getIconicName() {
        $names = [];
        foreach ($this->delegates as $sub) {
            $names[] = $sub->getIconicName();
        }
        return implode(" ", $names);
    }

    function getOpName() {
        return $this->getRecName(" + ");
    }
}
