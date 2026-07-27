<?php

declare(strict_types=1);

use Tests\GameUT;
use PHPUnit\Framework\TestCase;

/**
 * Regression guard for BGA #234349: land card 9 showed no description when clicked.
 * A missing pipe in misc/cardland_material.csv shifted its description into `tor`, leaving
 * `todr` unset - and the client builds the description section from `todr`.
 */
final class CardLandDescriptionTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init();
    }

    public function testEveryLandCardDescribesItsActions(): void {
        $cards = array_filter($this->game->material->get(), fn($key) => preg_match('/^card_land_\d+$/', $key), ARRAY_FILTER_USE_KEY);
        $this->assertNotEmpty($cards);
        foreach ($cards as $key => $rules) {
            $this->assertSame(($rules["dr"] ?? "") !== "", ($rules["todr"] ?? "") !== "", "$key: todr must be set exactly when dr is");
            $this->assertSame(($rules["r"] ?? "") !== "", ($rules["tor"] ?? "") !== "", "$key: tor must be set exactly when r is");
        }
    }
}
