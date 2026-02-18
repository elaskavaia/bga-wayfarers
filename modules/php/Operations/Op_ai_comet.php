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
 * AI comet track advancement
 */
class Op_ai_comet extends AiOperation {
    public function auto(): bool {
        $owner = $this->getOwner();
        $count = $this->getCount();

        [$trackerId, $currentPos] = $this->game->tokens->getTrackerIdAndValue($owner, "comet");
        $newPos = min($currentPos + $count, 10); // Max comet track is 10

        if ($newPos != $currentPos) {
            $this->dbSetTokenState($trackerId, $newPos, clienttranslate('${player_name} moves comet marker to ${pos}'), [
                "pos" => $newPos,
            ]);
        } else {
            $this->notifyMessage(clienttranslate('${player_name} does not move comet marker - maxed out at 10'));
        }

        return true;
    }
}
