<?php

declare(strict_types=1);

use Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_cardBaseTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init();
        $this->game->tokens->createTokens();
    }

    /**
     * Test hasPigeonLeftover — folk card tucked under placed card provides pigeon via da field.
     * Pigeon from folk card should count as available leftover.
     */
    public function testHasPigeonLeftover_TuckedFolkProvidesPigeon(): void {
        $color = PCOLOR;

        // card_folk_137 (Messenger) has da=pigeon. Tuck it under card_home_3 (state 1)
        $this->game->tokens->db->moveToken("card_folk_137", "tableau_$color", 1);

        // Die value 2 on card_home_3 (d=ship, state=1). No pigeon tile in caravan.
        $die = "dice_{$color}_1";
        $this->game->tokens->db->moveToken($die, "card_home_3_$color", 2);

        /** @var \Bga\Games\wayfarers\Operations\Op_cardBase */
        $op = $this->game->machine->instanciateOperation("cardWater", $color, [
            "die" => $die,
            "reason" => "card_home_3_$color",
        ]);

        $result = $op->hasPigeonLeftover();
        $this->assertTrue($result, "Tucked folk with pigeon should provide leftover pigeon");
    }

    /**
     * Test hasPigeonLeftover — no folk card tucked, no pigeon tile. Should be false.
     */
    public function testHasPigeonLeftover_NoFolkNoPigeonTile(): void {
        $color = PCOLOR;

        $die = "dice_{$color}_1";
        $this->game->tokens->db->moveToken($die, "card_home_3_$color", 2);

        /** @var \Bga\Games\wayfarers\Operations\Op_cardBase */
        $op = $this->game->machine->instanciateOperation("cardWater", $color, [
            "die" => $die,
            "reason" => "card_home_3_$color",
        ]);

        $result = $op->hasPigeonLeftover();
        $this->assertFalse($result, "No pigeon source — should not have leftover pigeon");
    }
}
