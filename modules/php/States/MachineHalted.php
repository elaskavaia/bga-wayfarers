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

namespace Bga\Games\wayfarers\States;

use Bga\GameFramework\StateType;
use Bga\Games\wayfarers\Game;
use Bga\GameFramework\States\GameState;
use Bga\Games\wayfarers\StateConstants;

/**
 * When nothing is on stack
 */
class MachineHalted extends GameState {
    public function __construct(protected Game $game) {
        parent::__construct($game, id: StateConstants::STATE_MACHINE_HALTED, type: StateType::GAME);
    }

    public function onEnteringState() {
        if ($this->game->isEndOfGame()) {
            if ($this->game->isStudio()) {
                return PlayerTurnConfirm::class;
            }
            return StateConstants::STATE_END_GAME;
        }
        return PlayerTurnConfirm::class;
    }
}
