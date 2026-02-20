<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class RefillMainAreaTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->setPlayersNumber(1);
        $this->game->init();
        $this->game->tokens->createTokens();
        $this->game->refillMainArea();
    }

    private function getMainAreaCards(string $ctype): array {
        $cards = $this->game->tokens->getTokensOfTypeInLocation("card_$ctype", "mainarea", null, "token_state");
        return $cards;
    }

    private function getCardAtPosition(string $ctype, int $pos): ?string {
        $cards = $this->getMainAreaCards($ctype);
        foreach ($cards as $key => $info) {
            if ((int) $info["state"] == $pos) {
                return $key;
            }
        }
        return null;
    }

    private function getPositions(string $ctype): array {
        $cards = $this->getMainAreaCards($ctype);
        return array_map(fn($c) => (int) $c["state"], array_values($cards));
    }

    public function testNoGapsDoesNothing(): void {
        foreach (["folk", "space", "land", "water", "insp"] as $ctype) {
            $this->assertCount(4, $this->getMainAreaCards($ctype), "Should start with 4 $ctype cards");
        }

        $this->game->refillMainArea();

        foreach (["folk", "space", "land", "water", "insp"] as $ctype) {
            $this->assertCount(4, $this->getMainAreaCards($ctype), "Should still have 4 $ctype cards after refill");
        }
    }

    public function testRefillsOneGap(): void {
        $removedCard = $this->getCardAtPosition("land", 2);
        $this->assertNotNull($removedCard, "Should find a card at position 2");
        $this->game->tokens->db->moveToken($removedCard, "discard");

        $this->game->refillMainArea();

        $this->assertCount(4, $this->getMainAreaCards("land"), "Should have 4 land cards after refill");
        $this->assertEquals([1, 2, 3, 4], $this->getPositions("land"), "Cards should be compacted to positions 1-4");
    }

    public function testRefillsMultipleGaps(): void {
        $card1 = $this->getCardAtPosition("water", 1);
        $card3 = $this->getCardAtPosition("water", 3);
        $this->assertNotNull($card1);
        $this->assertNotNull($card3);
        $this->game->tokens->db->moveToken($card1, "discard");
        $this->game->tokens->db->moveToken($card3, "discard");

        $this->game->refillMainArea();

        $this->assertCount(4, $this->getMainAreaCards("water"), "Should have 4 water cards after refill");
        $this->assertEquals([1, 2, 3, 4], $this->getPositions("water"), "Cards should be compacted to positions 1-4");
    }

    public function testCompactsGapCorrectly(): void {
        $pos3Card = $this->getCardAtPosition("space", 3);
        $pos2Card = $this->getCardAtPosition("space", 2);
        $this->assertNotNull($pos3Card);
        $this->assertNotNull($pos2Card);
        $this->game->tokens->db->moveToken($pos2Card, "discard");

        $this->game->refillMainArea();

        $cards = $this->getMainAreaCards("space");
        $this->assertEquals(2, (int) $cards[$pos3Card]["state"], "Card from position 3 should slide to position 2");
    }

    public function testOnlyAffectsTypesWithGaps(): void {
        $folkCard = $this->getCardAtPosition("folk", 1);
        $this->assertNotNull($folkCard);
        $this->game->tokens->db->moveToken($folkCard, "discard");

        $landBefore = $this->getMainAreaCards("land");

        $this->game->refillMainArea();

        $landAfter = $this->getMainAreaCards("land");
        $this->assertEquals(array_keys($landBefore), array_keys($landAfter), "Land cards should be untouched");
    }
}
