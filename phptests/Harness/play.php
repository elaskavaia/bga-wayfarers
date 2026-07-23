<?php

declare(strict_types=1);

use Tests\Harness\GameDriver;
use Tests\Harness\GameWrapper;

// Bootstrap: _autoload.php registers the autoloader; harness + stub classes load on demand.
require_once __DIR__ . "/../_autoload.php";

GameDriver::main(new GameWrapper(), $argv, __DIR__, __DIR__ . "/../../staging");
