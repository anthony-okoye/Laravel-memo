<?php

namespace App\Repositories;

use Illuminate\Support\Str;

class FileEntryRepository
{
    private string $dataFilePath;
    private string $lockFilePath;

    public function __construct(?string $dataFilePath = null)
    {
        $this->dataFilePath = $dataFilePath ?? storage_path('app/entries.json');
        $this->lockFilePath = $this->dataFilePath . '.lock';
        $this->ensureStorageInitialized();
    }

    public function all(): array
    {
        return $this->withLock(function (): array {
            return $this->readEntriesUnlocked();
        });
    }

    public function append(array $entry): array
    {
        return $this->withLock(function () use ($entry): array {
            $entries = $this->readEntriesUnlocked();
            $entries[] = $entry;
            $this->atomicWrite(json_encode($entries, JSON_UNESCAPED_SLASHES));
            return $entry;
        });
    }

    public function update(string $id, array $patch): array
    {
        return $this->withLock(function () use ($id, $patch): array {
            $entries = $this->readEntriesUnlocked();
            $foundIndex = null;
            foreach ($entries as $index => $item) {
                if (($item['id'] ?? null) === $id) {
                    $foundIndex = $index;
                    $entries[$index] = array_merge($item, $patch);
                    break;
                }
            }
            if ($foundIndex === null) {
                abort(404, 'Entry not found');
            }
            $this->atomicWrite(json_encode($entries, JSON_UNESCAPED_SLASHES));
            return $entries[$foundIndex];
        });
    }

    private function ensureStorageInitialized(): void
    {
        $dir = dirname($this->dataFilePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (!file_exists($this->dataFilePath)) {
            $this->atomicWrite('[]');
        }
        if (!file_exists($this->lockFilePath)) {
            touch($this->lockFilePath);
        }
    }

    private function atomicWrite(string $contents): void
    {
        $tmp = $this->dataFilePath . '.' . Str::uuid() . '.tmp';
        file_put_contents($tmp, $contents);
        rename($tmp, $this->dataFilePath);
    }

    /**
     * @template T
     * @param callable():T $callback
     * @return T
     */
    private function withLock(callable $callback)
    {
        $lockHandle = fopen($this->lockFilePath, 'c');
        if ($lockHandle === false) {
            return $callback();
        }
        try {
            flock($lockHandle, LOCK_EX);
            return $callback();
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }

    /**
     * Read and decode entries without acquiring the file lock.
     * Must only be called from within withLock().
     */
    private function readEntriesUnlocked(): array
    {
        $contents = file_get_contents($this->dataFilePath);
        if ($contents === false || trim($contents) === '') {
            return [];
        }
        $decoded = json_decode($contents, true);
        if (!is_array($decoded)) {
            @copy($this->dataFilePath, $this->dataFilePath . '.bak');
            $this->atomicWrite('[]');
            return [];
        }
        return $decoded;
    }
}


