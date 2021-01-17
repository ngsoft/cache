<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

class TaggableCacheItemPool extends CacheItemPool implements \Cache\TagInterop\TaggableCacheItemPoolInterface {

    /**
     * Holds the list of issued tags
     */
    private const ISSUED_TAG_KEY_INDEX = 'NGSOFT_CACHE_DRIVER_TAG_INDEX';

    /**
     * Key Modifier for a stored tag
     */
    private const TAG_KEY_MODIFIER = '__TAG__%s';

    /** @var CacheDriver */
    protected $tagDriver;

    /** @var array */
    protected $issuedTags = [];

    ////////////////////////////   Tags   ////////////////////////////

    /**
     * {@inheritdoc}
     * @return bool
     */
    public function invalidateTag($tag) {
        try {
            return $this->invalidateTags([$tag]);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /**
     * {@inheritdoc}
     * @return bool
     */
    public function invalidateTags(array $tags) {
        if (empty($tags)) return true;
        try {
            if ($this->deferred) $this->commit();
            $tags = array_map(fn($t) => $this->getValidTag($t), array_values(array_unique($tags)));
            $toRemove = $tagitems = [];
            foreach ($tags as $tagName) {
                foreach ($this->driver->fetchTag($tagName) as $keyItem) {
                    $toRemove[$keyItem->getLabel()] = $keyItem->getLabel();
                }
            }
            if (count($toRemove) > 0) return $this->deleteItems($toRemove);
            return true;
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

}