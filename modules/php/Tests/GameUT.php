<?php

declare(strict_types=1);
namespace Bga\Games\wayfarers\Tests;

if (!defined("PCOLOR")) {
    define("PCOLOR", "6cd0f6");
    define("BCOLOR", "982fff");
    define("CCOLOR", "ff0000");
    define("ACOLOR", "ffffff"); // automa
    define("PCOLOR_ID", 10);
}

use Bga\Games\wayfarers\Common\PGameTokens;
use Bga\Games\wayfarers\Game;
use Bga\Games\wayfarers\OpCommon\Operation;
use Bga\Games\wayfarers\OpCommon\OpMachine;
use Bga\Games\wayfarers\StateConstants;

class GameUT extends Game {
    var $multimachine;
    var $xtable;
    var $gameap_number = 0;
    var $var_colonies = 0;

    function __construct() {
        parent::__construct();
        //$this->gamestate = new GameStateInMem();

        //$this->tokens = new TokensInMem($this);
        $this->xtable = [];
        $this->machine = new OpMachine(new MachineInMem($this, $this->xtable));
        //$this->_setCurrentPlayerId(10);
        $this->setPlayersNumber(2);

        $tokens = new TokensInMem($this);
        $this->tokens = new PGameTokens($this, $tokens);
    }

    function setPlayersNumber(int $num) {
        $allColors = [PCOLOR, BCOLOR, CCOLOR, "ef58a2"];
        $colors = array_slice($allColors, 0, $num);
        $this->_setPlayerBasicInfoFromColors($colors);
    }

    function getUserPreference(int $player_id, int $code): int {
        return 0;
    }

    function init(int $numPlayers = 0) {
        //$this->adjustedMaterial(true);
        if ($numPlayers > 0) {
            $this->setPlayersNumber($numPlayers);
        }
        //$this->tokens->createTokens();
        $this->gamestate->changeActivePlayer(10);
        $this->gamestate->jumpToState(StateConstants::STATE_GAME_DISPATCH);
        return $this;
    }

    function clean_cache() {}

    function getMultiMachine() {
        return $this->multimachine;
    }

    function fakeUserAction(Operation $op, $target = null) {
        return $op->action_resolve([Operation::ARG_TARGET => $target]);
    }

    // override/stub methods here that access db and stuff
}
