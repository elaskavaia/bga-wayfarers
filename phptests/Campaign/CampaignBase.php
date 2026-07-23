<?php

declare(strict_types=1);

namespace Tests\Campaign;

use PHPUnit\Framework\TestCase;
use Tests\Harness\GameDriver;
use Tests\Harness\GameWrapper;

/**
 * Base for campaign integration tests: drives the real game through GameDriver
 * in-process (real op-machine, state classes and notifications - no mocks, only
 * the in-memory token/machine stores). Subclasses call setupGame() then script
 * player actions via respond()/skip().
 */
abstract class CampaignBase extends TestCase {
    protected GameWrapper $game;
    protected GameDriver $driver;
    protected string $outputDir;

    protected function setUp(): void {
        $this->outputDir = sys_get_temp_dir() . "/campaign_test_" . getmypid();
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0777, true);
        }
        $this->game = new GameWrapper();
        $this->driver = new GameDriver($this->game, $this->outputDir);
        $this->driver->setVerbose(0);
    }

    protected function tearDown(): void {
        $this->assertNotificationPlaceholdersResolved();
        array_map("unlink", glob("$this->outputDir/*"));
        if (is_dir($this->outputDir)) {
            @rmdir($this->outputDir);
        }
    }

    /** Set up a game with $numPlayers and run the first dispatch so an interactive op is waiting. */
    protected function setupGame(int $numPlayers = 2): void {
        $this->game->setupForTest($numPlayers);
        $this->syncCurrentPlayerToActive();
        $this->driver->runDispatchLoop();
        $this->driver->emitGameStateChange();
        $this->syncCurrentPlayerToActive();
    }

    // -- Driving actions --------------------------------------------------------

    /** Send a player response (action_resolve with target). */
    protected function respond(mixed $target): void {
        $this->syncCurrentPlayerToActive();
        $this->driver->runStep("action_resolve", ["data" => ["target" => $target]]);
    }

    /** Send action_skip (decline an optional op). */
    protected function skip(): void {
        $this->syncCurrentPlayerToActive();
        $this->driver->runStep("action_skip", []);
    }

    /** The harness keeps a sticky currentPlayerId; point it at the seat the machine is waiting on. */
    protected function syncCurrentPlayerToActive(): void {
        $this->game->_setCurrentPlayerId((int) $this->game->getMostlyActivePlayerId());
    }

    // -- State / op inspection ----------------------------------------------------

    /** Current game state (id, name, active_player, args) as the active player's client sees it. */
    protected function getStateArgs(): array {
        $this->syncCurrentPlayerToActive();
        $playerId = (int) $this->game->getCurrentPlayerId();
        $state = $this->driver->getGameState($this->driver->getCurrentStateIdFor($playerId));
        $this->driver->privateFilter($state, $playerId, true);
        return $state;
    }

    /** Current operation args (type, target, info, prompt). */
    protected function getOpArgs(): array {
        return $this->getStateArgs()["args"] ?? [];
    }

    /** Type of the operation currently awaiting input. */
    protected function getOpType(): string {
        return $this->getOpArgs()["type"] ?? "";
    }

    /** Color of the player whose turn it currently is. */
    protected function getActiveColor(): string {
        return $this->game->custom_getPlayerColorById((int) $this->game->getMostlyActivePlayerId());
    }

    protected function tokenLocation(string $tokenId): string {
        return $this->game->tokens->getTokenLocation($tokenId);
    }

    protected function countTokens(string $type, string $location): int {
        return count($this->game->tokens->getTokensOfTypeInLocation($type, $location));
    }

    /** Seed upcoming bgaRand results. */
    protected function seedRand(array $values): void {
        $this->game->randQueue = array_merge($this->game->randQueue, $values);
    }

    // -- Assertions ---------------------------------------------------------------

    protected function assertOpType(string $type, string $message = ""): void {
        $this->assertEquals($type, $this->getOpType(), $message ?: "expected operation '$type'");
    }

    protected function assertValidTarget(string $target, string $message = ""): void {
        $this->assertContains($target, $this->getOpArgs()["target"] ?? [], $message ?: "$target should be a valid target");
    }

    protected function assertNotValidTarget(string $target, string $message = ""): void {
        $this->assertNotContains($target, $this->getOpArgs()["target"] ?? [], $message ?: "$target should not be a valid target");
    }

    /**
     * Every emitted notification's ${name} placeholders must have a matching arg key -
     * catches template/arg mismatches that the client would render as a raw ${name}.
     */
    private function assertNotificationPlaceholdersResolved(): void {
        if (!isset($this->game) || !isset($this->game->notify)) {
            return;
        }
        foreach ($this->game->notify->_getNotifications() as $idx => $notif) {
            $log = $notif["log"] ?? "";
            if (!is_string($log) || $log === "" || !preg_match_all('/\$\{([a-zA-Z0-9_]+)\}/', $log, $m)) {
                continue;
            }
            $args = $notif["args"] ?? [];
            foreach ($m[1] as $name) {
                $this->assertArrayHasKey(
                    $name,
                    $args,
                    "Notification #$idx ({$notif["type"]}) template \"$log\" references \${{$name}} but no matching arg",
                );
            }
        }
    }
}
