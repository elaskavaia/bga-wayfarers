<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * implementation : © Alena Laskavaia <laskava@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 */

declare(strict_types=1);

namespace Bga\Games\wayfarers\Operations;

use Bga\Games\wayfarers\Material;
use Bga\Games\wayfarers\OpCommon\Operation;

use function Bga\Games\wayfarers\getPart;

class Op_infMove extends Operation {
    const GUILDS = ["guild_black", "guild_yellow", "guild_blue"];

    function getPossibleMoves() {
        $owner = $this->getOwner();
        $res = [];

        foreach (self::GUILDS as $from) {
            $tokens = $this->game->tokens->getTokensOfTypeInLocation("influence_{$owner}", $from);
            if (empty($tokens)) {
                continue;
            }
            $tokenId = array_key_first($tokens);
            foreach (self::GUILDS as $to) {
                if ($to === $from) {
                    continue;
                }
                $fromColor = getPart($from, 1);
                $toColor = getPart($to, 1);
                $res["{$from}_{$to}"] = [
                    "q" => Material::RET_OK,
                    "from" => $from,
                    "to" => $to,
                    "name" => "[wicon_inf_$fromColor] ⤇ [wicon_inf_$toColor]",
                    "token_id" => $tokenId,
                ];
            }
        }

        if (empty($res)) {
            return ["q" => Material::ERR_NONE_LEFT];
        }

        return $res;
    }

    function canSkip() {
        return true;
    }

    function resolve(): void {
        $selected = $this->getCheckedArg();

        $moves = $this->getArgs()["info"];
        $move = $moves[$selected]; // this cannot fail - getCheckedArg already checked
        $this->dbSetTokenLocation(
            $move["token_id"],
            $move["to"],
            0,
            clienttranslate('${player_name} moves ${token_name} from ${place_from_name} to ${place_name}'),
            ["place_from_name" => $this->game->getTokenName($move["from"])]
        );
    }

    function getPrompt() {
        return clienttranslate("Select where to move Influence");
    }

}
