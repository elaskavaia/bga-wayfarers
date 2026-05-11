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
 * AI variant of infAny: picks a guild via resource-track color priority and delegates the actual placement to Op_inf<Color>,
 * which already handles the automa case in Op_infBase.
 */
class Op_ai_infAny extends AiOperation {
    public function auto(): bool {
        foreach ($this->getColorPriority() as $color) {
            if ($color === "green") {
                continue; // no green guild
            }
            $this->queue("inf" . ucfirst($color), $this->getOwner());
            return true;
        }
        return true;
    }
}
