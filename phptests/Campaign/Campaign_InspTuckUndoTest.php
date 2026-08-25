<?php

declare(strict_types=1);

namespace Tests\Campaign;

/**
 * BGA #227997 - "Tucking an inspiration card is not undoable".
 * Report: "I tucked an inspiration card under the wrong space card, attempted to
 * undo, was unable."
 *
 * Undo restores to the latest barrier savepoint, and each new barrier wipes the
 * earlier ones (DbMultiUndo::doSaveUndoSnapshot). Savepoints are taken at turn
 * start and right after hidden information is revealed (cardDraw/reroll/newDie) -
 * the post-reveal boundary is what makes reroll fishing impossible.
 *
 * Journal spots 20/23/27 and 60/63/67 reward an inspiration tuck plus a new die.
 * With cardInsp ordered first, the player tucked and THEN Op_newDie rolled and
 * savepointed, wiping the turn-start snapshot: the only restore point sat after
 * the tuck, so Undo could not reach back before it. The reward order must be
 * newDie,cardInsp - the roll and its savepoint land before the tuck, the tuck
 * stays undoable, and the roll still cannot be re-fished.
 *
 * Drives the live jpos_20 reward string through the real op machine, exactly as
 * Op_journal::resolve queues it, so this test follows the material.
 */
class Campaign_InspTuckUndoTest extends CampaignBase {
    public function testJournalRewardRollsTheDieBeforeTheTuckSoTheTuckStaysUndoable(): void {
        $this->setupGame(2);
        $pc = $this->getActiveColor();
        $this->assertOpType("turn");

        // Two space cards to tuck under, so a wrong choice is possible.
        $deckSpace = array_keys($this->game->tokens->getTokensOfTypeInLocation("card_space", "deck_space"));
        [$wrongCard, $rightCard] = [$deckSpace[0], $deckSpace[1]];
        $this->game->tokens->db->moveToken($wrongCard, "tableau_$pc", 2);
        $this->game->tokens->db->moveToken($rightCard, "tableau_$pc", 3);

        $supplyDie = $this->game->tokens->db->getTokensOfTypeInLocationSingleKey("dice_$pc", "supply");
        $this->assertNotEmpty($supplyDie, "sanity: a die must be in supply for newDie to resolve");

        // Queue the live journal reward of spot 20, exactly as Op_journal::resolve does.
        $reward = $this->game->getRulesFor("jpos_20");
        $this->assertStringContainsString("cardInsp", $reward);
        $this->assertStringContainsString("newDie", $reward);
        $this->game->machine->push($reward, $pc, ["jpos" => "jpos_20", "reason" => "jpos_20"]);
        // Jump to dispatch so the reward expression is expanded by the machine, as after a real journal move.
        $this->game->gamestate->jumpToState(\Bga\Games\wayfarers\StateConstants::STATE_GAME_DISPATCH);
        $this->driver->runDispatchLoop();
        $this->driver->emitGameStateChange();

        $this->game->savepointCalls = [];

        // Resolve the reward up to the tuck-target decision, in whatever order the ops come.
        $inspCard = array_keys($this->game->tokens->getTokensOfTypeInLocationWithChildren("card_insp", "mainarea"))[0];
        $guard = 0;
        while (true) {
            $this->assertLessThan(8, $guard++, "never reached the tuck-target decision");
            $type = $this->getOpType();
            if ($type == "seq" || $type == "newDie") {
                $this->respond("confirm");
            } elseif ($type == "cardInsp") {
                if (in_array($wrongCard, $this->getOpArgs()["target"] ?? [])) {
                    break;
                }
                $this->respond($inspCard);
            } else {
                $this->fail("unexpected operation '$type' while resolving the journal reward");
            }
        }

        // The reveal must be fully behind us at the tuck decision: die already rolled into the
        // tableau and its savepoint recorded. With the buggy cardInsp-first order the roll (and
        // the savepoint that wipes every earlier restore point) came AFTER the tuck, so undo
        // could not reach back before it (BGA #227997).
        $this->assertNotEmpty(
            $this->game->savepointCalls,
            "the newDie reveal savepoint must precede the tuck decision, or undo cannot reach back before the tuck"
        );
        $this->assertSame(
            ["player_id" => (int) $this->game->getMostlyActivePlayerId(), "barrier" => 1, "label" => "undo"],
            $this->game->savepointCalls[0],
            "the pre-tuck savepoint is the player's own barrier savepoint"
        );
        $this->assertEquals("tableau_$pc", $this->game->tokens->db->getTokenLocation($supplyDie), "the new die is rolled before the tuck is chosen");

        // Nothing of the reward remains after the tuck to take another savepoint. (The turn op
        // re-entering afterwards is a harness artifact of pushing the reward directly; in a real
        // game the next boundary comes with the next turn, behind the confirm gate.)
        $wrongState = (int) $this->game->tokens->db->getTokenState($wrongCard);
        $this->respond($wrongCard);

        // The reorder must not break the reward itself: card tucked at the chosen slot.
        $this->assertEquals("tableau_$pc", $this->game->tokens->db->getTokenLocation($inspCard), "insp card tucked into the tableau");
        $this->assertEquals($wrongState, (int) $this->game->tokens->db->getTokenState($inspCard), "tucked at the chosen space card's slot");
        $this->assertOpType("turn", "reward fully resolved");
    }
}
