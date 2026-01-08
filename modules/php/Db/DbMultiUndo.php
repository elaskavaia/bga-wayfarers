<?php

declare(strict_types=1);

namespace Bga\Games\wayfarers\Db;

use Bga\GameFramework\UserException;
use Bga\Games\wayfarers\Game;
use feException;

use function Bga\Games\wayfarers\array_get;

/*
 * This is a generic class to manage multi-step undo.
 *
 *
 *
CREATE TABLE IF NOT EXISTS `multiundo` (
  `move_id` int(10) NOT NULL,
  `player_id` int(10) NOT NULL,
  `data` mediumtext NOT NULL,
  `meta` mediumtext NOT NULL,
  PRIMARY KEY (`move_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

 *
 *
 */

class DbMultiUndo {
    var $table;
    public Game $game; // game ref

    function __construct(Game $game, private $customRestoreHook = null) {
        $this->table = "multiundo";
        $this->game = $game;
    }

    function getSelectQuery() {
        $sql = "SELECT *";
        $sql .= " FROM " . $this->table;
        return $sql;
    }

    function isXUndo() {
        return true; // multi-step undo
    }

    function setMoveSnapshot(int $move_id, int $player_id, array $data, array $meta = []) {
        $meta = $meta + ["version" => 1];
        $json_data = $this->game->escapeStringForDB(fixedJsonEncode($data));
        $json_meta = $this->game->escapeStringForDB(fixedJsonEncode($meta));

        $table = $this->table;
        $hasmove = $this->game->getUniqueValueFromDB("SELECT `move_id` FROM $table WHERE `move_id`='$move_id' AND player_id='$player_id'");
        if ($hasmove) {
            $sql = "UPDATE " . $this->table;
            $sql .= " SET data='$json_data', meta='$json_meta'";
            $sql .= " WHERE move_id='$move_id' AND player_id='$player_id'";
            $this->game->DbQuery($sql);
        } else {
            $sql = "INSERT INTO " . $this->table . " (move_id,player_id,data,meta)";
            $sql .= " VALUES ('$move_id','$player_id', '$json_data', '$json_meta')";
            $this->game->DbQuery($sql);
        }
    }

    function doSaveUndoSnapshot(array $meta, int $player_id, bool $notify = false) {
        $move_id = $this->getNextMoveId() - 1;

        $barrier = array_get($meta, "barrier", 0);

        $this->game->setGameStateValue("undo_moves_player", $player_id);
        $data_all = $this->getCurrentTablesAsObject();

        $this->clearSnapshotsAfter($move_id, $player_id);
        if ($barrier) {
            $this->clearSnapshotsBefore($move_id, $player_id);
        }
        if ($player_id == 0) {
            return;
        }
        $this->setMoveSnapshot($move_id, $player_id, $data_all, $meta);
        if ($notify) {
            $this->notifyUndoMove($move_id, $player_id);
        }
    }

    function notifyUndoMove(int $move_id, int $player_id) {
        $this->game->systemAssert("Missing player_id in notifyUndoMove", $player_id);
        $meta = $this->getMetaForMove($move_id, $player_id, true);
        $this->notifyUndoMoveMeta($meta);
    }
    function notifyUndoMoveMeta($meta) {
        $barrier = array_get($meta, "barrier", 0);
        $move_id = (int) array_get($meta, "move_id", 0);
        $player_id = (int) array_get($meta, "player_id", 0);
        $this->game->systemAssert("Missing player_id in notifyUndoMoveMeta", $player_id);
        $meta["barrier"] = $barrier;
        unset($meta["player_id"]);

        $this->game->notifyWithName(
            "undoMove",
            "",
            //'${player_name} undoinfo ${label} ${undo_button} ${barrier}',
            $meta + ["undo_button" => $move_id],
            $player_id
        );
    }

    function clearSnapshotsAfter(int $move_id, int $player_id) {
        $undotable = $this->table;
        if ($player_id == 0) {
            $this->game->DbQuery("DELETE FROM $undotable WHERE `move_id` >= $move_id");
        } else {
            $this->game->DbQuery("DELETE FROM $undotable WHERE `move_id` >= $move_id AND `player_id` = $player_id");
        }
    }
    function clearSnapshotsBefore(int $move_id, int $player_id) {
        $undotable = $this->table;
        if ($player_id == 0) {
            $this->game->DbQuery("DELETE FROM $undotable WHERE `move_id` < $move_id");
        } else {
            $this->game->DbQuery("DELETE FROM $undotable WHERE `move_id` < $move_id AND `player_id` = $player_id");
        }
    }

    function deleteGamelogs(int $move_id) {
        $packet_id = $this->game->getUniqueValueFromDB("SELECT MIN(`gamelog_packet_id`) FROM gamelog WHERE `gamelog_move_id` >= $move_id");
        if (!$packet_id) {
            return;
        }

        $cancelledIds = $this->game->getObjectListFromDB(
            "SELECT `gamelog_notification` FROM gamelog  WHERE `gamelog_packet_id` > $packet_id AND `gamelog_private` != 1"
        );

        $this->game->DbQuery("DELETE FROM gamelog WHERE `gamelog_packet_id` > $packet_id AND `gamelog_private` != 1");
        return self::extractNotifIds($cancelledIds);
    }

    function getLatestSavedMoveId(int $before, int $player_id) {
        $undotable = $this->table;

        return $this->game->getUniqueValueFromDB(
            "SELECT MAX(`move_id`) FROM $undotable WHERE `move_id` < $before AND `player_id` = $player_id"
        );
    }

    function getAvailableUndoMoves() {
        $moves = [];
        if (!$this->isXUndo()) {
            $res = [
                "player_id" => $this->game->getGameStateValue("undo_moves_player", 0),
                "move_id" => $this->game->getGameStateValue("undo_moves_stored", 0),
                "label" => "",
            ];
            $moves[$res["move_id"]] = $res;
            return $moves;
        }
        $undotable = $this->table;
        $all = $this->game->getObjectListFromDB("SELECT `move_id`,`player_id`,`meta` FROM $undotable");

        foreach ($all as $row) {
            $value = $row["meta"];
            $res = json_decode($value, true);
            $res["player_id"] = $row["player_id"];
            $res["move_id"] = $row["move_id"];
            $moves[$row["move_id"]] = $res;
        }
        return $moves;
    }

    function getNextMoveId() {
        return $this->game->getNextMoveId();
    }
    function errorCannotUndo(int $move_id = 0) {
        if ($move_id == 0) {
            $message = $this->game->_("Nothing to undo");
        } else {
            $message = sprintf($this->game->_("Nothing to undo for move %s"), $move_id);
        }
        throw new \BgaUserException($message);
    }

    /** Replace custom undo data of move_id into bga system undo tables */
    function doReplaceUndoSnapshot(int $move_id, int $player_id) {
        $current = $this->getNextMoveId();
        //$this->warn("restoring to move $move_id ($current)|");
        if ($move_id >= $current - 1) {
            $this->errorCannotUndo($move_id);
        }
        $tables = $this->game->getObjectListFromDB("SHOW TABLES", true);
        $prefix = "zz_savepoint_";
        $saved = $this->getMoveSnapshotDataJson($move_id, $player_id);
        $meta = $this->getMetaForMove($move_id, $player_id, true);

        if (!$saved) {
            $this->errorCannotUndo($move_id);
        }
        if (count($saved) == 0) {
            $this->errorCannotUndo($move_id);
        }

        foreach ($tables as $table) {
            $ret = false; // custom override method
            $saved_data = $saved[$table] ?? [];
            $copy = "{$prefix}{$table}";
            if ($this->customRestoreHook) {
                $method = $this->customRestoreHook;
                $ret = $this->game->$method($table, $saved_data, $meta);
            }
            if (!$ret && $this->needsRestoring($table)) {
                $this->game->DbQuery("DELETE FROM $copy");
                $this->dbInsertValues($copy, $saved_data);
                //$this->warn("restore $table");
            } elseif ($ret || $this->needsOverrdeFromCurrent($table)) {
                // special case - override some tables with existing (including self)
                $fields = $this->game->dbGetFieldListAsString($table);
                $this->game->DbQuery("DELETE FROM $copy");
                $this->game->DbQuery("INSERT INTO $copy ($fields) SELECT $fields FROM $table");
                //$this->warn("copy over $table");
            }
        }
    }

    function needsSaving(string $table) {
        if ($table == "token" || $table == "machine") {
            return true;
        }
        return false;
    }

    function needsRestoring(string $table) {
        return false;
    }

    /**
     * The tables that need copying are in "undo" list but the should not be, we preserve current copy instead
     */
    function needsOverrdeFromCurrent(string $table) {
        // if (
        //     $table == $this->table || // multiundo
        //     $table == "user_preferences" ||
        //     $table == "gamelog" ||
        //     $table == "player" ||
        //     $table == "stats" ||
        //     $table == "bga_globals"
        // ) {
        //     return true;
        // }
        return false;
    }

    function getMoveSnapshotDataJson(int $move_id, int $player_id) {
        $row = $this->getMoveSnapshot($move_id, $player_id);
        if ($row == null) {
            return null;
        }
        $value = $row["data"];
        return json_decode($value, true);
    }

    function getMetaForMove(int $move_id, int $player_id, $extra = false) {
        $row = $this->getMoveSnapshot($move_id, $player_id);
        if ($row == null) {
            return null;
        }
        $value = $row["meta"];
        $res = json_decode($value, true);
        if ($extra) {
            $res["player_id"] = $player_id;
            $res["move_id"] = $move_id;
        }
        return $res;
    }

    function getCurrentTablesAsObject() {
        $tables = $this->game->getObjectListFromDB("SHOW TABLES", true);
        $data_all = [];

        foreach ($tables as $table) {
            if ($this->needsSaving($table)) {
                $datatable = $this->game->getObjectListFromDB("SELECT * from $table");
                $data_all[$table] = $datatable;
            }
        }
        return $data_all;
    }

    function getMoveSnapshot(int $move_id, int $player_id) {
        $sql = $this->getSelectQuery();
        $sql .= " WHERE move_id='$move_id' AND player_id='$player_id'";
        $dbres = $this->game->getObjectListFromDB($sql);
        return reset($dbres);
    }

    function rewriteHistory(int $from_move_id, int $to_move_id, int $player_id) {
        $undotable = $this->table;
        $meta = $this->getMetaForMove($from_move_id, $player_id);
        $this->game->systemAssert("ERR:DbMultiUndo:01", $meta && is_array($meta));
        $meta["last_move"] = $to_move_id;
        $json_meta = $this->game->escapeStringForDB(fixedJsonEncode($meta, JSON_NUMERIC_CHECK));
        $this->game->DbQuery("UPDATE $undotable SET `meta` = '$json_meta' WHERE `move_id` = $from_move_id");
    }

    function undoRestorePoint(int $move_id = 0, bool $partial = false) {
        $player_id = (int) $this->game->getCurrentPlayerId(true);
        if (!$this->game->gamestate->isPlayerActive($player_id)) {
            $this->game->userAssert(clienttranslate("Only active player can Undo"));
        }
        $next = $this->game->getNextMoveId();

        if ($move_id === 0) {
            $move_id = (int) $this->getLatestSavedMoveId($next, $player_id);
        }
        if (!$move_id) {
            $this->errorCannotUndo();
        }

        $meta = $this->getMetaForMove($move_id, $player_id, true);

        $save_player_id = array_get($meta, "player_id", 0);
        if ($player_id != $save_player_id && $save_player_id != 0) {
            $this->game->userAssert(clienttranslate("Stored Undo data belongs to other player"));
        }

        //$this->game->not_a_move_notification = false;
        $this->doReplaceUndoSnapshot($move_id, $player_id);
        if ($partial === false) {
            $this->game->bgaUndoRestorePoint();
        }
        $this->game->setUndoSavepoint(false); // unset it because it was set by bga undo
        $this->clearSnapshotsAfter($move_id + 1, $player_id);
        $cancelledIds = $this->deleteGamelogs($move_id);
        $this->rewriteHistory($move_id, $next, $player_id);
        $this->game->setGameStateValue("next_move_id", $next);

        $this->game->notifyWithName(
            "undoRestore",
            clienttranslate('${player_name} undoes moves ${last_move} - ${undo_move}'),
            [
                "last_move" => $next - 1,
                "undo_move" => $move_id,
                "cancelledIds" => $cancelledIds,
            ],
            $player_id
        );

        $this->notifyUndoMove($move_id, $player_id);
    }

    protected static function extractNotifIds($notifications) {
        $notificationUIds = [];
        foreach ($notifications as $packet) {
            $data = \json_decode($packet["gamelog_notification"], true);
            foreach ($data as $notification) {
                array_push($notificationUIds, $notification["uid"]);
            }
        }
        return $notificationUIds;
    }

    function dbInsertValues($table, $values) {
        if (count($values) == 0) {
            return;
        }
        $fields_list = $this->game->dbGetFieldList($table);
        $seqvalues = [];
        foreach ($values as $row) {
            $quoted = [];
            foreach ($fields_list as $field) {
                $value = array_get($row, $field, null);
                if ($value === null) {
                    $quoted[] = "NULL";
                } elseif (is_numeric($value)) {
                    $quoted[] = "$value";
                } else {
                    $value = $this->game->escapeStringForDB($value);
                    $quoted[] = "'$value'";
                }
            }
            $seqvalues[] = "( " . implode(",", $quoted) . " )";
        }
        $fields = "`" . implode("`,`", $fields_list) . "`";
        $sql = "INSERT INTO $table ($fields)";
        $sql .= " VALUES " . implode(",", $seqvalues);
        $this->game->DbQuery($sql);
    }
}

function convertToUtf8($d) {
    if (is_array($d)) {
        foreach ($d as $k => $v) {
            $d[$k] = convertToUtf8($v);
            // if ($d[$k] != $v) {
            //     throw new BgaUserException("trip point $v $k ($d[$k])");
            // }
        }
    } elseif (is_object($d)) {
        foreach ($d as $k => $v) {
            $d->$k = convertToUtf8($v);
        }
    } else {
        return utf8_encode($d);
    }

    return $d;
}

function fixedJsonEncode($data) {
    $result = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
    $ret = json_last_error();
    if ($result === false) {
        $data = convertToUtf8($data);
        $result = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
    }
    if ($result === false) {
        throw new \feException("json error $ret");
    }
    return $result;
}
