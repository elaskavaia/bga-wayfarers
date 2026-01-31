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
use Bga\Games\wayfarers\States\GameDispatchForced;

/** Special operation that act as barrier for all multi player operations */
class Op_barrier extends Operation {
    function onEnteringGameState() {
        $this->destroy();
        $this->game->customUndoSavepoint(0, 1);
        return GameDispatchForced::class;
    }
}
