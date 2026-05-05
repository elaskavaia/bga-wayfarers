<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * wayfarers implementation : © Alena Laskavaia <laskava@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

declare(strict_types=1);

namespace Bga\Games\wayfarers\OpCommon;

use Bga\GameFramework\SystemException;
use Bga\Games\wayfarers\OpCommon\OpExpression;
use Bga\Games\wayfarers\OpCommon\OpExpressionRanged;
use Bga\Games\wayfarers\OpCommon\Operation;
use Bga\Games\wayfarers\Game;
use Bga\Games\wayfarers\StateConstants;
use Bga\Games\wayfarers\Db\DbMachine;
use Bga\Games\wayfarers\OpCommon\ComplexOperation;
use Bga\Games\wayfarers\OpCommon\CountableOperation;
use Bga\Games\wayfarers\States\GameDispatchForced;
use Bga\Games\wayfarers\States\GameDispatch;
use Bga\Games\wayfarers\States\PlayerTurnConfirm;

use Exception;
use ReflectionClass;
use Throwable;

class OpMachine {
    const MA_GAME_DISPATCH_MAX = 1000;
    const GAME_MULTI_COLOR = "000000";
    const GAME_BARIER_COLOR = "";
    protected Game $game;

    public function __construct(public DbMachine $db = new DbMachine()) {
        $this->game = Game::$instance;
    }

    function createTopOperationFromDb($player_id): ?Operation {
        if ($player_id === 0) {
            $owner = null;
        } else {
            $owner = $this->game->custom_getPlayerColorById($player_id);
        }
        return $this->createTopOperationFromDbForOwner($owner);
    }

    function createTopOperationFromDbForOwner(?string $owner): ?Operation {
        $ops = $this->db->getTopOperations($owner);
        if (count($ops) == 0) {
            return null;
        }
        $dop = reset($ops);

        return $this->instanciateOperationFromDbRow($dop);
    }

    function instanciateOperationFromDbRow(mixed $dop): Operation {
        if (is_string($dop["data"])) {
            $data = Operation::decodeData($dop["data"]);
        } else {
            $data = $dop["data"];
        }
        $args = $data["args"] ?? [];
        if ($args) {
            unset($data["args"]);
        }
        $op = $this->instanciateOperation($dop["type"], $dop["owner"], $data, $dop["id"] ?? 0);
        if ($op instanceof ComplexOperation) {
            foreach ($args as $sub) {
                $subOp = $this->instanciateOperationFromDbRow(["owner" => $dop["owner"]] + $sub);
                $op->withDelegate($subOp);
            }
        }
        return $op;
    }

    function instanciateOperation(string $type, ?string $owner = null, mixed $data = null, mixed $id = 0): Operation {
        try {
            if ($id) {
                $id = (int) $id;
            } else {
                $id = 0;
            }

            $expr = OpExpression::parseExpression($type);
            $op = $this->exprToOperation($expr, $owner)->withId($id)->withData($data);
            return $op;
        } catch (Exception $e) {
            throw new SystemException("Cannot instantiate '$type': " . $e->getMessage());
        }
    }
    function exprToOperation(OpExpression $expr, ?string $owner) {
        $operand = OpExpression::getop($expr);
        //[op min max arg1 arg2 arg3]...

        if (!$expr->isSimple()) {
            $mnemonic = self::opToMnemonic($operand);
            /** @var ComplexOperation */
            $op = $this->instanciateCommonOperation($mnemonic, $owner);
            foreach ($expr->args as $arg) {
                $sub = $this->exprToOperation($arg, $owner);
                $op->withDelegate($sub);
            }
            $op->withCounts($expr);
            return $op;
        }

        $unrangedType = OpExpression::str($expr->toUnranged());
        $matches = null;
        $params = null;
        if (preg_match("/^(\w+)\((.*)\)$/", $unrangedType, $matches)) {
            // function call
            $params = $matches[2];
            $unrangedType = $matches[1];
        }
        $sub = $this->instanciateSimpleOperation($unrangedType, $owner)->withParams($params);
        if ($expr instanceof OpExpressionRanged) {
            if ($sub instanceof CountableOperation) {
                $sub->withCounts($expr);
                return $sub;
            } else {
                /** @var ComplexOperation */
                $op = $this->instanciateCommonOperation("seq", $owner);
                $op->withDelegate($sub)->withCounts($expr);
                return $op;
            }
        } else {
            return $sub;
        }
    }
    static function opToMnemonic(string $operand) {
        return match ($operand) {
            "!" => "atomic",
            "+" => "order",
            "," => "seq",
            ":" => "paygain",
            ";" => "seq",
            "^" => "unique",
            "/" => "or",
            default => throw new SystemException("Unknown operator $operand"),
        };
    }
    function instanciateCommonOperation(string $type, ?string $owner = null, mixed $data = null): Operation {
        $reflectionClass = new ReflectionClass("Bga\\Games\\wayfarers\\Operations\\Op_$type");
        $instance = $reflectionClass->newInstance($type, $owner, $data);
        return $instance;
    }

    function instanciateSimpleOperation(string $type, ?string $owner = null, mixed $data = null): Operation {
        if (strlen($type) > 80) {
            throw new SystemException("Cannot instantiate op");
        }

        $operandclass = $this->game->getRulesFor("Op_$type", "class", "Op_$type");

        // Instantiate the class with constructor arguments
        try {
            $reflectionClass = new ReflectionClass("Bga\\Games\\wayfarers\\Operations\\$operandclass");
            $instance = $reflectionClass->newInstance($type, $owner, $data);
        } catch (Throwable $e) {
            throw new SystemException("Cannot instantiate $type: " . $e->getMessage());
        }

        return $instance;
    }

    function getTopOperations($owner) {
        $ops = $this->db->getTopOperations($owner);
        return $ops;
    }

    function hide(int $id) {
        $this->db->hide($id);
    }

    function interrupt(int $rank = 0, int $count = 1) {
        $this->db->interrupt($rank, $count);
    }

    function push(string $type, ?string $owner = null, mixed $data = null) {
        $this->interrupt();
        return $this->put($type, $owner, $data, 1);
    }

    function queue(string $type, ?string $owner = null, mixed $data = null) {
        $rank = $this->db->getExtremeRank(true);
        $rank++;
        return $this->put($type, $owner, $data, $rank);
    }

    function put(string $type, ?string $owner = null, mixed $data = null, int $rank = 1) {
        if ($owner === null) {
            $owner = "";
        }
        $op = $this->db->createRow($type, $owner, $data);
        return $this->db->insertRow($rank, $op);
    }

    function insertRow(mixed $row, int $rank = 1) {
        return $this->db->insertRow($rank, $row);
    }

    function insert(string $type, ?string $owner = null, mixed $data = null, ?int &$rank = null) {
        if ($rank === null) {
            $rank = 1;
        }
        $this->interrupt($rank);
        $this->put($type, $owner, $data, $rank);
        $rank++;
    }

    //DISPATCH

    function dispatchAll(int $n = OpMachine::MA_GAME_DISPATCH_MAX) {
        // dispatch does mulple rounds without switching state, need to watch for notif limit
        for ($i = 0; $i < $n; $i++) {
            $state = $this->dispatchOne();
            if ($state && $state !== GameDispatch::class) {
                return $state;
            }
        }
        return PlayerTurnConfirm::class;
    }
    function dispatchOne() {
        //$this->game->debugLog("- SINGLE: machine top: ", array_values($this->getTopOperations(null)));
        $op = $this->createTopOperationFromDbForOwner(null); // null means any
        if (!$op) {
            return StateConstants::STATE_MACHINE_HALTED;
        }
        //$this->game->notify->all("message", "starting op " . $op->getType());
        return $op->onEnteringGameState();
    }

    function getAllOperationsMulti() {
        $operations = $this->db->getOperations();
        $result = [];
        while (count($operations) > 0) {
            $op = array_shift($operations);
            if ($op["owner"] === null || $op["owner"] === OpMachine::GAME_BARIER_COLOR) {
                return $result;
            }
            $result[$op["id"]] = $op;
        }
        return [];
    }

    function getAllOperations(?string $owner = null) {
        $operations = $this->db->getOperations();
        $result = [];
        while (count($operations) > 0) {
            $op = array_shift($operations);
            if ($op["owner"] === null || $op["owner"] === $owner) {
                $result[$op["id"]] = $op;
            }
        }
        return $result;
    }
    /** Debug functions */

    function gettablearr() {
        return $this->db->gettablearr();
    }

    // STATE FUNCTIONS

    function getArgs(int $player_id) {
        $op = $this->createTopOperationFromDb($player_id);
        if ($op === null) {
            return [];
        }
        return $op->getArgs();
    }

    function onEnteringPlayerState(int $player_id) {
        $op = $this->createTopOperationFromDb($player_id);
        if ($op === null) {
            return GameDispatch::class;
        }
        return $op->onEnteringPlayerState();
    }

    function action_resolve(int $player_id, mixed $data) {
        $op = $this->createTopOperationFromDb($player_id);
        return $op->action_resolve($data);
    }

    function action_skip(int $player_id) {
        $op = $this->createTopOperationFromDb($player_id);
        return $op->action_skip();
    }
    function action_whatever(int $player_id) {
        $op = $this->createTopOperationFromDb($player_id);
        return $op->action_whatever();
    }

    function action_undo(int $player_id, int $move_id = 0) {
        try {
            // Use the wayfarers-native undo (restores only the tables listed in DbMultiUndo::needsSaving:
            // token, machine, stats). The framework's undoRestorePoint() restores the full DB including
            // the `global` table, which can wipe next_move_id if zz_savepoint_global wasn't populated.
            $this->game->dbMultiUndo->undoRestorePoint($player_id, $move_id);
        } catch (Exception $e) {
            $this->game->userAssert($e->getMessage());
        }
        return GameDispatchForced::class;
    }
}
