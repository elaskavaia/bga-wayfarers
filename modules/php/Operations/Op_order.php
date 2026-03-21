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
        // Auto-queue trivial delegates (order doesn't matter for them)
        foreach ($this->delegates as $i => $sub) {
            if ($sub->isTrivial()) {
                $this->queue($sub->getTypeFullExpr(), $sub->getOwner(), $sub->getDataForDb());
                unset($this->delegates[$i]);
            }
        }
        $this->delegates = array_values($this->delegates);

        // If 0 or 1 non-trivial delegates remain, queue and auto-resolve
        if (count($this->delegates) <= 1) {
            foreach ($this->delegates as $sub) {
                $this->queue($sub->getTypeFullExpr(), $sub->getOwner(), $sub->getDataForDb());
            }
            return true;
        }

        // 2+ non-trivial delegates: save updated operation to DB and prompt player
        $this->saveToDb($this->queueRank, true);
        return false;
    }

    function resolve(): void {
        // this suppose to pick selected operation and push on top of stack, remaing choice if any stored back
        $target = $this->getCheckedArg();
        foreach ($this->delegates as $i => $arg) {
            if ("choice_$i" == $target) {
                $this->queue($arg->getTypeFullExpr(), $arg->getOwner(), $arg->getDataForDb());
                $arg->destroy();
                unset($this->delegates[$i]);
                break;
            }
        }
        if (count($this->delegates) > 0) {
            $this->queueRank++;
            $this->saveToDb($this->queueRank, true);
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

    function getOpName() {
        return $this->getRecName(" + ");
    }
}
