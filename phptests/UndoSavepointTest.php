<?php

declare(strict_types=1);

use Tests\GameUT;
use Bga\Games\wayfarers\Db\DbMultiUndo;
use Bga\Games\wayfarers\Game;
use PHPUnit\Framework\TestCase;

/**
 * Tests the deferred-snapshot behavior of customUndoSavepoint.
 *
 * Background: snapshots used to be taken inline from inside customUndoSavepoint, which
 * captured DB state mid-resolve (before the current op had been destroy()'d). Op_cardDraw
 * snapshotted between Phase 1 (draw) and destroy(), so the original cardDraw stayed at rank>0
 * in the snapshot — restoring it would re-run Phase 1.
 *
 * The fix: customUndoSavepoint only stores meta + raises the flag; the actual save runs in
 * doCustomUndoSavePoint at end of request via the sendNotifications hook.
 */
final class UndoSavepointTest extends TestCase {
    private GameUT $game;
    private RecordingDbMultiUndo $undoStub;

    protected function setUp(): void {
        $this->game = new GameUT();
        $this->game->init();
        $this->undoStub = new RecordingDbMultiUndo($this->game);
        $this->game->dbMultiUndo = $this->undoStub;
        // Reset both the duplicate flag and the meta — GameUT carries state across helpers.
        $this->game->setUndoSavepoint(false);
        $this->setMeta([]);
    }

    private function setMeta(array $meta): void {
        $ref = new ReflectionProperty(Game::class, "undoSavepointMeta");
        $ref->setAccessible(true);
        $ref->setValue($this->game, $meta);
    }

    private function getMeta(): array {
        $ref = new ReflectionProperty(Game::class, "undoSavepointMeta");
        $ref->setAccessible(true);
        return $ref->getValue($this->game);
    }

    public function testCustomUndoSavepoint_DoesNotSaveImmediately(): void {
        $this->game->customUndoSavepoint(PCOLOR_ID, 1, "test");

        $this->assertSame(0, $this->undoStub->saveCount, "Snapshot must be deferred, not taken inline");
        $this->assertTrue($this->game->isUndoSavepoint(), "Flag must be raised");
    }

    public function testCustomUndoSavepoint_StoresMeta(): void {
        $this->game->customUndoSavepoint(PCOLOR_ID, 1, "after-cardDraw");

        $meta = $this->getMeta();
        $this->assertSame(1, $meta["barrier"]);
        $this->assertSame("after-cardDraw", $meta["label"]);
        $this->assertSame(PCOLOR_ID, $meta["player_id"]);
    }

    public function testMultipleCustomUndoSavepoint_LastWins(): void {
        // Simulates Op_turn.auto → Op_cardDraw: only one snapshot at end of request, with the
        // latest meta. The intermediate one is discarded.
        $this->game->customUndoSavepoint(PCOLOR_ID, 1, "start-of-turn");
        $this->game->customUndoSavepoint(PCOLOR_ID, 1, "after-cardDraw");

        $this->assertSame(0, $this->undoStub->saveCount, "Still no save before the hook fires");
        $this->assertSame("after-cardDraw", $this->getMeta()["label"]);
    }

    public function testDoCustomUndoSavePoint_FiresSaveWithCorrectMeta(): void {
        $this->game->customUndoSavepoint(PCOLOR_ID, 1, "mylabel");
        $this->game->doCustomUndoSavePoint();

        $this->assertSame(1, $this->undoStub->saveCount);
        $call = $this->undoStub->lastSaveCall;
        $this->assertSame(PCOLOR_ID, $call["player_id"]);
        $this->assertSame(1, $call["meta"]["barrier"]);
        $this->assertSame("mylabel", $call["meta"]["label"]);
        $this->assertArrayNotHasKey("player_id", $call["meta"], "player_id is passed separately, not in meta");
    }

    public function testDoCustomUndoSavePoint_NoOpWhenNoMeta(): void {
        // Flag never raised, meta empty — nothing to save.
        $this->game->doCustomUndoSavePoint();

        $this->assertSame(0, $this->undoStub->saveCount);
    }

    public function testDoCustomUndoSavePoint_ClearsMetaAfterSave(): void {
        $this->game->customUndoSavepoint(PCOLOR_ID, 1, "once");
        $this->game->doCustomUndoSavePoint();
        $this->game->doCustomUndoSavePoint(); // second call: should not re-save the same snapshot

        $this->assertSame(1, $this->undoStub->saveCount);
        $this->assertSame([], $this->getMeta());
    }

    public function testCustomUndoSavepoint_SoloModeForcesPlayerIdToFirstPlayer(): void {
        $this->game->setPlayersNumber(1);
        $this->assertTrue($this->game->isSolo(), "sanity: 1-player table is solo");

        // Pass a deliberately wrong id (e.g. 0 or AUTOMA=1) — the meta should still resolve to
        // the human player so the snapshot is keyed to a real undoer.
        $this->game->customUndoSavepoint(0, 1, "solo");

        $expected = $this->game->getFirstPlayer();
        $this->assertSame($expected, $this->getMeta()["player_id"]);
    }
}

/**
 * Stub DbMultiUndo that records doSaveUndoSnapshot calls without touching any DB.
 */
class RecordingDbMultiUndo extends DbMultiUndo {
    public int $saveCount = 0;
    public ?array $lastSaveCall = null;

    public function doSaveUndoSnapshot(array $meta, int $player_id, bool $notify = false) {
        $this->saveCount++;
        $this->lastSaveCall = [
            "meta" => $meta,
            "player_id" => $player_id,
            "notify" => $notify,
        ];
    }
}
