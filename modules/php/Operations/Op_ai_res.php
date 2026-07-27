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
 *  AI move on res track
 */
class Op_ai_res extends AiOperation {
    public function auto(): bool {
        $owner = $this->getOwner();
        [$trackerId, $currentPos] = $this->game->tokens->getTrackerIdAndValue($owner, "res");
        $silver = $this->getCount();
        $newPos = ($currentPos + $silver) % 8;
        $this->dbSetTokenState($trackerId, $newPos, clienttranslate('${player_name} moves resource marker to ${pos} ${reason}'), [
            "pos" => $newPos
        ]);

        //   - [ ] Resolve resource track effects passed over (comet, guild influence, townsfolk card)
        // Check if we passed position 4.5 (between 4 and 5)
        // Silver is only 0, 1, or 2, so we can't wrap and pass 4.5 at the same time
        if ($currentPos <= 4 && $newPos >= 5) {
            // means we passed 4.5 where bonus lies
            $boardNumber = $this->aiGetBoardNumber();
            $bonus = $this->game->getRulesForAndAssert("aiboard_$boardNumber", "r2");

            $data = ["reason" => "restracker_bonus"];
            // A pending 'turn' op for the automa means we are inside a human's action - the bonus
            // must wait for the automa to activate instead of interleaving into that action.
            // On the final turn there is no automa turn left, so fall back to the (ownerless)
            // finalScoring op, which still keeps the bonus out of the human's unfinished action.
            $pending = $this->game->machine->db->getOperations($owner, "turn");
            if (!$pending) {
                $pending = $this->game->machine->db->getOperations(null, "finalScoring");
            }
            if ($pending) {
                $rank = (int) reset($pending)["rank"];
                $this->game->machine->insert($bonus, $owner, $data, $rank);
            } else {
                $this->queue($bonus, $owner, $data);
            }
        }
        return true;
    }
}
