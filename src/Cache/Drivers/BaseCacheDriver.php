<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use ErrorException;
use NGSOFT\{
    Cache\TaggedCacheDriver, Traits\StringableObject, Traits\Unserializable
};
use Psr\Log\LoggerAwareTrait,
    Throwable,
    Traversable;

abstract class BaseCacheDriver implements TaggedCacheDriver
{

    use LoggerAwareTrait,
        StringableObject,
        Unserializable;

    protected const TAG_KEY_ENTRY = 'NGSOFTCacheTag[%s]';
    protected const TAG_KEY_TAG = 'NGSOFTCacheTagged[%s]';

    protected int $defaultLifetime = 0;

    /** {@inheritdoc} */
    public function setDefaultLifetime(int $defaultLifetime): void
    {
        $this->defaultLifetime = max(0, $defaultLifetime);
    }

    /** {@inheritdoc} */
    public function purge(): void
    {

    }

    /** {@inheritdoc} */
    public function getRaw(string $key): mixed
    {
        return $this->get($key)->value;
    }

    /** {@inheritdoc} */
    public function deleteMultiple(iterable $keys): Traversable
    {

        foreach ($keys as $key) {
            yield $key => $this->delete($key);
        }
    }

    /** {@inheritdoc} */
    public function getMultiple(iterable $keys): Traversable
    {
        foreach ($keys as $key) {
            yield $key => $this->get($key);
        }
    }

    /** {@inheritdoc} */
    public function setMultiple(iterable $values, int $expiry = 0): Traversable
    {

        foreach ($values as $key => $value) {
            yield $key => $this->set($key, $value, $expiry);
        }
    }

    /** {@inheritdoc} */
    public function getTagged(string|array $tag): iterable
    {

        $tag = is_array($tag) ? $tag : [$tag];

        $entries = [];

        foreach ($tag as $tagName) {

            $encodedTagKey = sprintf(static::TAG_KEY_TAG, $tagName);
            $tagEntry = $this->getRaw($encodedTagKey) ?? [];
            foreach ($tagEntry as $key) {
                if (!isset($entries[$key])) {

                    if ($this->hasTag($key, $tag)) {
                        $entries[$key] = $this->get($key);
                        yield $key => $entries[$key];
                    }
                }
            }
        }
    }

    /** {@inheritdoc} */
    public function hasTag(string $key, string|array $tag): bool
    {

        $tag = is_array($tag) ? $tag : [$tag];
        $missing = [];
        foreach ($tag as $tagName) {
            $missing[$tagName] = $tagName;
        }

        foreach ($this->getTags($key) as $tagged) {
            unset($missing[$tagged]);
        }
        return count($missing) === 0;
    }

    /** {@inheritdoc} */
    public function setTag(string $key, string|array $tag): bool
    {

        $encodedKey = sprintf(static::TAG_KEY_ENTRY, $key);
        $tag = is_string($tag) ? [$tag] : $tag;
        $result = $this->set($encodedKey, $tag);

        foreach ($tag as $tagName) {
            $encodedTagKey = sprintf(static::TAG_KEY_TAG, $tagName);
            $tagEntry = $this->getRaw($encodedTagKey) ?? [];
            $tagEntry[$key] = $key;
            $result = $this->set($encodedTagKey, $tagEntry) && $result;
        }

        return $result;
    }

    /** {@inheritdoc} */
    public function getTags(string $key): iterable
    {
        $encodedKey = sprintf(static::TAG_KEY_ENTRY, $key);
        return $this->getRaw($encodedKey) ?? [];
    }

    /** {@inheritdoc} */
    public function deleteTags(string $key): bool
    {
        $encodedKey = sprintf(static::TAG_KEY_ENTRY, $key);
        return $this->delete($encodedKey);
    }

    /** {@inheritdoc} */
    public function deleteTagged(string|array $tag): bool
    {

        $tag = is_string($tag) ? [$tag] : $tag;
        $result = true;

        foreach ($tag as $tagName) {

            $encodedTagKey = sprintf(static::TAG_KEY_TAG, $tagName);
            $tagEntry = $this->getRaw($encodedTagKey) ?? [];

            foreach ($tagEntry as $key) {
                // check if entry has given tag
                $encodedKey = sprintf(static::TAG_KEY_ENTRY, $key);
                $keyTags = $this->getRaw($encodedKey);

                if (!in_array($tagName, $keyTags)) {
                    continue;
                } else {
                    $result = $this->delete($encodedKey) && $result;
                    $result = $this->delete($key) && $result;
                }
            }

            $result = $this->delete($encodedTagKey) && $result;
        }

        return $result;
    }

    /**
     * Get a 32 Chars hashed key
     *
     * @param string $key
     * @return string
     */
    protected function getHashedKey(string $key): string
    {
        // classname added to prevent conflicts on similar drivers
        // MD5 as we need speed and some filesystems are limited in length
        return hash('MD5', static::class . $key);
    }

    /**
     * Convenience function to convert expiry into TTL
     * A TTL/expiry of 0 never expires
     *
     *
     * @param int $expiry
     * @return int the ttl a negative ttl is already expired
     */
    protected function expiryToLifetime(int $expiry): int
    {
        return
                $expiry !== 0 ?
                $expiry - time() :
                0;
    }

    /**
     * Convenience function to check if item is expired status against current time
     * @param int|null $expiry
     * @return bool
     */
    protected function isExpired(?int $expiry = null): bool
    {
        $expiry = $expiry ?? 1;
        return $expiry !== 0 && microtime(true) > $expiry;
    }

    /**
     * Convenient Function used to convert php errors, warning, ... as ErrorException
     *
     * @suppress PhanTypeMismatchArgumentInternal
     * @staticvar Closure $handler
     * @return void
     */
    protected function setErrorHandler(): void
    {
        static $handler;
        if (!$handler) {
            $handler = static function ($type, $msg, $file, $line) {
                throw new ErrorException($msg, 0, $type, $file, $line);
            };
        }
        set_error_handler($handler);
    }

    protected function safeExec(callable $callable, array $arguments = []): mixed
    {
        try {
            $this->setErrorHandler();
            return call_user_func_array($callable, $arguments);
        } catch (Throwable) { return null; } finally { restore_error_handler(); }
    }

}
