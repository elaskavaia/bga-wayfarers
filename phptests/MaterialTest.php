<?php

declare(strict_types=1);

use Tests\GameUT;
use PHPUnit\Framework\TestCase;

use function Bga\Games\wayfarers\getPart;
use function Bga\Games\wayfarers\startsWith;

/**
 * Data checks on the generated Material - the CSV files in misc/ are hand edited, so a shifted pipe,
 * a flipped flag or a rule naming an operation that does not exist is a real and recurring failure mode.
 * Every rule expression the material names is instantiated (or evaluated) here so a typo fails the build
 * instead of a game in progress.
 */
final class MaterialTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init();
        $this->game->tokens->createTokens();
    }

    private function instanciate(string $expression, string $key, string $context): void {
        try {
            $this->game->machine->instantiateOperation($expression, PCOLOR);
        } catch (\Exception $e) {
            $this->fail("$key $context=$expression: " . $e->getMessage());
        }
    }

    private function evaluate(string $expression, string $key, string $context): void {
        try {
            $this->game->evaluateExpression($expression, PCOLOR);
        } catch (\Exception $e) {
            $this->fail("$key $context=$expression: " . $e->getMessage());
        }
    }

    /** All material entries whose key starts with $prefix. */
    private function entries(string $prefix): array {
        $entries = array_filter($this->game->material->get(), fn($key) => startsWith($key, $prefix), ARRAY_FILTER_USE_KEY);
        $this->assertNotEmpty($entries, "no material entries for '$prefix'");
        return $entries;
    }

    // -- Rule expressions ---------------------------------------------------------

    public function testAllDiceSpots() {
        foreach ($this->entries("card_") as $key => $info) {
            if (!($info["d"] ?? "")) {
                continue;
            }
            $r = $info["dr"] ?? "";
            $this->assertTrue($r != "", "empty dr for $key");
            $this->instanciate($r, $key, "dr");
        }
    }

    public function testAllInstRules() {
        foreach ($this->entries("card_") as $key => $info) {
            $r = $info["r"] ?? "";
            if (!$r) {
                continue;
            }
            $this->instanciate($r, $key, "r");
        }
    }

    public function testAllInspExpr() {
        foreach ($this->entries("card_insp_") as $key => $info) {
            $r = $info["collect"] ?? "";
            $this->assertTrue($r != "", "empty collect for $key");
            $this->evaluate($r, $key, "collect");
        }
    }

    public function testAllSpaceCards() {
        foreach ($this->entries("card_space_") as $key => $info) {
            $r = $info["vpexp"] ?? "";
            $this->assertTrue($r != "", "empty vpexp for $key");
            $this->evaluate((string) $r, $key, "vpexp");

            $r = $info["r"] ?? "";
            if ($r) {
                $this->instanciate($r, $key, "r");
            }
            $this->assertTrue(($info["tags"] ?? "") != "", "empty tags for $key");
        }
    }

    public function testFolk() {
        foreach ($this->entries("card_folk_") as $key => $info) {
            $this->assertTrue(($info["cost"] ?? "") != "", "empty cost for $key");

            $r = $info["dr"] ?? ($info["da"] ?? "");
            $this->assertTrue($r != "", "empty dr for $key");
            $this->instanciate($r, $key, "dr");
        }
    }

    /** aiboard_X: every rule the AI board names has to be an implemented operation. */
    public function testAIBoard() {
        $token_types = $this->game->material->get();
        foreach ($this->entries("aiboard_") as $key => $info) {
            $bnum = getPart($key, 1);
            foreach (["t", "r1", "r2"] as $field) {
                $r = $info[$field] ?? "";
                $this->assertTrue($r != "", "empty $field for $key");
                $this->instanciate($r, $key, $field);
            }
            for ($i = 0; $i < 21; $i++) {
                $bonus = "aibonus_{$bnum}_{$i}";
                $this->assertArrayHasKey($bonus, $token_types);
                $r = $token_types[$bonus]["r"] ?? "";
                if ($r == "") {
                    continue;
                }
                $this->instanciate($r, $bonus, "r");
            }
        }
    }

    /** card_scheme_X: both the primary action and the fallback the AI turn falls back to have to exist. */
    public function testSchemeCardRules() {
        foreach ($this->entries("card_scheme_") as $key => $info) {
            foreach (["r1", "r2"] as $field) {
                $r = $info[$field] ?? "";
                $this->assertTrue($r != "", "empty $field for $key");
                $this->instanciate($r, $key, $field);
            }
            $this->assertTrue(is_numeric($info["p"] ?? ""), "$key: p (upgrade priority) must be a number");
            $this->assertTrue(is_numeric($info["c"] ?? ""), "$key: c (silver value) must be a number");
        }
    }

    /** spot_res_X: one entry per position of the AI resource track (tracker_res state 0-7). */
    public function testResourceTrackSpots() {
        $spots = $this->entries("spot_res_");
        $this->assertCount(8, $spots, "the resource track has 8 positions");
        foreach ($spots as $key => $info) {
            $this->assertTrue(($info["t"] ?? "") != "", "$key: t (priority color) must be set");
            $this->assertTrue(is_numeric($info["c"] ?? ""), "$key: c (inspiration card order) must be a number");
        }
    }

    // -- Regression guards --------------------------------------------------------

    /**
     * Regression guard for BGA #234349: land card 9 showed no description when clicked.
     * A missing pipe in misc/cardland_material.csv shifted its description into `tor`, leaving
     * `todr` unset - and the client builds the description section from `todr`.
     */
    public function testEveryLandCardDescribesItsActions(): void {
        $cards = array_filter($this->game->material->get(), fn($key) => preg_match('/^card_land_\d+$/', $key), ARRAY_FILTER_USE_KEY);
        $this->assertNotEmpty($cards);
        foreach ($cards as $key => $rules) {
            $this->assertSame(($rules["dr"] ?? "") !== "", ($rules["todr"] ?? "") !== "", "$key: todr must be set exactly when dr is");
            $this->assertSame(($rules["r"] ?? "") !== "", ($rules["tor"] ?? "") !== "", "$key: tor must be set exactly when r is");
        }
    }

    /**
     * Regression guard for BGA #230000: the scheme cards' `comet` flag must match the printed card art.
     * Per img/cards_scheme.jpg (comet icon, bottom-right), a comet appears on cards 2, 3, 4 and 6 only.
     * This flag drives the tooltip AND the AI rest comet-track advance (Op_ai_rest.php:59-61), so a wrong
     * value both mislabels the card and mis-scores the AI. The flags were previously inverted (1,1,0,1,1,0).
     */
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
