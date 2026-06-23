<?php

declare(strict_types=1);

namespace App\AudioCatalog\Application\Storage;

interface AudioAssetStorageInterface
{
    public function store(string $sourcePath, string $extension): string;

    public function delete(string $storageKey): void;
}
