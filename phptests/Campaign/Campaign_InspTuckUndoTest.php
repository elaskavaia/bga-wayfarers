<?php

declare(strict_types=1);

namespace Tests\Campaign;

/**
 * BGA #227997 - "Tucking an inspiration card is not undoable".
 *
 * A player gains an Inspiration Card (Op_cardInsp) and tucks it under a Space Card,
 * then realises it is the WRONG Space Card and wants to undo just that choice. The
 * reporter had "no ability to undo" the tuck within the (still unconfirmed) turn.
 *
 * Undo in this engine is boundary-based: undo restores the whole DB to the nearest
 * "undo savepoint", and the only ops that register one are turn/cardDraw/reroll/newDie
 * (all via Game::customUndoSavepoint - see modules/php/Operations/Op_turn.php:45 etc).
 * Op_cardInsp registers NONE, so the two-step "pick inspiration card -> pick space card
 * to tuck under" has no undo boundary of its own: the tuck target cannot be re-picked in
 * isolation. There is nothing between the two steps to roll back to.
 *
 * This test drives the real Op_cardInsp two-step flow through the op-machine and pins
 * that behaviour: gaining + tucking an Inspiration Card records no undo savepoint, and the
 * op is destroyed once the tuck resolves, so the target is committed with no way back short
 * of undoing the entire turn.
 *
 * Note on harness scope: the campaign harness keeps token/machine state in in-memory stubs,
 * while DbMultiUndo snapshots the real (empty) `token`/`machine`/`multiundo` SQL tables - so
 * getAvailableUndoMoves() is always 0 here and DB-level undo restore cannot be exercised
 * in-process. We therefore observe undoability at its source: the customUndoSavepoint
 * boundaries the engine records. Op_cardInsp records none for the tuck.
 *
 * Documents buggy behavior for BGA #227997; flip once fixed (the tuck should be undoable
 * within the turn).
 */
class Campaign_InspTuckUndoTest extends CampaignBase {
    public function testTuckingAnInspirationCardRegistersNoUndoBoundary(): void {
        $this->setupGame(2);
        $pc = $this->getActiveColor();
        $this->assertOpType("turn");

        // Starting a turn is the one undo boundary the engine records for this player.
        $this->assertNotEmpty($this->game->savepointCalls, "sanity: Op_turn records the turn-start undo savepoint");

        // Give the player two Space Cards to tuck under, so a WRONG choice is possible.
        $deckSpace = array_keys($this->game->tokens->getTokensOfTypeInLocation("card_space", "deck_space"));
        [$wrongCard, $rightCard] = [$deckSpace[0], $deckSpace[1]];
        $this->game->tokens->db->moveToken($wrongCard, "tableau_$pc", 2);
        $this->game->tokens->db->moveToken($rightCard, "tableau_$pc", 3);

        // Queue the real "acquire an Inspiration card" operation for this player (as Muse's Light
        // / cardspace 96 would) and dispatch to it.
        $this->game->machine->push("cardInsp", $pc);
        $this->driver->runDispatchLoop();
        $this->driver->emitGameStateChange();
        $this->assertOpType("cardInsp");

        // From here on, everything the player does is the inspiration acquire + tuck. Watch for any
        // NEW undo boundary the engine records while the player makes the tuck decision.
        $this->game->savepointCalls = [];

        // Step 1: choose which Inspiration Card to gain. Now the player is asked WHICH space card to
        // tuck under - the exact decision the reporter got wrong and wanted to redo.
        $inspCard = array_keys($this->game->tokens->getTokensOfTypeInLocationWithChildren("card_insp", "mainarea"))[0];
        $this->respond($inspCard);
        $this->assertOpType("cardInsp", "second step: choose the space card to tuck under");
        $this->assertValidTarget($wrongCard);
        $this->assertValidTarget($rightCard);

        // BUG #227997: the player is at the tuck-target decision, but the engine has recorded NO undo
        // savepoint for it. There is no boundary to roll back to that would let them re-pick the space
        // card. The tuck is not an independently undoable decision point.
        $this->assertSame(
            [],
            $this->game->savepointCalls,
            "the inspiration tuck decision establishes no undo boundary, so the target cannot be re-picked within the turn"
        );

        // Step 2: tuck it under the WRONG space card.
        $wrongState = (int) $this->game->tokens->db->getTokenState($wrongCard);
        $this->respond($wrongCard);

        // The tuck resolved: inspiration card now sits at the wrong space card's position, and the
        // right one is still free - a committed mis-tuck with no per-step way back.
        $this->assertEquals("tableau_$pc", $this->game->tokens->db->getTokenLocation($inspCard), "insp card was tucked into the tableau");
        $this->assertEquals($wrongState, (int) $this->game->tokens->db->getTokenState($inspCard), "tucked at the WRONG space card's slot");
        $this->assertOpType("turn", "cardInsp is destroyed once the tuck resolves - the choice is committed");

        // The only undo savepoints the engine ever records around this are turn/die boundaries, and any
        // that follow the tuck sit AFTER it (next turn boundary) - none captures the pre-tuck state that
        // would let the player re-pick the space card. Every recorded boundary is a barrier savepoint,
        // never a fine-grained one for the tuck itself.
        foreach ($this->game->savepointCalls as $call) {
            $this->assertSame(1, $call["barrier"], "only coarse turn/die barrier savepoints exist - none is a per-tuck boundary");
        }
    }
}
