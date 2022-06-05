<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

interface TaggableCacheItem extends \Psr\Cache\CacheItemInterface
{

    /**
     * Adds a tag to a cache item.
     *
     * Tags are strings that follow the same validation rules as keys.
     *
     * @param string|string[] $tags A tag or array of tags
     *
     * @return $this
     *
     * @throws InvalidArgument  When $tag is not valid
     */
    public function tag(string|iterable $tags): static;
}
