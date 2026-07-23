<?php

declare(strict_types=1);

namespace Tests\Harness;

use Bga\GameFramework\GameResult\GameResult;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\StateType;
use Bga\GameFramework\Table;
use Bga\Games\wayfarers\StateConstants;
use ReflectionClass;
use ReflectionMethod;

use function Bga\Games\wayfarers\toJson;

/**
 * Generic harness driver for BGA games.
 * Handles scenario execution, state persistence, endpoint dispatch, and notifications.
 *
 * The game instance must provide:
 *   getGameName(): string — namespace name (e.g. "Euro")
 *   saveDbState(): array — serialize all custom DB tables
 *   loadDbState(array $db): void — restore custom DB tables
 *   getAllDatas(): array — game data for client
 */
class GameDriver {
    public Table&HarnessGameInterface $game;
    private string $stagingDir;
    public array $states;
    private int $verbose = 1;

    public function __construct(Table&HarnessGameInterface $game, string $stagingDir, int $currentPlayerId = 10) {
        $this->game = $game;
        $this->stagingDir = $stagingDir;
        $this->game->_setCurrentPlayerId($currentPlayerId);
        $this->game->gamestate->changeActivePlayer($currentPlayerId);
        $this->states = $this->buildStateNameMap();
    }

    /** @return array<int, GameState> map of state id => state instance */
    public function buildStateNameMap(): array {
        $gameName = $this->game->getGameName();
        $map = [];
        $statesDir = __DIR__ . "/../../modules/php/States";
        foreach (glob("$statesDir/*.php") as $file) {
            $className = "Bga\\Games\\{$gameName}\\States\\" . basename($file, ".php");
            if (!class_exists($className)) {
                continue;
            }
            $inst = new $className($this->game);
            $map[$inst->id] = $inst;
        }
        return $map;
    }

    // ── State persistence ────────────────────────────────────────────────────

    public function loadDbFromJson(string $dbPath): void {
        $db = self::loadJson($dbPath);
        if ($db === null) {
            die("db.json not found: $dbPath\n");
        }
        $this->debugLog("Loading $dbPath");
        $this->game->loadDbState($db);
        if (isset($db["gamestate"]["active_player"])) {
            $this->game->gamestate->changeActivePlayer($db["gamestate"]["active_player"]);
        }
        if (isset($db["gamestate"]["state_id"])) {
            $this->game->gamestate->jumpToState($db["gamestate"]["state_id"]);
        }
        if (isset($db["players"])) {
            $colors = array_column($db["players"], "player_color");
            $this->game->_setPlayerBasicInfoFromColors($colors);
        }
    }

    public function saveDbToJson(): void {
        $finalDb = $this->game->saveDbState() + [
            "gamestate" => [
                "state_id" => $this->game->gamestate->getCurrentMainStateId(),
                "active_player" => (int) $this->game->getActivePlayerId(),
            ],
            "players" => array_values($this->game->loadPlayersBasicInfos()),
        ];
        self::saveJson("$this->stagingDir/db.json", $finalDb);
    }

    public function saveGamedatas(): void {
        $gamedatas = $this->game_getAllDatas();
        self::saveJson("$this->stagingDir/gamedatas.json", $gamedatas);
        $this->debugLog("Wrote staging/gamedatas.json");
    }

    /**
     * The state THIS player's client is really in. During simultaneous play (MultiPlayerMaster) the
     * main state is shared but each player sits in their own private state, which is where their
     * args and their actions belong.
     */
    public function getCurrentStateIdFor(int $playerId): int {
        return $this->game->getPrivateStateId($playerId) ?: $this->game->gamestate->getCurrentMainStateId();
    }

    public function getGameState(int $stateId) {
        $activePlayer = (int) $this->game->getActivePlayerId();

        // The framework owns the terminal state and it has no class in States/; a finished game just
        // sits there, so report it rather than looking for a class that cannot exist.
        if ($stateId === StateConstants::STATE_END_GAME) {
            return ["id" => $stateId, "name" => "gameEnd", "active_player" => $activePlayer, "args" => []];
        }

        /** @var GameState */
        $stateInst = $this->states[$stateId] ?? null;
        if (!$stateInst) {
            throw new \RuntimeException("State not found: $stateId");
        }
        $stateName = $stateInst->name;

        $stateArgs = $this->runStateClass_getArgs($stateId, (int) $this->game->getCurrentPlayerId());

        return [
            "id" => $stateId,
            "name" => $stateName,
            "active_player" => $activePlayer,
            "args" => $stateArgs,
        ];
    }

    public function privateFilter(array &$state, int $currentPlayerId, bool $merge = false) {
        $private = $state["args"]["_private"] ?? null;
        if ($private === null) {
            return;
        }
        $forPlayer =
            $private[$currentPlayerId] ?? ($this->game->gamestate->isPlayerActive($currentPlayerId) ? $private["active"] ?? null : null);
        unset($state["args"]["_private"]);

        if ($forPlayer !== null) {
            if ($merge) {
                $state["args"] = array_merge($state["args"], $forPlayer);
            } else {
                $state["args"]["_private"] = $forPlayer;
            }
        }
    }
    public function game_getAllDatas() {
        /** @var int */
        $currentPlayerId = (int) $this->game->getCurrentPlayerId();
        $result = [];
        $stateId = $this->getCurrentStateIdFor($currentPlayerId);
        $state = $this->getGameState($stateId);
        $this->privateFilter($state, $currentPlayerId);
        $result["gamestate"] = $state;
        $result["gamestate"]["updateGameProgression"] = $stateId === 99 ? 100 : round((float) $this->game->getGameProgression());

        // Players info, aliases
        $players = $this->game->loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            foreach (["color", "name", "avatar", "zombie", "eliminated"] as $field) {
                $result["players"][$player_id][$field] = $player["player_$field"];
            }
            $result["players"][$player_id]["beginner"] = $player["player_beginner"] !== null;
        }

        // Player ordering
        $player_ids = array_keys($players);
        $pos = array_search($currentPlayerId, $player_ids);
        $result["playerorder"] =
            $pos !== false ? array_merge(array_slice($player_ids, $pos), array_slice($player_ids, 0, $pos)) : $player_ids;

        // assume this is blackbox, we don't know what game did the data
        $result += $this->game->getAllDatas();
        return $result;
    }

    public function saveNotifications(): void {
        self::saveJson("$this->stagingDir/notifications.json", $this->game->notify->_getNotifications());
        $this->debugLog("Wrote staging/notifications.json (" . count($this->game->notify->_getNotifications()) . " notifications)");
    }

    // ── Dispatch ─────────────────────────────────────────────────────────────

    public function dispatchEndpoint(string $endpoint, array $data): void {
        $states = $this->states;
        if (str_starts_with($endpoint, "action_")) {
            $stateId = $this->getCurrentStateIdFor((int) $this->game->getCurrentPlayerId());
            $stateInst = $states[$stateId];
            $ref = new ReflectionMethod($stateInst, $endpoint);
            $state = $ref->invokeArgs($stateInst, self::matchArgs($ref, $data));
            // A private state returns null while the other players are still answering: the shared
            // main state does not move until everyone is done.
            if ($state !== null) {
                $this->game->gamestate->jumpToState($this->getStateId($state));
            }
        } elseif (str_starts_with($endpoint, "debug_")) {
            if (!method_exists($this->game, $endpoint)) {
                die("Unknown debug endpoint: $endpoint\n");
            }
            $ref = new ReflectionMethod($this->game, $endpoint);
            $ref->invokeArgs($this->game, self::matchArgs($ref, $data));
        } else {
            die("Unknown endpoint: $endpoint\n");
        }
    }

    /** Max chained state entries per dispatch (GameDispatchForced -> GameDispatch -> ... ). */
    private const MAX_STATE_HOPS = 20;

    /** A state that runs itself on entry: chain into it. One that waits for a player ends the dispatch. */
    private function isAutomaticState(int $stateId): bool {
        $type = ($this->states[$stateId] ?? null)?->type;
        return $type === StateType::GAME || $type === StateType::MULTIPLE_ACTIVE_PLAYER;
    }

    public function runDispatchLoop(): void {
        $stateId = $this->game->gamestate->getCurrentMainStateId();
        // BGA re-enters every state it is sent to, so a state whose onEnteringState returns another
        // state chains on (GameDispatchForced -> GameDispatch -> the next player state).
        for ($hop = 0; $hop < self::MAX_STATE_HOPS; $hop++) {
            $nextState = $this->runStateClass_onEnteringState($stateId, (int) $this->game->getCurrentPlayerId());
            if (!$nextState) {
                break; // state did not change
            }
            $nextState = $this->getStateId($nextState);
            $this->game->gamestate->jumpToState($nextState);
            if ($nextState === $stateId || !$this->isAutomaticState($nextState)) {
                $stateId = $nextState;
                break;
            }
            $stateId = $nextState;
        }

        // BGA flushes the pending undo savepoint at the end of every request; without this the harness
        // would never write one, and undo could not be exercised at all.
        $this->game->doUndoSavePoint();
        $this->debugLog("  → Dispatched → state:  $stateId");
    }

    public function runStateClass_getArgs(int $stateId, ?int $privateStatePlayerId = null): mixed {
        return $this->runStateMethod($stateId, "getArgs", $privateStatePlayerId) ?? [];
    }

    public function runStateClass_onEnteringState(int $stateId, ?int $privateStatePlayerId = null): mixed {
        return $this->runStateMethod($stateId, "onEnteringState", $privateStatePlayerId);
    }

    private function runStateMethod(int $stateId, string $methodName, ?int $privateStatePlayerId): mixed {
        $state = $this->states[$stateId] ?? throw new \RuntimeException("State not found: $stateId");
        $reflection = new \ReflectionClass($state);

        if (!$reflection->hasMethod($methodName)) {
            return null;
        }

        $method = $reflection->getMethod($methodName);
        $args = $this->matchStateMethodArgs($method, $privateStatePlayerId);
        if ($this->verbose >= 2) {
            $stateName = $reflection->getShortName();
            echo "Calling $stateName::$methodName\n";
        }
        $res = $state->$methodName(...$args);
        if ($this->verbose >= 2) {
            echo "Return:" . toJson($res) . "\n";
        }
        return $res;
    }

    private function matchStateMethodArgs(\ReflectionMethod $method, ?int $privateStatePlayerId): array {
        $functionParameters = [];
        foreach ($method->getParameters() as $parameter) {
            $paramName = $parameter->getName();
            $paramType = $parameter->getType()->getName();
            if (in_array($paramName, ["arg", "args"]) && $paramType === "array") {
                $functionParameters[] = [];
            } elseif (in_array($paramName, ["playerId", "player_id", "currentPlayerId", "current_player_id"]) && $paramType === "int") {
                $functionParameters[] = $privateStatePlayerId;
            } elseif (in_array($paramName, ["activePlayerId", "active_player_id"]) && $paramType === "int") {
                $functionParameters[] = (int) $this->game->getActivePlayerId();
            } else {
                $functionName = $method->getName();
                $stateName = $method->getDeclaringClass()->getName();
                die("Unknown $paramType $paramName for $stateName::$functionName\n");
            }
        }
        return $functionParameters;
    }

    private static function matchArgs(ReflectionMethod $ref, array $data): array {
        $args = [];
        foreach ($ref->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $data)) {
                $args[] = $data[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                $endpoint = $ref->getName();
                die("Missing required param '$name' for $endpoint\n");
            }
        }
        return $args;
    }

    public function emitGameStateChange(): void {
        $currentPlayerId = $this->game->_getCurrentPlayerId();
        $stateId = $this->getCurrentStateIdFor((int) $currentPlayerId);
        $newGamestate = $this->getGameState($stateId);
        $this->privateFilter($newGamestate, $currentPlayerId);

        $this->game->notify->all("gameStateChange", "", [
            "id" => $newGamestate["id"] ?? 0,
            "name" => $newGamestate["name"] ?? "",
            "active_player" => (string) $newGamestate["active_player"],
            "type" => "activeplayer",
            "args" => $newGamestate["args"] ?? [],
        ]);
    }

    public function setVerbose(int $level): void {
        $this->verbose = $level;
    }

    private function debugLog($loc) {
        if ($this->verbose > 0) {
            echo "$loc\n";
        }
    }

    // ── Run ──────────────────────────────────────────────────────────────────

    public function runStep(string $endpoint, array $data): void {
        $this->dispatchEndpoint($endpoint, $data);
        $this->runDispatchLoop();
        $this->emitGameStateChange();
    }

    /**
     * What BGA does when a player quits: call the zombie hook of the state THAT player sits in, with
     * no current player set (the quit seat is not the one making the request).
     */
    public function runZombie(int $playerId): void {
        $stateId = $this->getCurrentStateIdFor($playerId);
        $state = $this->states[$stateId] ?? throw new \RuntimeException("State not found: $stateId");
        $next = $state->zombie($playerId);
        if ($next !== null) {
            $this->game->gamestate->jumpToState($this->getStateId($next));
        }
        $this->runDispatchLoop();
        $this->emitGameStateChange();
    }

    public function runSteps(array $steps): void {
        $this->debugLog("Loading " . count($steps) . " steps");
        foreach ($steps as $i => $step) {
            $endpoint = $step["endpoint"] ?? "";
            $this->debugLog("Step " . ($i + 1) . ": $endpoint");
            $this->runStep($endpoint, $step);

            if ($step["reload"] ?? false) {
                $gamedatas = $this->game_getAllDatas();
                self::saveJson("$this->stagingDir/gamedatas.json", $gamedatas);
                $this->debugLog("  → Wrote staging/gamedatas.json (reload after step)");
            }
        }
    }

    public function runDebug(string $debugFunction): void {
        $this->debugLog("Calling debug: $debugFunction");
        $this->runStep($debugFunction, []);
    }

    // ── CLI entry point ────────────────────────────────────────────────────

    /**
     * Run the harness from CLI arguments.
     * @param array $argv CLI arguments (from $argv global)
     * @param string $baseDir Directory containing plays/ subdirectory (for default scenario)
     * @param string $defaultStagingDir Default output directory
     */
    public static function main(Table&HarnessGameInterface $game, array $argv, string $baseDir, string $defaultStagingDir): void {
        $debugFunction = null;
        $scriptPath = null;
        $dbPath = null;
        $outputDir = null;

        $args = array_slice($argv, 1);
        for ($i = 0; $i < count($args); $i++) {
            switch ($args[$i]) {
                case "--debug":
                    $debugFunction = $args[++$i] ?? null;
                    if (!$debugFunction) {
                        die("Usage: --debug <function_name>\n");
                    }
                    break;
                case "--script":
                case "--scenario":
                    $scriptPath = $args[++$i] ?? null;
                    if (!$scriptPath) {
                        die("Usage: --script <path.json>\n");
                    }
                    break;
                case "--db":
                    $dbPath = $args[++$i] ?? null;
                    if (!$dbPath) {
                        die("Usage: --db <path.json>\n");
                    }
                    break;
                case "--output":
                    $outputDir = $args[++$i] ?? null;
                    if (!$outputDir) {
                        die("Usage: --output <dir>\n");
                    }
                    break;
                default:
                    if (!$scriptPath && !str_starts_with($args[$i], "-")) {
                        $scriptPath = $args[$i];
                    } else {
                        die("Unknown option: {$args[$i]}\n");
                    }
            }
        }

        if (!$scriptPath && !$debugFunction) {
            $scriptPath = "$baseDir/plays/setup.json";
        }

        $stagingDir = $outputDir ?? $defaultStagingDir;
        $currentPlayerId = 10;
        $steps = [];

        if ($scriptPath) {
            $script = self::loadJson($scriptPath);
            if ($script === null) {
                die("Script not found: $scriptPath\n");
            }
            echo "Loading script: $scriptPath\n";
            $currentPlayerId = (int) ($script["current_player_id"] ?? 10);
            $steps = $script["steps"] ?? [];
        }

        $driver = new self($game, $stagingDir, $currentPlayerId);

        if ($dbPath) {
            $driver->loadDbFromJson($dbPath);
        }

        $driver->runSteps($steps);

        if ($debugFunction) {
            $driver->runDebug($debugFunction);
        }

        $driver->saveGamedatas();
        $driver->saveNotifications();
        $driver->saveDbToJson();

        echo "Done.\n";
    }

    // ── Static helpers ───────────────────────────────────────────────────────

    public static function loadJson(string $path): mixed {
        if (!file_exists($path)) {
            return null;
        }
        $data = json_decode(file_get_contents($path), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            die("JSON parse error in $path: " . json_last_error_msg() . "\n");
        }
        return $data;
    }

    public static function saveJson(string $path, mixed $data): void {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function getStateId(mixed $targetClass): int {
        if ($targetClass instanceof GameState) {
            return $targetClass->id;
        } elseif ($targetClass instanceof GameResult) {
            // A scored game result (e.g. a solo win/loss) ends the game rather than naming a state.
            return StateConstants::STATE_END_GAME;
        } elseif (is_numeric($targetClass)) {
            return (int) $targetClass;
        } else {
            $ref = new ReflectionClass($targetClass);
            $inst = $ref->newInstance($this->game);
            return $inst->id;
        }
    }
}
