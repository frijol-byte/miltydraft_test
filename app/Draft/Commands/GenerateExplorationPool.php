<?php

declare(strict_types=1);

namespace App\Draft\Commands;

use App\Draft\Settings;
use App\Shared\Command;
use App\TwilightImperium\Exploration;

/**
 * Generates a pool of draftable exploration events based on settings
 */
class GenerateExplorationPool implements Command
{
    private readonly array $explorationData;

    public function __construct(
        private readonly Settings $settings,
    ) {
        $this->explorationData = Exploration::all();
    }

    /**
     * @return array<Exploration>
     */
    public function handle(): array
    {
        if (! $this->settings->includeExplorations) {
            return [];
        }

        $this->settings->seed->setForExplorations();

        $explorations = array_values($this->explorationData);
        shuffle($explorations);

        return array_slice($explorations, 0, $this->settings->numberOfExplorations);
    }
}
