<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Operations\Op_ai_journal;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_ai_journalTest extends TestCase {
    private GameUT $game;
    private const AI_COLOR = "ffffff";

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init(1);
        $this->game->tokens->createTokens();
        $this->game->setupSolo();
        $this->game->_setCurrentPlayerId(PCOLOR_ID);
    }

    private function createOp(array $data = []): Op_ai_journal {
        /** @var Op_ai_journal */
        $op = $this->game->machine->instanciateOperation("ai_journal", self::AI_COLOR, $data);
        return $op;
    }

    private function setMarkerPosition(int $position, string $owner): void {
        $markerId = "marker_$owner";
        $this->game->tokens->dbSetTokenState($markerId, $position);
    }

    private function setupPosition(int $pos, string $connections, string $reward = "nop"): void {
        $posId = "jpos_$pos";
        $rules = ["conn" => $connections, "r" => $reward];
        $this->game->material->setRulesFor($posId, $rules);
    }

    private function addBlackInfluence(int $count, string $owner = self::AI_COLOR): void {
        for ($i = 1; $i <= $count; $i++) {
            $tokenId = "influence_{$owner}_$i";
            $this->game->tokens->db->moveToken($tokenId, "guild_black", 0);
        }
    }

    private function addSchemeCard(int $index, string $color, int $silverValue = 0): void {
        $card = "card_scheme_$index";
        $this->game->tokens->db->moveToken($card, "tableau_" . self::AI_COLOR, $index + 1);
        $this->game->material->setRulesFor($card, ["t" => $color, "c" => "$silverValue"]);
    }

    private function queueAndDispatch(): void {
        $this->game->machine->queue("ai_journal", self::AI_COLOR);
        $this->game->machine->dispatchAll();
    }

    /**
     * Test that when AI is ahead and spends black influence for double journal,
     * influence is actually removed (not gained).
     * This was the bug: the code used "infBlack" (gain) instead of "n_infBlack" (spend).
     */
    public function testDoubleJournalSpendsInfluenceWhenAhead(): void {
        // AI at column 3 (pos 30), human at column 1 (pos 10) => AI is ahead, costs 3
        $this->setMarkerPosition(30, self::AI_COLOR);
        $this->setMarkerPosition(10, PCOLOR);

        $this->setupPosition(30, "40");
        $this->setupPosition(40, "50");
        $this->setupPosition(50, "60");

        $this->addSchemeCard(1, "blue");
        $this->addBlackInfluence(5);
        $this->assertEquals(5, $this->game->countGuildInfluence("guild_black", self::AI_COLOR));

        $this->queueAndDispatch();

        // AI should have spent 3 black influence, leaving 2
        $this->assertEquals(2, $this->game->countGuildInfluence("guild_black", self::AI_COLOR));

        // AI should have moved 2 steps (double journal): 30 -> 40 -> 50
        $aiPos = (int) $this->game->tokens->db->getTokenState("marker_" . self::AI_COLOR);
        $this->assertEquals(50, $aiPos);
    }

    /**
     * Test that AI spends 1 black influence when behind.
     */
    public function testDoubleJournalSpendsOneWhenBehind(): void {
        // AI at column 1 (pos 10), human at column 3 (pos 30) => AI is behind, costs 1
        $this->setMarkerPosition(10, self::AI_COLOR);
        $this->setMarkerPosition(30, PCOLOR);

        $this->setupPosition(10, "20");
        $this->setupPosition(20, "30");
        $this->setupPosition(30, "40");

        $this->addSchemeCard(1, "blue");
        $this->addBlackInfluence(3);

        $op = $this->createOp();
        $this->assertEquals(1, $op->getBlackInfluenceAmountForDoubleAdvance());

        $this->queueAndDispatch();

        // Should have spent 1, leaving 2
        $this->assertEquals(2, $this->game->countGuildInfluence("guild_black", self::AI_COLOR));

        // AI should have moved 2 steps: 10 -> 20 -> 30
        $aiPos = (int) $this->game->tokens->db->getTokenState("marker_" . self::AI_COLOR);
        $this->assertEquals(30, $aiPos);
    }

    /**
     * Test that AI spends 2 black influence when in the same column.
     */
    public function testDoubleJournalSpendsTwoWhenSameColumn(): void {
        // Both at column 2 => same column, costs 2
        $this->setMarkerPosition(20, self::AI_COLOR);
        $this->setMarkerPosition(23, PCOLOR);

        $this->setupPosition(20, "30");
        $this->setupPosition(30, "40");
        $this->setupPosition(40, "50");

        $this->addSchemeCard(1, "blue");
        $this->addBlackInfluence(3);

        $op = $this->createOp();
        $this->assertEquals(2, $op->getBlackInfluenceAmountForDoubleAdvance());

        $this->queueAndDispatch();

        // Should have spent 2, leaving 1
        $this->assertEquals(1, $this->game->countGuildInfluence("guild_black", self::AI_COLOR));
    }

    /**
     * Test that AI does NOT double journal when it doesn't have enough black influence.
     */
    public function testNoDoubleJournalWhenInsufficientInfluence(): void {
        // AI ahead (costs 3) but only has 2 influence
        $this->setMarkerPosition(30, self::AI_COLOR);
        $this->setMarkerPosition(10, PCOLOR);

        $this->setupPosition(30, "40");
        $this->setupPosition(40, "50");

        $this->addSchemeCard(1, "blue");
        $this->addBlackInfluence(2);

        $this->queueAndDispatch();

        // Should NOT have spent any influence (not enough for cost of 3)
        $this->assertEquals(2, $this->game->countGuildInfluence("guild_black", self::AI_COLOR));

        // AI should have moved only one step: 30 -> 40
        $aiPos = (int) $this->game->tokens->db->getTokenState("marker_" . self::AI_COLOR);
        $this->assertEquals(40, $aiPos);
    }

    public function testGetColumnCalculation(): void {
        $op = $this->createOp();

        $this->assertEquals(0, $op->getColumn(0));
        $this->assertEquals(1, $op->getColumn(10));
        $this->assertEquals(1, $op->getColumn(15));
        $this->assertEquals(2, $op->getColumn(20));
        $this->assertEquals(2, $op->getColumn(23));
        $this->assertEquals(10, $op->getColumn(100));
    }

    public function testPathPreferenceMajorityBlue(): void {
        $this->addSchemeCard(1, "blue");
        $this->addSchemeCard(2, "blue");
        $this->addSchemeCard(3, "red");

        $op = $this->createOp();
        $this->assertEquals("blue", $op->getPathPreference());
    }

    public function testPathPreferenceMajorityRed(): void {
        $this->addSchemeCard(1, "red");
        $this->addSchemeCard(2, "red");
        $this->addSchemeCard(3, "blue");

        $op = $this->createOp();
        $this->assertEquals("red", $op->getPathPreference());
    }

    public function testPathPreferenceTieUsesLatest(): void {
        $this->addSchemeCard(1, "blue");
        $this->addSchemeCard(2, "red"); // most recent (highest state)

        $op = $this->createOp();
        $this->assertEquals("red", $op->getPathPreference());
    }
}
