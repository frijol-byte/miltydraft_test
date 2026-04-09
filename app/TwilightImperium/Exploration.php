<?php

declare(strict_types=1);

namespace App\TwilightImperium;

class Exploration
{
    /**
     * @var array<string, Exploration>
     */
    private static array $allExplorationData;

    public function __construct(
        public readonly string $name,
        public readonly string $id,
        public readonly string $type,
        public readonly string $description,
        public readonly string $set,
    ) {
    }

    public static function fromJson($data): self
    {
        return new self(
            $data['name'],
            $data['id'],
            $data['type'],
            $data['description'],
            $data['set'],
        );
    }

    /**
     * @return array<string, Exploration>
     */
    public static function all(): array
    {
        if (! isset(self::$allExplorationData)) {
            $rawData = json_decode(file_get_contents('data/explorations.json'), true);
            self::$allExplorationData = array_map(fn ($data) => self::fromJson($data), $rawData);
        }

        return self::$allExplorationData;
    }

    public function typeLabel(): string
    {
        return ucfirst($this->type);
    }

    public function typeClass(): string
    {
        return 'exploration-' . $this->type;
    }
}
