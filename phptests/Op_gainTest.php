<?php

declare(strict_types=1);

use Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_gainTest extends TestCase {
    private GameUT $game;
    private const AI_COLOR = "ffffff";

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init(1);
        $this->game->tokens->createTokens();
        $this->game->setupSolo();
        $this->game->_setCurrentPlayerId(PCOLOR_ID);
        $this->game->tokens->db->setTokenState("tracker_res_" . self::AI_COLOR, 0);
    }

    private function gainForAi(string $expression): int {
        $this->game->machine->queue($expression, self::AI_COLOR);
        $this->game->machine->dispatchAll();
        return (int) $this->game->tokens->getTrackerValue(self::AI_COLOR, "res");
    }

    public function testSingleSilverMovesResourceMarkerOneSpace(): void {
        $this->assertSame(1, $this->gainForAi("coin"));
    }

    public function testSingleProvisionMovesResourceMarkerOneSpace(): void {
        $this->assertSame(1, $this->gainForAi("food"));
    }

    /**
     * AI Board conversion strip: 1 provision or 1 silver = 1 space, so a gain of 2 is 2 spaces (BGA #239548).
     */
    public function testTwoSilverMovesResourceMarkerTwoSpaces(): void {
        $this->assertSame(2, $this->gainForAi("2coin"));
    }

    public function testTwoProvisionsMoveResourceMarkerTwoSpaces(): void {
        $this->assertSame(2, $this->gainForAi("2food"));
    }
}
