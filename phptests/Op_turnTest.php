<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Material;
use Bga\Games\wayfarers\Operations\Op_turn;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_turnTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init();
        $this->game->tokens->createTokens();
    }

    /**
     * Choosing a die or a worker is binding: the turn action is spent and there is no way back.
     * An option whose placement leads nowhere must therefore be shown with the reason, not offered.
     */
    public function testDieWithNowhereToGoIsShownWithTheReasonInsteadOfOffered(): void {
        $color = PCOLOR;
        $dice = array_keys($this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_$color"));
        $dieKey = array_shift($dice);

        // Park every other die on the two cost-free slots: occupies them and empties the supply,
        // so the remaining die has no slot and no die to switch to.
        foreach ($dice as $i => $other) {
            $this->game->tokens->db->moveToken($other, $i === 0 ? "card_home_12_$color" : "card_home_13_$color", 2);
        }
        $this->game->tokens->db->setTokenState($dieKey, 2); // value 2 carries no caravan asset

        /** @var Op_turn */
        $op = $this->game->machine->instanciateOperation("turn", $color);
        $args = $op->getArgs();

        $this->assertNotContains($dieKey, $args["target"], "a die that cannot be placed is not a legal turn action");
        $this->assertEquals(Material::ERR_NOT_APPLICABLE, $args["info"][$dieKey]["q"] ?? null);
        $this->assertNotEmpty($args["info"][$dieKey]["err"] ?? "", "the player is told why");
    }

    /** A die that has somewhere to go stays a normal choice. */
    public function testPlaceableDieIsStillOffered(): void {
        $color = PCOLOR;
        $dice = array_keys($this->game->tokens->getTokensOfTypeInLocation("dice", "tableau_$color"));
        $dieKey = $dice[0];
        $this->game->tokens->db->setTokenState($dieKey, 1); // value 1 supplies the camel Capital City wants

        /** @var Op_turn */
        $op = $this->game->machine->instanciateOperation("turn", $color);
        $this->assertContains($dieKey, $op->getArgs()["target"]);
    }
}
