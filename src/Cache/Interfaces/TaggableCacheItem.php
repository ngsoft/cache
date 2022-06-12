<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Interfaces;

use NGSOFT\Cache\Exceptions\InvalidArgument,
    Psr\Cache\CacheItemInterface;

interface TaggableCacheItem extends CacheItemInterface
{

    public const METADATA_EXPIRY = 0;
    public const METADATA_VALUE = 1;
    public const METADATA_TAGS = 2;

    /**
     * Adds a tag to a cache item.
     *
     * Tags are strings that follow the same validation rules as keys.
     *
     * @param string|string[] $tags A tag or array of tags
     *
     * @return static
     *
     * @throws InvalidArgument  When $tag is not valid
     */
    public function tag(string|iterable $tags): static;

    /**
     * Returns a list of metadata info that were saved alongside with the cached value.
     *
     * @return array
     */
    public function getMetadata(): array;
}
