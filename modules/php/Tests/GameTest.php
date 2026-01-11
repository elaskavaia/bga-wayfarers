<?php

declare(strict_types=1);

use Bga\GameFramework\NotificationMessage;
use Bga\GameFramework\Notify;
use Bga\Games\wayfarers\OpCommon\OpExpression;
use Bga\Games\wayfarers\Game;
use Bga\Games\wayfarers\OpCommon\Operation;
use Bga\Games\wayfarers\Common\PGameTokens;
use Bga\Games\wayfarers\OpCommon\ComplexOperation;
use Bga\Games\wayfarers\Operations\Op_or;
use Bga\Games\wayfarers\Operations\Op_paygain;
use Bga\Games\wayfarers\Operations\Op_seq;
use Bga\Games\wayfarers\Operations\Op_cotag;
use Bga\Games\wayfarers\Operations\Op_craft;
use Bga\Games\wayfarers\Operations\Op_pay;
use Bga\Games\wayfarers\OpCommon\OpMachine;
use Bga\Games\wayfarers\Operations\Op_barrier;
use Bga\Games\wayfarers\Operations\Op_furnish;
use Bga\Games\wayfarers\Operations\Op_furnishPay;
use Bga\Games\wayfarers\Operations\Op_task;
use Bga\Games\wayfarers\Operations\Op_turn;
use Bga\Games\wayfarers\Operations\Op_turnall;
use Bga\Games\wayfarers\Operations\Op_turnpick;
use Bga\Games\wayfarers\StateConstants;
use Bga\Games\wayfarers\States\GameDispatch;
use Bga\Games\wayfarers\States\GameDispatchForced;
use Bga\Games\wayfarers\States\MachineHalted;
use Bga\Games\wayfarers\States\MultiPlayerMaster;
use Bga\Games\wayfarers\States\PlayerTurn;
use Bga\Games\wayfarers\Tests\MachineInMem;
use Bga\Games\wayfarers\Tests\TokensInMem;
use PHPUnit\Framework\TestCase;

use function Bga\Games\wayfarers\array_get;
use function Bga\Games\wayfarers\startsWith;

//    'player_colors' => ["ef58a2", "a0d28c", "6cd0f6", "ffcc02"],
define("PCOLOR", "a0d28c");
define("BCOLOR", "6cd0f6");
define("ACOLOR", "ffcc02");

class FakeNotify extends Notify {
    public function all(string $notifName, string|NotificationMessage $message = "", array $args = []): void {
        //echo "Notify all: $notifName : $message\n";
    }
    public function player(int $playerId, string $notifName, string|NotificationMessage $message = "", array $args = []): void {
        //echo "Notify player $playerId: $notifName : $message\n";
    }
}

class GameUT extends Game {
    var $multimachine;
    var $xtable;
    var $gameap_number = 0;
    var $var_colonies = 0;
    var $_colors = [];

    function __construct() {
        parent::__construct();
        //$this->gamestate = new GameStateInMem();

        //$this->tokens = new TokensInMem($this);
        $this->xtable = [];
        $this->machine = new OpMachine(new MachineInMem($this, $this->xtable));
        $this->curid = 1;
        $this->_colors = [PCOLOR, BCOLOR];
        $this->notify = new FakeNotify();

        $tokens = new TokensInMem($this);
        $this->tokens = new PGameTokens($this, $tokens);
    }

    public function _($s): string {
        return $s;
    }

    function getPlayersNumber(): int {
        return count($this->_colors);
    }

    function setPlayersNumber(int $num) {
        switch ($num) {
            case 2:
                $this->_colors = [PCOLOR, BCOLOR];
                break;
            case 3:
                $this->_colors = [PCOLOR, BCOLOR, ACOLOR];
                break;
            case 4:
                $this->_colors = [PCOLOR, BCOLOR, ACOLOR, "ef58a2"];
                break;
            default:
                throw new BgaVisibleSystemException("Invalid number of players");
        }
    }

    function getUserPreference(int $player_id, int $code) {
        return 0;
    }
    function getAutomaColor() {
        return ACOLOR;
    }

    function init(int $x = 0) {
        //$this->adjustedMaterial(true);
        //$this->createTokens();
        $this->gamestate->changeActivePlayer(10);
        $this->gamestate->jumpToState(StateConstants::STATE_GAME_DISPATCH);
        return $this;
    }

    function clean_cache() {}

    function getMultiMachine() {
        return $this->multimachine;
    }

    public $curid;

    public function getCurrentPlayerId($bReturnNullIfNotLogged = false): string|int {
        return $this->curid;
    }

    public function getCurrentPlayerColor(): string {
        return $this->getPlayerColorById($this->curid);
    }

    function _getColors() {
        return $this->_colors;
    }

    function fakeUserAction(Operation $op, $target = null) {
        return $op->action_resolve([Operation::ARG_TARGET => $target]);
    }

    // override/stub methods here that access db and stuff
}

final class GameTest extends TestCase {
    private GameUT $game;
    function dispatchOneStep($done = null) {
        $game = $this->game;
        $state = $game->machine->dispatchOne();
        if ($state === null) {
            $state = GameDispatch::class;
        }
        if ($done !== null) {
            $this->assertEquals($done, $state);
        }
        $op = $this->game->machine->createTopOperationFromDbForOwner(null);
        return $op;
    }
    function dispatch($done = null) {
        $game = $this->game;
        $state = $game->machine->dispatchAll();
        if ($state === null) {
            $state = GameDispatch::class;
        }
        if ($done !== null) {
            $this->assertEquals($done, $state);
        }
        $op = $this->game->machine->createTopOperationFromDbForOwner(null);
        return $op;
    }
    function game(int $x = 0) {
        $game = new GameUT();
        $game->init($x);
        $this->game = $game;
        return $game;
    }

    protected function setUp(): void {
        $this->game();
    }
    public function testInstanciateAllOperations() {
        $this->game();
        $token_types = $this->game->material->get();
        $tested = [];
        foreach ($token_types as $key => $info) {
            $this->assertTrue(!!$key);
            if (!startsWith($key, "Op_")) {
                continue;
            }
            echo "testing op $key\n";
            $this->subTestOp($key, $info);
            $tested[$key] = 1;
        }

        $dir = dirname(dirname(__FILE__));
        $files = glob("$dir/Operations/*.php");

        foreach ($files as $file) {
            $base = basename($file);
            $this->assertTrue(!!$base);
            if (!startsWith($base, "Op_")) {
                continue;
            }
            $mne = preg_replace("/Op_(.*).php/", "\\1", $base);
            $key = "Op_{$mne}";
            if (array_key_exists($key, $tested)) {
                continue;
            }
            echo "testing op $key\n";
            $this->subTestOp($key, ["type" => $mne]);
        }
    }

    function subTestOp($key, $info = []) {
        $type = array_get($info, "type", substr($key, 3));
        $this->assertTrue(!!$type);

        /** @var Operation */
        $op = $this->game->machine->instanciateOperation($type, PCOLOR);

        $args = $op->getArgs();
        $ttype = array_get($args, "ttype");
        $this->assertTrue($ttype != "", "empty ttype for $key");

        $propt = array_get($args, "prompt");
        if (isset($info["prompt"])) {
            $this->assertEquals($info["prompt"], $propt, $type);
        }

        $this->assertFalse(str_contains($op->getOpName(), "?"), $op->getOpName());
        $this->assertFalse($op->getOpName() == $op->getType(), "No name set for operation $key");
        return $op;
    }

    public function testAllDiceSpots() {
        $this->game();
        $token_types = $this->game->material->get();

        foreach ($token_types as $key => $info) {
            $this->assertTrue(!!$key);
            if (!startsWith($key, "dslot_")) {
                continue;
            }
            echo "testing dspot $key\n";
            $r = $info["d"] ?? "";
            $this->assertTrue($r != "", "empty d for $key");
            //$this->game->machine->instanciateOperation($r, PCOLOR);
            $r = $info["dr"] ?? "";
            $this->assertTrue($r != "", "empty dr for $key");
            $this->game->machine->instanciateOperation($r, PCOLOR);
        }
    }
}
