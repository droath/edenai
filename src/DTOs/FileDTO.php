<?php

declare(strict_types=1);

namespace Droath\Edenai\DTOs;

/**
 * Immutable data transfer object for file references.
 *
 * This DTO provides a unified interface for handling both local file paths
 * and remote file URLs. It uses static factory methods to enforce proper
 * instantiation and provides runtime type detection through helper methods.
 *
 * Usage:
 * - For local files: FileDTO::fromPath('/path/to/file.png')
 * - For remote files: FileDTO::fromUrl('https://example.com/file.png')
 *
 * @package Droath\Edenai\DTOs
 */
final readonly class FileDTO
{
    /**
     * Private constructor to enforce factory method usage.
     *
     * @param string|null $path Absolute path to the local file
     * @param string|null $url URL to the remote file
     */
    private function __construct(
        private ?string $path,
        private ?string $url,
    ) {}

    /**
     * Create a FileDTO from a local file path.
     *
     * @param string $filePath Absolute path to the local file
     *
     * @return self A new FileDTO instance with the path set
     */
    public static function fromPath(string $filePath): self
    {
        return new self(path: $filePath, url: null);
    }

    /**
     * Create a FileDTO from a remote file URL.
     *
     * @param string $fileUrl URL to the remote file
     *
     * @return self A new FileDTO instance with the URL set
     */
    public static function fromUrl(string $fileUrl): self
    {
        return new self(path: null, url: $fileUrl);
    }

    /**
     * Check if this DTO represents a local file path.
     *
     * @return bool True if this DTO contains a file path, false otherwise
     */
    public function isPath(): bool
    {
        return $this->path !== null;
    }

    /**
     * Check if this DTO represents a remote file URL.
     *
     * @return bool True if this DTO contains a file URL, false otherwise
     */
    public function isUrl(): bool
    {
        return $this->url !== null;
    }

    /**
     * Get the local file path.
     *
     * @return string|null The file path, or null if this is a URL-based DTO
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * Get the remote file URL.
     *
     * @return string|null The file URL, or null if this is a path-based DTO
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }
}
