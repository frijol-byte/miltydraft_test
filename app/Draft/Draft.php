<?php

declare(strict_types=1);

namespace App\Draft;

use App\TwilightImperium\Exploration;
use App\TwilightImperium\Faction;
use App\TwilightImperium\Tile;

class Draft
{
    public function __construct(
        // @todo implement DraftId value object
        public string   $id,
        public bool     $isDone,
        /** @var array<string, Player> $players */
        public array    $players,
        public Settings $settings,
        public Secrets  $secrets,
        /** @var array<Slice> $slicePool */
        public array $slicePool,
        /** @var array<Faction> $factionPool */
        public array $factionPool,
        /** @var array<Pick> $log */
        public array $log = [],
        public ?PlayerId $currentPlayerId = null,
        /** @var array<Exploration> $explorationPool */
        public array $explorationPool = [],
    ) {
    }

    public static function fromJson($data)
    {
        /**
         * @var array<string, Player>
         */
        $players = array_reduce($data['draft']['players'], function ($players, $playerData) {
            $player = Player::fromJson($playerData);
            $players[$player->id->value] = $player;

            return $players;
        }, []);

        return new self(
            $data['id'],
            $data['done'],
            $players,
            Settings::fromJson($data['config']),
            Secrets::fromJson($data['secrets']),
            self::slicesFromJson($data['slices']),
            self::factionsFromJson($data['factions']),
            array_map(fn ($logData) => Pick::fromJson($logData), $data['draft']['log']),
            $data['draft']['current'] != null ? PlayerId::fromString($data['draft']['current']) : null,
            self::explorationsFromJson($data['explorations'] ?? []),
        );
    }

    /**
     * @return array<Slice>
     */
    private static function slicesFromJson($slicesData): array
    {
        $allTiles = Tile::all();

        return array_map(function (array $sliceData) use ($allTiles) {
            $tiles = array_map(
                fn (string|int $tileId) => $allTiles[$tileId],
                $sliceData['tiles'],
            );

            return new Slice($tiles);
        }, $slicesData);
    }

    /**
     * @return array<Faction>
     */
    private static function factionsFromJson($factionNames): array
    {
        $allFactions = Faction::all();

        return array_map(function (string $name) use ($allFactions) {
            return $allFactions[$name];
        }, $factionNames);
    }

    /**
     * @return array<Exploration>
     */
    private static function explorationsFromJson(array $explorationNames): array
    {
        if (empty($explorationNames)) {
            return [];
        }

        $allExplorations = Exploration::all();

        return array_map(function (string $name) use ($allExplorations) {
            return $allExplorations[$name];
        }, $explorationNames);
    }

    public function toFileContent(): string
    {
        return json_encode($this->toArray(true));
    }

    public function toArray($includeSecrets = false): array
    {
        $data = [
            'id' => $this->id,
            'done' => $this->isDone,
            'config' => $this->settings->toArray(),
            'draft' => [
                'players' => array_map(fn (Player $player) => $player->toArray(), $this->players),
                'log' => array_map(fn (Pick $pick) => $pick->toArray(), $this->log),
                'current' => $this->currentPlayerId?->value,
            ],
            'factions' => array_map(fn (Faction $f) => $f->name, $this->factionPool),
            'slices' => array_map(fn (Slice $s) => ['tiles' => $s->tileIds()], $this->slicePool),
            'explorations' => array_map(fn (Exploration $e) => $e->name, $this->explorationPool),
        ];

        if ($includeSecrets) {
            $data['secrets'] = $this->secrets->toArray();
        }

        return $data;
    }

    public function updateCurrentPlayer(): void
    {
        $doneSteps = count($this->log);
        $snakeDraft = array_merge(array_keys($this->players), array_keys(array_reverse($this->players)));

        $picksPerPlayer = $this->settings->includeExplorations ? 4 : 3;

        if (count($this->log) >= (count($this->players) * $picksPerPlayer)) {
            $this->isDone = true;
            $this->currentPlayerId = null;
        } else {
            $this->isDone = false;
            $this->currentPlayerId = PlayerId::fromString($snakeDraft[$doneSteps % count($snakeDraft)]);
        }
    }

    public function canRegenerate(): bool
    {
        return empty($this->log);
    }

    public function playerById(PlayerId $id): Player
    {
        foreach ($this->players as $p) {
            if ($p->id->equals($id)) {
                return $p;
            }
        }

        throw new \Exception('No player found with id ' . $id->value);
    }

    public function updatePlayerData(Player $newPlayerData): void
    {
        if (! isset($this->players[$newPlayerData->id->value])) {
            throw new \Exception('No player found with id ' . $newPlayerData->id->value);
        }

        $this->players[$newPlayerData->id->value] = $newPlayerData;
    }

}