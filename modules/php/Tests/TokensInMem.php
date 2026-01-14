<?php

declare(strict_types=1);

namespace Bga\Games\wayfarers\Tests;

use Bga\Games\wayfarers\Db\DbTokens;
use Exception;

use function Bga\Games\wayfarers\array_get;
use function Bga\Games\wayfarers\toJson;

/**
 * Stub class for tokens overriding db function to be in memory
 */

class TokensInMem extends DbTokens {
    static function record($arr) {
        return [
            "key" => $arr[0],
            "location" => $arr[1],
            "state" => $arr[2],
        ];
    }

    function clear_cache() {
        //
    }
    function getTokenInfo($token_key) {
        self::checkKey($token_key);
        return array_get($this->keyindex, $token_key, null);
    }

    function DbCreateTokens($values) {
        foreach ($values as $row) {
            $rec = static::record($row);
            $key = $rec["key"];
            if (array_key_exists($key, $this->keyindex)) {
                throw new Exception("Dupicate key $key in " . toJson($row));
            }

            $this->keyindex[$key] = $rec;
        }
    }

    function setTokenState($token_key, $state) {
        self::checkState($state);
        self::checkKey($token_key);
        if (!array_key_exists($token_key, $this->keyindex)) {
            echo "$token_key does not exists";
        }
        $this->keyindex[$token_key]["state"] = $state;
        return $state;
    }

    function moveToken($token_key, $location, $state = 0) {
        self::checkLocation($location);
        self::checkState($state, true);
        self::checkKey($token_key);
        if (!array_key_exists($token_key, $this->keyindex)) {
            $this->keyindex[$token_key] = [
                "location" => "limbo",
                "state" => 0,
                "key" => $token_key,
            ];
        }
        $this->keyindex[$token_key]["location"] = $location;
        if ($state !== null) {
            $this->keyindex[$token_key]["state"] = $state;
        }
    }

    static function matchLike($pattern, $value) {
        if ($pattern === null) {
            return true;
        }
        if ($value === null) {
            return false;
        }

        //$pattern = preg_quote($pattern);
        $pattern = str_replace("%", ".*", $pattern);
        $ret = preg_match("/^" . $pattern . '$/i', $value);
        return $ret === 1;
    }

    function getTokensOfTypeInLocation($type, $location = null, $state = null, $order_by = null) {
        $result = [];

        if ($type !== null) {
            if (strpos($type, "%") === false) {
                $type .= "%";
            }
        }
        foreach ($this->keyindex as $key => $rec) {
            if (!$this->matchLike($type, $key)) {
                continue;
            }
            if (!$this->matchLike($location, array_get($rec, "location", null))) {
                continue;
            }
            if ($state !== null && $rec["state"] != $state) {
                continue;
            }
            $result[$key] = $rec;
        }

        return $result;
    }

    function countTokensInLocation($location, $state = null) {
        return count($this->getTokensOfTypeInLocation(null, $location, $state));
    }
}
