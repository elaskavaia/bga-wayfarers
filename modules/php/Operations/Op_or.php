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
use Bga\Games\wayfarers\OpCommon\ComplexOperation;
use Bga\Games\wayfarers\OpCommon\CountableOperation;
use Bga\Games\wayfarers\OpCommon\Operation;

/** User choses operation. If count is used it is shared and decreases for all choices */
class Op_or extends ComplexOperation {
    function resolve(): void {
        $res = $this->getCheckedArg();
        if (!is_array($res)) {
            $res = [$res => 1];
        }
        $total = 0;
        $count = $this->getCount();
        $minCount = $this->getMinCount();
        $rank = 1;
        foreach ($this->delegates as $i => $sub) {
            $key = "choice_$i";
            $c = $res[$key] ?? 0; // user selects the count of sub operation
            $total += $c;
            if ($c > 0) {
                $max = $sub->getDataField("count", 1);
                $min = $sub->getDataField("mcount", 1);
                // now override count
                $sub->withDataField("count", $max * $c);
                $sub->withDataField("mcount", $min * $c);
                // save
                $this->queueOp($sub);

                // Reset delegate counts so serialization stays clean if saved again
                $sub->withDataField("count", $max);
                $sub->withDataField("mcount", $min);

                //$this->notifyMessage(clienttranslate('${player_name} selected ${opname}'), ["opname" => $arg->getOpName()]);
                $this->incMinCount(-$c);
                $this->incCount(-$c);
            }
            $sub->destroy(); // this destroys this in db, but it will saved again when parent saves its state
        }

        if ($total > $count) {
            $this->game->userAssert(clienttranslate("Cannot use this action because superfluous amount of elements selected"));
        }

        if ($this->getCount() > 0) {
            $this->queueOp($this);
        }
        return;
    }

    function getPossibleMoves() {
        $res = [];
        $totalLimit = 0;
        foreach ($this->delegates as $i => $sub) {
            $arg = $this->paramInfo($sub);
            // a skippable option is never void, but offering it with nothing to take is a trap
            if ($arg["q"] == 0 && $sub->noValidTargets()) {
                $arg["q"] = Material::ERR_NOT_APPLICABLE;
                $arg["max"] = 0;
                $arg["err"] = $sub->getError();
            }
            $totalLimit += $arg["max"] ?? 0;
            $res["choice_$i"] = $arg;
        }
        if ($totalLimit == 0) {
            return $res;
        }
        if ($totalLimit < $this->getMinCount()) {
            return ["q" => Material::ERR_COST];
        }
        return $res;
    }

    function getArgType() {
        if ($this->getCount() > 1) {
            return Operation::TTYPE_TOKEN_COUNT;
        }
        return Operation::TTYPE_TOKEN;
    }

    function getPrompt() {
        if ($this->getCount() > 1) {
            return clienttranslate('Choose one of the options (count: ${count})');
        }
        return clienttranslate("Choose one of the options");
    }

    function getDescription() {
        return clienttranslate('${actplayer} chooses one of the options');
    }
    function getIconicName() {
        $names = [];
        foreach ($this->delegates as $sub) {
            $names[] = $sub->getIconicName();
        }
        return implode(" / ", $names);
    }

    function getOpName() {
        return $this->getRecName(" / ");
    }

    function isTrivial(): bool {
        $nonVoid = [];
        foreach ($this->delegates as $sub) {
            if (!$sub->noValidTargets()) {
                $nonVoid[] = $sub;
            }
        }
        return count($nonVoid) <= 1 && (!$nonVoid || $nonVoid[0]->isTrivial());
    }

    /** Picking a skippable option and skipping it is a decline, so offer it in one step; an unpayable payment choice stays void */
    public function canSkip() {
        if (parent::canSkip()) {
            return true;
        }
        foreach ($this->delegates as $sub) {
            if ($sub->canSkip()) {
                return true;
            }
        }
        return false;
    }

    /** Show why no option can be taken instead of skipping silently */
    public function requireConfirmation() {
        return $this->noValidTargets();
    }

    public function skip() {
        parent::skip();
        if ($this->noValidTargets()) {
            $this->notifyMessage(clienttranslate('${player_name} skips ${op_name}'), [
                "op_name" => $this->getOpName()
            ]);
        }
    }

    function getOperator() {
        return "/";
    }
}
