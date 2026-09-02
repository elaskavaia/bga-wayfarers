<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Operations\Op_journal;
use Bga\Games\wayfarers\Operations\Op_spendInfBlack;
use Bga\Games\wayfarers\OpCommon\Operation;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_spendInfBlackTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init(2);
        $this->game->tokens->createTokens();
        $this->game->_setCurrentPlayerId(PCOLOR_ID);
    }

    private function createOp(): Op_spendInfBlack {
        /** @var Op_spendInfBlack */
        $op = $this->game->machine->instantiateOperation("spendInfBlack", PCOLOR);
        return $op;
    }

    private function createJournalOp(): Op_journal {
        /** @var Op_journal */
        $op = $this->game->machine->instantiateOperation("journal", PCOLOR);
        return $op;
    }

    private function setMarkerPosition(int $position): void {
        $this->game->tokens->dbSetTokenState("marker_" . PCOLOR, $position);
    }

    private function gainBlackInfluence(int $count = 1): void {
        for ($i = 1; $i <= $count; $i++) {
            $this->game->tokens->db->moveToken("influence_" . PCOLOR . "_$i", "guild_black");
        }
    }

    private function setUsedFlag(int $state): void {
        $this->game->tokens->db->setTokenState("used_inf_black_" . PCOLOR, $state);
    }

    private function queuedOpTypes(): array {
        $ops = $this->game->machine->db->getOperations();
        return array_map(fn($o) => $o["type"], array_values($ops));
    }

    public function testNoBlackInfluenceGivesError(): void {
        $this->setMarkerPosition(0);
        $moves = $this->createOp()->getPossibleMoves();
        $this->assertArrayHasKey("err", $moves);
        $this->assertArrayNotHasKey("confirm", $moves);
    }

    public function testAlreadyUsedThisTurnGivesError(): void {
        $this->setMarkerPosition(0);
        $this->gainBlackInfluence();
        $this->setUsedFlag(1);
        $moves = $this->createOp()->getPossibleMoves();
        $this->assertArrayHasKey("err", $moves);
        $this->assertArrayNotHasKey("confirm", $moves);
    }

    /**
     * The reporter's pattern for BGA #224966: no Black Influence at the start of the turn,
     * gained during the turn (Townsfolk ability / rest income) just before Journaling.
     * RULES.md "Guilds": "Science (Black) - When Journaling, move 1 additional space."
     * Nothing requires the Influence to be held at the start of the turn.
     */
    public function testOfferedWhenBlackInfluenceGainedDuringTheTurn(): void {
        $this->setMarkerPosition(0);
        $this->setUsedFlag(0);
        $this->gainBlackInfluence();

        $moves = $this->createOp()->getPossibleMoves();
        $this->assertArrayHasKey("confirm", $moves);
    }

    /**
     * Same pattern, one step earlier: resolving the first Journal move must queue the
     * spendInfBlack offer even though the Influence only arrived this turn.
     */
    public function testFirstJournalMoveQueuesTheOfferAfterMidTurnGain(): void {
        $this->setMarkerPosition(0);
        $this->setUsedFlag(0);
        $this->gainBlackInfluence();

        $this->createJournalOp()->action_resolve([Operation::ARG_TARGET => "jpos_10"]);

        $this->assertContains("spendInfBlack", $this->queuedOpTypes());
    }

    /**
     * Documents current behavior for BGA #224966: the connector out of position 40 costs
     * 1 Black Influence (journal_material.csv "40|50|Op(n_infBlack)"), and Op_journal::resolve
     * queues that payment before the spendInfBlack offer. A player who gained exactly 1 Black
     * Influence this turn therefore pays it to cross and is never offered the Guild's extra
     * space. RULES.md "Journaling": "Some ink splotches require players to spend Influence.
     * If they do not have the Influence to spend, they cannot move past."
     * Flip this assertion if the offer is ever meant to survive spending the last Influence.
     */
    public function testCrossingAPaidSplotchConsumesTheOnlyBlackInfluence(): void {
        $this->setMarkerPosition(40);
        $this->setUsedFlag(0);
        $this->gainBlackInfluence();

        $this->createJournalOp()->action_resolve([Operation::ARG_TARGET => "jpos_50"]);

        $types = $this->queuedOpTypes();
        $this->assertContains("n_infBlack", $types);
        $this->assertContains("spendInfBlack", $types);
        $this->assertLessThan(array_search("spendInfBlack", $types), array_search("n_infBlack", $types));
    }
}
