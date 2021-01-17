<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Cache\TagInterop\TaggableCacheItemPoolInterface,
    Psr\Log\LoggerInterface,
    Throwable;

/**
 * A Taggable Cache pool Implementation
 */
class TaggableCacheItemPool extends CacheItemPool implements TaggableCacheItemPoolInterface {

    /**
     * Key Modifier for a stored tag
     */
    private const TAG_KEY_MODIFIER = '__TAG__%s';

    /** @var CacheDriver */
    protected $tagDriver;

    /** @var Tag[] */
    protected $loadedTags = [];

    /**
     * @param CacheDriver $driver The Cache Driver
     * @param CacheDriver|null $tagDriver The driver used to hold tags, if set to null the cache driver will be used
     * @param int $defaultLifetime TTL dor cache entries without expiry values
     * @param string $namespace the namespace to use
     */
    public function __construct(
            CacheDriver $driver,
            CacheDriver $tagDriver = null,
            int $defaultLifetime = 0,
            string $namespace = ''
    ) {
        $this->tagDriver = $tagDriver ?? clone $driver;
        parent::__construct($driver, $defaultLifetime, $namespace);
    }

    ////////////////////////////   Pool   ////////////////////////////

    /** {@inheritdoc} */
    public function setLogger(LoggerInterface $logger) {
        $this->tagDriver->setLogger($logger);
        parent::setLogger($logger);
    }

    /** {@inheritdoc} */
    public function setNamespace(string $namespace): void {
        $this->tagDriver->setNamespace($namespace);
        parent::setNamespace($namespace);
    }

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
            $toRemove = [];
            foreach ($tags as $tagName) {
                foreach ($this->fetchTag($tagName) as $keyItem) {
                    $toRemove[$keyItem->getLabel()] = $keyItem->getLabel();
                }
            }
            if (count($toRemove) > 0) return $this->deleteItems($toRemove);
            return true;
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    ////////////////////////////   Overrides   ////////////////////////////

    /** {@inheritdoc} */
    public function clear(): bool {
        $this->loadedTags = [];
        return parent::clear();
    }

    /**
     * {@inheritdoc}
     * @return bool
     */
    public function commit() {
        try {
            if (empty($this->deferred)) return true;

            $r = true;
            $items = $this->deferred;
            $this->deferred = $toSave = $toRemove = $loaded = [];
            $tagList = new TagList();
            //group items by expiries
            foreach ($items as $key => $item) {
                if (!$item->isHit()) {
                    $toRemove[] = $key;
                    continue;
                }
                $expiry = $item->getExpiration() ?? $this->getExpiryRealValue();
                $toSave[$expiry] = $toSave[$expiry] ?? [];
                // tags
                foreach ($item->getPreviousTags() as $tagLabel) {
                    if (!isset($loaded[$tagLabel])) {
                        $tagList->loadTag($this->fetchTag($tagLabel));
                        $loaded[$tagLabel] = $tagLabel;
                    }
                    $tagList->remove($key, $tagLabel);
                }
                foreach (($tags = $item->getNewTags()) as $tagLabel) {
                    if (!isset($loaded[$tagLabel])) {
                        $tagList->loadTag($this->fetchTag($tagLabel));
                        $loaded[$tagLabel] = $tagLabel;
                    }
                    $tagList->add($key, $tagLabel);
                }
                //a CacheObject makes it easier to retrieve item expiry
                $toSave[$expiry] [$key] = new CacheObject($key, $item->get(), $expiry, $tags);
            }
            foreach ($loaded as $tagLabel) $r = $this->saveTag($tagList->getTag($tagLabel)) && $r;
            foreach ($toSave as $expiry => $knv) $r = $this->driver->save($knv, $expiry) && $r;
            if (count($toRemove) > 0) $r = $this->deleteItems($toRemove) && $r;
            return $r;
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /**
     * {@inheritdoc}
     * @return bool
     */
    public function deleteItems(array $keys) {
        if (empty($keys)) return true;
        try {
            $this->doCheckKeys($keys);
            $keys = array_values(array_unique($keys));
            foreach ($keys as $key) {
                $this->getValidKey($key);
                unset($this->deferred[$key]);
            }
            return $this->driver->delete(...$keys);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    ////////////////////////////   Utils   ////////////////////////////

    /**
     * Shortcut to fetch value directly from the tag driver
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function fetchOne(string $key, $default = null) {
        foreach ($this->tagDriver->fetch($key) as $value) {
            return $value !== null ? $value : $default;
        }
        return $default;
    }

    /**
     * Shortcut to save value directly to the tag driver
     *
     * @param string $key The cache Key
     * @param mixed $value The value to save
     * @param int $expiry Expiration Timestamp
     * @return bool
     */
    protected function saveOne(string $key, $value, int $expiry = 0): bool {
        $this->doCheckValue($value);
        return
                $value !== null or
                $this->isExpired($expiry) ?
                $this->tagDriver->save([$key => $value], $expiry) :
                $this->tagDriver->delete($key);
    }

    /**
     * Converts tag name into tag key
     *
     * @param mixed $tag
     * @return string
     */
    protected function getTagKey($tag): string {
        return sprintf(self::TAG_KEY_MODIFIER, $this->getValidTag($tag));
    }

    /**
     * Saves or removes a tag
     *
     * @param Tag $tag
     * @return bool
     */
    protected function saveTag(Tag $tag): bool {
        $label = $tag->getLabel();
        unset($this->loadedTags[$label]);
        if (count($tag) == 0) return $this->tagDriver->delete($this->getTagKey($label));
        if ($r = $this->saveOne($this->getTagKey($label), $tag)) $this->loadedTags[$label] = clone $tag;
        return $r;
    }

    /**
     * Fetches a tag from the cache
     *
     * @param string $tag
     * @return Tag
     */
    protected function fetchTag(string $tag): Tag {
        if (isset($this->loadedTags[$tag])) return clone $this->loadedTags[$tag];
        $val = $this->fetchOne($this->getTagKey($tag));
        $val = $val instanceof Tag ? $val : new Tag($tag);
        $this->loadedTags[$tag] = clone $val;
        return $val;
    }

}
