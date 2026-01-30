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

use Bga\Games\wayfarers\OpCommon\Operation;

/**
 * Base class for operations that acquire something with a cost (cards, upgrades).
 * Provides coin/food discount logic based on caravan assets from die placement.
 */
abstract class Op_acquireBase extends Operation {
    function getDie() {
        return $this->getDataField("die", null);
    }
    /**
     * Get the die value
     */
    function getDieValue(): int {
        if (!$this->getDie()) {
            return 0;
        }
        return (int) $this->game->tokens->db->getTokenState($this->getDie());
    }

    /**
     * Get coin discount from caravan assets (coinDis) for the placed die
     */
    function getCoinDiscount(): int {
        $dieValue = $this->getDieValue();
        if (!$dieValue) {
            return 0;
        }
        $owner = $this->getOwner();
        $caravanAssets = $this->game->getCaravanAssetsForDie($dieValue, $owner);
        return $caravanAssets["coinDis"] ?? 0;
    }

    /**
     * Get food discount from caravan assets (foodDis) for the placed die
     */
    function getFoodDiscount(): int {
        $dieValue = $this->getDieValue();
        if (!$dieValue) {
            return 0;
        }
        $owner = $this->getOwner();
        $caravanAssets = $this->game->getCaravanAssetsForDie($dieValue, $owner);
        return $caravanAssets["foodDis"] ?? 0;
    }

    /**
     * Apply food discount to a rule string containing food payment operations (e.g., "2n_food:cardLand,coin")
     * Reduces Xn_food by the food discount amount (minimum 0)
     */
    function applyFoodDiscount(string $rule): string {
        $foodDis = $this->getFoodDiscount();
        //$this->notifyMessage(clienttranslate("activated Provisions discount $foodDis"));
        if ($foodDis <= 0) {
            return $rule;
        }

        return preg_replace_callback(
            "/(\d*)n_food/",
            function ($matches) use ($foodDis) {
                $count = $matches[1] === "" ? 1 : (int) $matches[1];
                $newCount = max(0, $count - $foodDis);
                if ($newCount == 0) {
                    return "nop";
                }
                return "{$newCount}n_food";
            },
            $rule
        );
    }
}
