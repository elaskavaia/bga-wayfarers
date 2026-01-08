<?php

declare(strict_types=1);

namespace Bga\Games\wayfarers\States;

use Bga\GameFramework\StateType;
use Bga\Games\wayfarers\Game;
use Bga\GameFramework\States\GameState;
use Bga\Games\wayfarers\StateConstants;

class GameDispatch extends GameState {
    public function __construct(protected Game $game) {
        parent::__construct($game, id: StateConstants::STATE_GAME_DISPATCH, type: StateType::GAME);
    }

    public function onEnteringState() {
        $state = $this->game->machine->dispatchAll();
        return $state;
    }
}
