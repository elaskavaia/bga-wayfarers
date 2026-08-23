<?php

declare(strict_types=1);

namespace Tests\Campaign;

use Bga\Games\wayfarers\StateConstants;
use PHPUnit\Framework\Attributes\Group;
use Tests\Harness\GameDriver;
use Tests\Harness\GameWrapper;
use Throwable;

/**
 * Drives every seat of a seeded game as a zombie. A zombie picks a random valid target
 * (Operation::whatever), so across seeds it walks into states no hand-written scenario would script.
 *
 * On demand only - `npm run tests:fuzz`. It is minutes of work per hundred steps, and unlike the rest of
 * the suite it is a search, not a check, so it does not belong in `npm run tests` or in predeploy.
 *
 * A BLOCKED operation reaches PlayerTurn with no target and no way to skip; that is the bug class. The
 * PlayerTurn::zombie backstop no longer throws on one - it drops the operation with a notification -
 * so the test watches for that notification as well as for a throw. The set of blocking operation
 * types is asserted EXACTLY, so a new site fails the test and fixing a listed site fails it too -
 * which is what forces KNOWN_BLOCKED to shrink rather than rot.
 *
 * Running out of steps is reported but not a failure. A zombie game does make real progress - at 1200
 * steps both journal markers are around 20 with a healthy spread of operations - it just takes longer
 * than is worth spending here. Raise MAX_STEPS by hand if you want to watch one finish.
 */
#[Group("fuzz")]
class Campaign_ZombieFuzzTest extends CampaignBase {
    /** Budget, not coverage. Raise both by hand when hunting; keep the committed run near half a minute. */
    private const SEEDS = 4;
    private const MAX_STEPS = 120;

    /**
     * Operation types still known to block. Empty is the goal and the current state - keep it that way.
     * If a run adds one, that is a real bug: fix it rather than listing it, and only record a type here
     * with the call site that offers the choice, as a deliberate and temporary admission.
     */
    private const KNOWN_BLOCKED = [];

    public function testZombiesNeverReachAnOperationTheyCannotAnswer(): void {
        $blocked = [];
        $unfinished = [];
        for ($seed = 1; $seed <= self::SEEDS; $seed++) {
            $outcome = $this->playOutAsZombies($seed);
            if ($outcome === null) {
                continue;
            }
            if ($outcome["type"] === "") {
                $unfinished[] = $outcome;
                continue;
            }
            $blocked[] = $outcome;
        }

        $types = array_values(array_unique(array_column($blocked, "type")));
        sort($types);
        $report = $this->describe($blocked) . "\nout of steps: " . count($unfinished) . " of " . self::SEEDS . " seeds";
        $this->assertEquals(self::KNOWN_BLOCKED, $types, $report);
    }

    /**
     * Returns null when the game ends, an entry with an empty type when it runs out of steps, or the
     * operation the zombie could not answer.
     */
    private function playOutAsZombies(int $seed): ?array {
        mt_srand($seed);
        $this->game = new GameWrapper();
        $this->driver = new GameDriver($this->game, $this->outputDir);
        $this->driver->setVerbose(0);
        $this->setupGame(2);

        $over = [StateConstants::STATE_END_SCORE, StateConstants::STATE_END_GAME, StateConstants::STATE_MACHINE_HALTED];
        for ($step = 0; $step < self::MAX_STEPS; $step++) {
            if (in_array($this->game->gamestate->getCurrentMainStateId(), $over, true)) {
                return null;
            }
            $playerId = (int) $this->game->getMostlyActivePlayerId();
            $this->game->_setCurrentPlayerId($playerId);
            $op = $this->game->machine->createTopOperationFromDb($playerId);
            $notifyCount = count($this->game->notify->_getNotifications());
            try {
                $this->driver->runZombie($playerId);
            } catch (Throwable $e) {
                return [
                    "seed" => $seed,
                    "step" => $step,
                    "type" => $op?->getType() ?? "(none)",
                    "expr" => $op?->getTypeFullExpr() ?? "(none)",
                    "reason" => $op?->getReason() ?? "",
                    "error" => $e->getMessage()
                ];
            }
            $dropped = $this->droppedNotification($notifyCount);
            if ($dropped !== null) {
                return [
                    "seed" => $seed,
                    "step" => $step,
                    "type" => $op?->getType() ?? "(none)",
                    "expr" => $op?->getTypeFullExpr() ?? "(none)",
                    "reason" => $op?->getReason() ?? "",
                    "error" => $dropped
                ];
            }
        }
        return ["seed" => $seed, "step" => self::MAX_STEPS, "type" => "", "expr" => "", "reason" => "", "error" => ""];
    }

    /** The PlayerTurn::zombie backstop announces a dropped stuck operation instead of throwing. */
    private function droppedNotification(int $skip): ?string {
        foreach (array_slice($this->game->notify->_getNotifications(), $skip) as $notif) {
            $log = $notif["log"] ?? "";
            if (is_string($log) && str_contains($log, "(as zombie) cannot perform")) {
                return $log;
            }
        }
        return null;
    }

    private function describe(array $blocked): string {
        return implode(
            "\n",
            array_map(fn($b) => "  seed {$b["seed"]} step {$b["step"]}: {$b["expr"]} reason={$b["reason"]} - {$b["error"]}", $blocked)
        );
    }
}
