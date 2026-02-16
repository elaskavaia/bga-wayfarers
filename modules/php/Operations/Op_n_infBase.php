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
use Bga\Games\wayfarers\OpCommon\CountableOperation;

use function Bga\Games\wayfarers\getPart;

abstract class Op_n_infBase extends CountableOperation {
    abstract function getGuild(): string;

    /**
     * Get player's influence tokens on the guild
     */
    function getInfluenceOnGuild(): array {
        $owner = $this->getOwner();
        return $this->game->tokens->getTokensOfTypeInLocation("influence_{$owner}", $this->getGuild());
    }

    function getLimitCount() {
        return count($this->getInfluenceOnGuild());
    }

    function getPossibleMoves() {
        $influence = $this->getInfluenceOnGuild();
        $count = $this->getCount();

        if (count($influence) < $count) {
            return ["q" => Material::ERR_COST];
        }
        return ["confirm"];
    }

    function resolve(): void {
        $this->checkVoid();
        $owner = $this->getOwner();
        $count = $this->getCount();
        $influence = $this->getInfluenceOnGuild();

        // Move influence tokens from guild back to player's tableau (supply)
        $i = 0;
        foreach ($influence as $tokenId => $info) {
            if ($i >= $count) {
                break;
            }
            $this->dbSetTokenLocation(
                $tokenId,
                "tableau_$owner",
                0,
                clienttranslate('${player_name} spends ${token_name} from ${place_from}')
            );
            $i++;
        }
    }

    function getIconicName() {
        $count = $this->getCount();
        $infcolor = getPart($this->getGuild(), 1);
        if ($count == 1) {
            return "[wicon_inf_{$infcolor}_pay]";
        } elseif ($count == 2) {
            return "[wicon_inf_{$infcolor}_pay][wicon_inf_{$infcolor}_pay]";
        }
        return "{$count}x [wicon_inf_{$infcolor}_pay]";
    }
}
