<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

interface TaggedCacheDriver extends CacheDriver
{

    /**
     * Checks if key is tagged with specific tags
     *
     * @param string $key
     * @param string|array $tag
     * @return bool
     */
    public function hasTag(string $key, string|array $tag): bool;

    /**
     * Tag a key entry
     *
     * @param string $key
     * @param string|string[] $tag
     * @return bool
     */
    public function setTag(string $key, string|array $tag): bool;

    /**
     * Get tags for key entry
     *
     * @param string $key
     * @return string[]
     */
    public function getTags(string $key): iterable;

    /**
     * Get tagged cache entries
     *
     * @param string $tag
     * @return iterable<string,CacheEntry>
     */
    public function getTagged(string|array $tag): iterable;

    /**
     * Remove tagged entries
     *
     * @param string|string[] $tag
     * @return bool
     */
    public function deleteTagged(string|array $tag): bool;
}