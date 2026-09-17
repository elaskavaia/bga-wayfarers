<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Game;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_cardDrawTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init(1); // solo, so the automa is a known player
        $this->game->tokens->createTokens();
        $this->game->_setCurrentPlayerId(PCOLOR_ID);
    }

    /**
     * BGA #243940. The Explore/Voyage worker spaces are "3n_food:3cardDraw(water)". The automa
     * ignores the cost, but it also has no hand to draw into and pick from, so drawing would stall
     * its turn with "Operation does not implement automata 3cardDraw(water)".
     *
     * RULES.md, Solo Play > Acquiring Cards and Upgrade Tiles: the AI simply takes a Card, using its
     * own priority rules, so a draw resolves as a plain acquisition of that card type.
     */
    #[PHPUnit\Framework\Attributes\DataProvider("deckTypeProvider")]
    public function testAutomaAcquiresInsteadOfDrawing(string $deckType, string $expected): void {
        $op = $this->game->machine->instantiateOperation("3cardDraw($deckType)", ACOLOR);

        $this->assertEquals(Game::PLAYER_AUTOMA, $op->getPlayerId());
        $this->assertTrue($op->auto());
        $this->assertEquals([$expected], $this->game->queuedTypes(ACOLOR));
    }

    public static function deckTypeProvider(): array {
        return [
            "land" => ["land", "ai_cardLand"],
            "water" => ["water", "ai_cardWater"]
        ];
    }

    /** A human still draws and picks, so the op has to wait for them. */
    public function testHumanStillConfirmsTheDraw(): void {
        $op = $this->game->machine->instantiateOperation("3cardDraw(water)", PCOLOR);

        $this->assertFalse($op->auto());
        $this->assertEquals([], $this->game->queuedTypes(PCOLOR));
    }
}
