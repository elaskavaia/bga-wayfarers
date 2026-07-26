<?php

declare(strict_types=1);

namespace Tests\Harness;

use Bga\Games\wayfarers\Common\PGameTokens;
use Bga\Games\wayfarers\Game;
use Bga\Games\wayfarers\OpCommon\OpMachine;
use Tests\MachineInMem;
use Tests\TokensInMem;

/**
 * Game-specific wrapper for the wayfarers harness.
 * Swaps the DB-backed machine/tokens for in-memory stubs and implements the
 * harness contract (getGameName, saveDbState, loadDbState, getAllDatas).
 *
 * Game-specific debug_* setup scenarios go here as the game logic grows.
 */
class GameWrapper extends Game implements HarnessGameInterface {
    public array $xtable = [];
    public array $randQueue = [];

    /** Recorded Game::customUndoSavepoint calls, so tests can assert which ops establish an undo boundary. */
    public array $savepointCalls = [];

    function __construct() {
        parent::__construct();
        $this->machine = new OpMachine(new MachineInMem($this, $this->xtable));
        $tokens = new TokensInMem($this);
        $this->tokens = new PGameTokens($this, $tokens);
    }

    /** Deterministic RNG so scenarios are reproducible. Seed via $randQueue. */
    function bgaRand(int $min, int $max): int {
        return $this->randQueue ? (int) array_shift($this->randQueue) : $min;
    }

    function setPlayersNumber(int $num): void {
        $allColors = ["6cd0f6", "982fff", "ff0000", "ef58a2"];
        $this->_setPlayerBasicInfoFromColors(array_slice($allColors, 0, $num));
    }

    /** wayfarers has no simultaneous/private states; the in-memory store has no player table to query. */
    function getPrivateStateId($player_id): int {
        return 0;
    }

    /** No user-preference storage in the harness - every preference reads as its default. */
    function getUserPreference(int $player_id, int $code): int {
        return 0;
    }

    public function customUndoSavepoint(int $player_id, int $barrier = 0, string $label = "undo"): void {
        $this->savepointCalls[] = ["player_id" => $player_id, "barrier" => $barrier, "label" => $label];
        parent::customUndoSavepoint($player_id, $barrier, $label);
    }

    // -- Debug scenarios -----------------------------------------------------

    /** Default harness scenario: 2-player setup, paused on the first player's turn. */
    public function debug_setup(): void {
        $this->setupForTest(2);
    }

    /** Integration-test entry: N-player setup, jumping to the first state (mirrors setupNewGame). */
    public function setupForTest(int $numPlayers): void {
        $this->setPlayersNumber($numPlayers);
        $this->loadDbState([]);
        // setupGameTables returns a state class-string; the live framework resolves it, the stub does not.
        $next = $this->setupGameTables();
        $this->gamestate->jumpToState(is_string($next) ? (new $next($this))->id : $next);
    }

    // -- Harness contract ------------------------------------------------------

    public function getGameName(): string {
        return "wayfarers";
    }

    public function saveDbState(): array {
        return [
            "tokens" => $this->tokens->db->toJson(),
            "machine" => $this->machine->db->toJson(),
        ];
    }

    public function loadDbState(array $db, bool $reset = true): void {
        if ($reset) {
            $this->tokens->db->deleteAll();
            $this->machine->db->fromJson([]);
        }
        $this->tokens->db->fromJson($db["tokens"] ?? []);
        $this->machine->db->fromJson($db["machine"] ?? []);
    }
}
