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

class Op_infAny extends Op_infBase {
    function getGuild(): string {
        return $this->getDataField("guild", "");
    }

    public function auto(): bool {
        if ($this->isAutomaPlayer()) {
            // Hand off to the AI-specific operation; the player-side flow doesn't apply.
            $this->queue("ai_infAny", $this->game->getAutomaColor());
            return true;
        }
        return parent::auto();
    }

    function getPossibleMoves() {
        $selectedGuild = $this->getGuild();

        if ($selectedGuild === "") {
            // Step 1: Select which guild to place on

            return [
                "guild_black" => ["q" => Material::RET_OK, "name" => $this->game->getTokenName("guild_black")],
                "guild_yellow" => ["q" => Material::RET_OK, "name" => $this->game->getTokenName("guild_yellow")],
                "guild_blue" => ["q" => Material::RET_OK, "name" => $this->game->getTokenName("guild_blue")],
            ];
        }

        // Step 2: Guild selected, now place or select influence to move
        $influence = $this->getInfluenceInPlayerSupply();
        if (count($influence) > 0) {
            return ["confirm"];
        }

        // No influence in supply - show movable influence
        $movable = $this->getMovableInfluence($selectedGuild);
        if (count($movable) == 0) {
            return ["q" => Material::ERR_NONE_LEFT];
        }

        return $movable;
    }

    public function getUiArgs() {
        return ["buttons" => true];
    }

    function canSkip() {
        $influence = $this->getInfluenceInPlayerSupply();
        if (count($influence) > 0) {
            return false;
        }
        return true;
    }

    function resolve(): void {
        $owner = $this->getOwner();
        $selectedGuild = $this->getGuild();

        if ($selectedGuild === "") {
            // Step 1: Store selected guild and queue step 2
            $guild = $this->getCheckedArg();
            $this->queue($this->getType(), $owner, ["guild" => $guild]);
            return;
        }

        // Step 2: Place or move influence
        $influence = $this->getInfluenceInPlayerSupply();

        if (count($influence) > 0) {
            // Place from supply
            $influenceKey = array_key_first($influence);
            $this->dbSetTokenLocation(
                $influenceKey,
                $selectedGuild,
                0,
                clienttranslate('${player_name} places ${token_name} on ${place_name}')
            );
        } else {
            // Move from another location
            $influenceKey = $this->getCheckedArg();
            $this->dbSetTokenLocation(
                $influenceKey,
                $selectedGuild,
                0,
                clienttranslate('${player_name} moves ${token_name} to ${place_name}')
            );
        }
    }

    function getPrompt() {
        $selectedGuild = $this->getGuild();

        if ($selectedGuild === "") {
            return clienttranslate("Select one guild to place influence on");
        }

        return parent::getPrompt();
    }
}
