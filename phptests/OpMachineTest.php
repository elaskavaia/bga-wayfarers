<?php

declare(strict_types=1);

use Bga\GameFramework\UserException;
use Tests\GameUT;
use PHPUnit\Framework\TestCase;

final class OpMachineTest extends TestCase {
    private GameUT $game;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init();
        $this->game->tokens->createTokens();
    }

    /** Studio error 690170: a silent cap let a self-requeueing op flood the 128k notification packet */
    public function testDispatchThatDoesNotSettleWithinTheCapThrowsNamingTheTopOp(): void {
        for ($i = 0; $i < 3; $i++) {
            $this->game->machine->queue("nop", PCOLOR);
        }
        $this->expectException(UserException::class);
        $this->expectExceptionMessage("top: nop");
        $this->game->machine->dispatchAll(2);
    }
}
