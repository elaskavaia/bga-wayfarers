<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * wayfarers implementation : © Alena Laskavaia <laskava@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * wayfarers.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */

declare(strict_types=1);

namespace Bga\Games\wayfarers\Operations;

use Bga\Games\wayfarers\OpCommon\CountableOperation;
use Bga\Games\wayfarers\Material;

class Op_pay extends CountableOperation {
    function getResType() {
        $type = $this->getType();
        return substr($type, 2); // n_XYZ -> XYZ
    }

    function getLimitCount() {
        $owner = $this->getOwner();
        $current = $this->game->tokens->getTrackerValue($owner, $this->getResType());
        return $current;
    }

    function getPossibleMoves() {
        $owner = $this->getOwner();
        $current = $this->game->tokens->getTrackerValue($owner, $this->getResType());
        if ($current < $this->getCount()) {
            return ["q" => Material::MA_ERR_COST];
        }
        return [$this->getResType()];
    }

    function resolve(): void {
        $this->checkVoid(); //validation
        $count = $this->getCount();
        $this->game->effect_incCount($this->getOwner(), $this->getResType(), -$count, $this->getReason());
        return;
    }

    public function getExtraArgs() {
        return parent::getExtraArgs() + ["token_div" => $this->game->tokens->getTrackerId($this->getOwner(), $this->getResType())];
    }

    function getButtonName() {
        $count = $this->getCount();
        if ($count == 1) {
            return '${token_div}';
        } elseif ($count == 2) {
            return '${token_div} ${token_div}';
        } elseif ($count == 3) {
            return '${token_div} ${token_div} ${token_div}';
        }
        return clienttranslate('${count} ${token_div}');
    }

    public function getPrompt() {
        return clienttranslate('Pay ${count} ${token_div}');
    }
}
