<?php
declare(strict_types=1);

namespace Panth\ProductAttachments\Test\Unit;

trait TempDirectoryTrait
{
    private ?string $tempRoot = null;

    private function tempRoot(): string
    {
        if ($this->tempRoot === null) {
            $this->tempRoot = sys_get_temp_dir() . '/panth_pa_' . bin2hex(random_bytes(6));
            mkdir($this->tempRoot, 0777, true);
        }
        return $this->tempRoot;
    }

    private function putTempFile(string $relativePath, string $content, ?int $mtime = null): string
    {
        $path = $this->tempRoot() . '/' . $relativePath;
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, $content);
        if ($mtime !== null) {
            touch($path, $mtime);
        }
        return $path;
    }

    private function removeTempRoot(): void
    {
        if ($this->tempRoot === null || !is_dir($this->tempRoot)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->tempRoot, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->tempRoot);
        $this->tempRoot = null;
    }
}
