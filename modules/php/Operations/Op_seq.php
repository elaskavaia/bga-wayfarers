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

/** Sequence of operations, no user choice */
class Op_seq extends ComplexOperation {
    function expandOperation() {
        if (count($this->delegates) == 0) {
            return true;
        }

        if ($this->isRangedChoice()) {
            return false;
        }
        if (!$this->isSubTrancient()) {
            return false;
        }
        $rank = 1;
        $this->game->machine->interrupt($rank, count($this->delegates));
        $c = $this->getCount();
        foreach ($this->delegates as $sub) {
            $sub->destroy();
            $max = $sub->getDataField("count", 1);
            $min = $sub->getDataField("mcount", 1);
            $sub->withData($this->getData());
            $sub->withDataField("count", $max * $c);
            $sub->withDataField("mcount", $min * $c);
            $sub->saveToDb($rank, false);
            $rank++;
        }

        return true;
    }

    function getPossibleMoves() {
        foreach ($this->delegates as $sub) {
            if ($sub->isVoid()) {
                return ["err" => $sub->getError()];
            }
        }
        if ($this->isRangedChoice()) {
            return parent::getRangeMoves();
        }
        if (count($this->delegates) == 0) {
            return [];
        }
        $sub = $this->delegates[0];
        return $sub->getPossibleMoves();
    }

    function getPrompt() {
        if ($this->isRangedChoice()) {
            $max = $this->getCount();
            if ($max > 1) {
                return clienttranslate('Select how many times to perform ${name}');
            }
            return clienttranslate('Perform ${name}');
        }
        return parent::getPrompt();
    }

    function getIconicName() {
        $names = [];
        foreach ($this->delegates as $sub) {
            $names[] = $sub->getIconicName();
        }
        return implode(" ", $names);
    }

    function getOpName() {
        return $this->getRecName(" ");
    }


    public function resolve(): void {
        if ($this->isRangedChoice()) {
            $c = $this->getCheckedArg();
            $this->withDataField("count", $c);
            $this->withDataField("mcount", $c);

            $this->saveToDb();
            return;
        }
    }

    function isTrivial(): bool {
        foreach ($this->delegates as $sub) {
            if (!$sub->isTrivial()) {
                return false;
            }
        }
        return true;
    }

    function getOperator() {
        return ",";
    }
}
