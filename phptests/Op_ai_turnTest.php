<?php

declare(strict_types=1);

use Bga\Games\wayfarers\Operations\Op_ai_turn;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class Op_ai_turnTest extends TestCase {
    private GameUT $game;
    private const AI_COLOR = "ffffff";

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init(1); // solo, so the automa is a known player
        $this->game->tokens->createTokens();
        $this->game->_setCurrentPlayerId(PCOLOR_ID);
    }

    /** Leave a single scheme card in the draw pile so the reveal is deterministic. */
    private function stackDeck(int $num): void {
        foreach (array_keys($this->game->tokens->getTokensOfTypeInLocation("card_scheme", "deck_scheme")) as $key) {
            if ($key !== "card_scheme_$num") {
                $this->game->tokens->db->moveToken($key, "discard_scheme");
            }
        }
    }

    private function addInfluence(string $guild, int $count): void {
        for ($n = 1; $n <= $count; $n++) {
            $this->game->tokens->db->moveToken("influence_" . self::AI_COLOR . "_$n", $guild);
        }
    }

    /**
     * BGA #243940 carve-out. RULES.md, Solo Play > Special Rules: "The AI ignores all costs
     * (other than those on their Scheme Cards)". The carve-out lives in Op_pay::auto (Provisions
     * and Silver); Scheme Card costs are Influence (Op_n_infBase) and stay payable, so the action
     * must be queued with its cost expression intact.
     */
    public function testSchemeCardCostIsKept(): void {
        $this->stackDeck(4); // 2n_infBlue:ai_cardWater
        $this->addInfluence("guild_blue", 2);
        $this->game->tokens->db->moveToken("card_water_1", "mainarea", 1);

        /** @var Op_ai_turn */
        $op = $this->game->machine->instantiateOperation("ai_turn", self::AI_COLOR);
        $op->aiRevealScheme(self::AI_COLOR);

        $this->assertContains("2n_infBlue:ai_cardWater", $this->game->queuedTypes(self::AI_COLOR));
    }

    /** An unaffordable Scheme Card action is void, so the AI falls back to the second action. */
    public function testUnaffordableSchemeCardFallsBackToSecondAction(): void {
        $this->stackDeck(4);
        $this->game->tokens->db->moveToken("card_water_1", "mainarea", 1);

        /** @var Op_ai_turn */
        $op = $this->game->machine->instantiateOperation("ai_turn", self::AI_COLOR);
        $op->aiRevealScheme(self::AI_COLOR);

        $types = $this->game->queuedTypes(self::AI_COLOR);
        $this->assertContains("infBlue,ai_upgAny", $types);
        $this->assertNotContains("2n_infBlue:ai_cardWater", $types);
    }
}
