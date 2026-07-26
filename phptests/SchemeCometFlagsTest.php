<?php

declare(strict_types=1);

use Tests\GameUT;
use PHPUnit\Framework\TestCase;

/**
 * Regression guard for BGA #230000: the scheme cards' `comet` flag must match the printed card art.
 * Per img/cards_scheme.jpg (comet icon, bottom-right), a comet appears on cards 2, 3, 4 and 6 only.
 * This flag drives the tooltip AND the AI rest comet-track advance (Op_ai_rest.php:59-61), so a wrong
 * value both mislabels the card and mis-scores the AI. The flags were previously inverted (1,1,0,1,1,0).
 */
final class SchemeCometFlagsTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init();
        $this->game->tokens->createTokens();
    }

    public function testSchemeCometFlagsMatchCardArt(): void {
        $expected = [1 => 0, 2 => 1, 3 => 1, 4 => 1, 5 => 0, 6 => 1];
        foreach ($expected as $num => $comet) {
            $this->assertSame(
                $comet,
                (int) $this->game->getRulesFor("card_scheme_$num", "comet", -1),
                "card_scheme_$num comet flag must match the card art"
            );
        }
        $this->assertSame(4, array_sum($expected), "exactly 4 of the 6 scheme cards show a comet (RULES.md)");
    }
}
