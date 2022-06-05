<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use NGSOFT\{
    Cache\Events\CacheEvent, Cache\Events\CacheHit, Cache\Events\CacheMiss, Cache\Events\KeyDeleted, Cache\Events\KeySaved, Traits\Unserializable
};
use Psr\{
    Cache\CacheItemInterface, Cache\CacheItemPoolInterface, EventDispatcher\EventDispatcherInterface, Log\LoggerInterface
};
use Throwable;

class_exists(Item::class);
class_exists(InvalidArgument::class);

final class CachePool extends NamespaceAble implements CacheItemPoolInterface
{

    use Unserializable;

    /** @var Item[] */
    private array $queue = [];
    private ?EventDispatcherInterface $eventDispatcher = null;

    public function __construct(
            TaggedCacheDriver $driver,
            protected int $defaultLifetime = 0,
            string $namespace = '',
            protected bool $useTags = false
    )
    {
        parent::__construct($driver, $namespace);
        $this->driver->setDefaultLifetime($defaultLifetime);
    }

    public function __destruct()
    {
        $this->commit();
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        parent::setLogger($logger);

        $this->driver->setLogger($logger);
    }

    /** {@inheritdoc} */
    public function clear(): bool
    {
        $this->clearNamespace();
        return $this->driver->clear();
    }

    /** {@inheritdoc} */
    public function deleteItem(string $key): bool
    {
        try {
            if ($this->driver->delete($this->getCacheKey($key))) {
                if ($this->useTags) {
                    $this->driver->deleteTags($this->getCacheKey($key));
                }
                $this->dispatch(new KeyDeleted($key));
                return true;
            }

            return false;
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function deleteItems(array $keys): bool
    {

        $result = true;
        foreach ($keys as $key) {
            $result = $this->deleteItem($key) && $result;
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getItem(string $key): TaggableCacheItem
    {

        try {
            $entry = $this->driver->get($this->getCacheKey($key));
            if ($entry->isHit()) {
                $this->dispatch(new CacheHit($key, $entry->value));
                return Item::create($key, $entry->value, $entry->expiry === 0 ? null : $entry->expiry);
            }

            $this->dispatch(new CacheMiss($key));
            return Item::create($key);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function getItems(array $keys = []): iterable
    {
        try {
            foreach ($keys as $key) {
                $nkey = $this->getCacheKey($key);
                if (isset($this->queue[$nkey])) {
                    $item = clone $this->queue[$nkey];
                } else $item = $this->getItem($key);
                yield $key => $item;
            }
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function hasItem(string $key): bool
    {

        try {
            $nkey = $this->getCacheKey($key);
            if (isset($this->queue[$nkey])) {
                $this->commit();
            }
            return $this->driver->has($nkey);
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function save(CacheItemInterface $item): bool
    {
        try {
            return $this->saveDeferred($item) && $this->commit();
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function saveDeferred(CacheItemInterface $item): bool
    {

        try {

            if ($item instanceof Item === false) {
                throw new InvalidArgument(sprintf(
                                        'Cache items are not transferable between pools. "%s" requested, "%s" given.',
                                        Item::class,
                                        get_class($item)
                ));
            }


            $this->queue[$this->getCacheKey($item->getKey())] = $item;
            return true;
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /** {@inheritdoc} */
    public function commit(): bool
    {

        try {
            $queue = $this->queue;
            $this->queue = $toSave = $assoc = [];

            $result = true;

            // pass 1: sort by expiry
            /** @var Item $item */
            foreach ($queue as $nkey => $item) {
                if (!$item->isHit()) {
                    $result = $this->driver->delete($nkey) && $result;
                    continue;
                }

                $expiry = $this->getExpiryRealValue($item->expiry);

                $toSave[$expiry] = $toSave[$expiry] ?? [];
                $toSave[$expiry][$nkey] = $item->value;
                $assoc[$nkey] = $item->getKey();
            }


            foreach ($toSave as $expiry => $items) {

                foreach ($this->driver->setMultiple($items, $expiry) as $nkey => $bool) {
                    if ($bool) {
                        $this->dispatch(new KeySaved($assoc[$nkey], $items[$nkey]));
                        $result = $this->saveTags($queue[$nkey]) && $result;
                    }
                    $result = $bool && $result;
                }
            }

            return $result;
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    /**
     * Invalidates cached items using tags.
     *
     * When implemented on a PSR-6 pool, invalidation should not apply
     * to deferred items. Instead, they should be committed as usual.
     * This allows replacing old tagged values by new ones without
     * race conditions.
     *
     * @param string[] $tags An array of tags to invalidate
     *
     * @return bool True on success
     *
     * @throws InvalidArgument When $tags is not valid
     */
    public function invalidateTags(array $tags): bool
    {
        try {
            $result = true;

            if (!$this->useTags) {
                throw new CacheError('Tags are disabled, cannot invalidate.');
            }

            foreach ($tags as $tag) {
                $ntag = $this->getCacheKey($tag);
                $result = $this->driver->deleteTagged($ntag) && $result;
            }

            return $result;
        } catch (Throwable $error) {
            throw $this->handleException($error, __FUNCTION__);
        }
    }

    public function dispatch(CacheEvent $event): object
    {
        return $this->eventDispatcher?->dispatch($event) ?? $event;
    }

    protected function saveTags(Item $item): bool
    {

        if (!$this->useTags) {
            if (!empty($item->tags)) {
                $this->logger?->debug('Cannot save tags, they are disabled.', [
                    'tags' => $item->tags
                ]);
            }
            return true;
        }

        if (empty($item->tags)) {
            return $this->driver->deleteTags($this->getCacheKey($item->getKey()));
        }
        $tags = [];
        foreach ($item->tags as $tag) {
            $ntag = $this->getCacheKey($tag);
            $tags[$ntag] = $ntag;
        }
        return $this->driver->setTag($this->getCacheKey($item->getKey()), $tags);
    }

    protected function getExpiryRealValue(?int $expiry = null): int
    {
        if (is_int($expiry)) {
            return $expiry;
        }
        return $this->defaultLifetime > 0 ? time() + $this->defaultLifetime : 0;
    }

}
