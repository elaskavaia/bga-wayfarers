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
 */

declare(strict_types=1);

namespace Bga\Games\wayfarers\Operations;

use Bga\Games\wayfarers\OpCommon\AiOperation;

/**
 *  AI does its focus action
 */
class Op_ai_focusAction extends AiOperation {
    public function auto(): bool {
        $boardNumber = $this->aiGetBoardNumber();
        $focusRule = $this->game->getRulesForAndAssert("aiboard_$boardNumber", "t");
        $this->queue($focusRule);
        return true;
    }
}
