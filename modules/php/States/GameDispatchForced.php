<?php

declare(strict_types=1);

namespace Bga\Games\wayfarers\States;

use Bga\GameFramework\StateType;
use Bga\Games\wayfarers\Game;
use Bga\GameFramework\States\GameState;
use Bga\Games\wayfarers\StateConstants;

/**
 * When some poperations return GameDispatch::class the stack machine can just continue dispatch without sending notif, this will force the switch
 */
class GameDispatchForced extends GameState {
    public function __construct(protected Game $game) {
        parent::__construct($game, id: StateConstants::STATE_GAME_DISPATCH_FORCED, type: StateType::GAME);
    }

    public function onEnteringState() {
        return GameDispatch::class;
    }
}
