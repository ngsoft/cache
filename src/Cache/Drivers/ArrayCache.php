<?php

declare(strict_types=1);

namespace NGSOFT\Cache\Drivers;

use NGSOFT\{
    Cache\TagList, Tools\FixedArray
};
use Throwable;

class ArrayCache extends BaseDriver {

    /** @var bool */
    protected $storeSerialized;

    /** @var int */
    protected $maxLifeTime;

    /** @var TagList */
    protected $taglist;

    /** @var array|FixedArray */
    protected $values = [];

    /**
     * @param bool $storeSerialized Serialize the values before storing them
     * @param int $capacity Maximum items that can be stored in the cache, a value of 0 disables that feature
     * @param int $maxLifeTime Maximum number of seconds an item can stay in the cache,  a value of 0 disables that feature
     */
    public function __construct(
            bool $storeSerialized = true,
            int $capacity = 0,
            int $maxLifeTime = 0
    ) {

        parent::__construct();


        $this->maxLifeTime = max(0, $maxLifeTime);
        $this->capacity = max(0, $capacity);
        $this->storeSerialized = $storeSerialized;
        $this->doClear();
    }

    /** {@inheritdoc} */
    public function fetchTag(string $tag): \NGSOFT\Cache\Tag {
        return $this->taglist->getTag($tag);
    }

    /** {@inheritdoc} */
    public function saveTag(\NGSOFT\Cache\Tag ...$tags): bool {
        foreach ($tags as $tagItem) {

            foreach ($this->taglist->getTag($tagItem->getLabel()) as $key) {
                $this->taglist->remove($key->getLabel(), $tagItem->getLabel());
            }
            $this->taglist->loadTag($tagItem);
        }

        return true;
    }

    /** {@inheritdoc} */
    protected function doClear(): bool {
        $this->taglist = TagList::create();
        if ($this->capacity > 0) {
            $this->values = FixedArray::create($this->capacity);
            $this->expiries = FixedArray::create($this->capacity);
            $this->values->recursive = $this->expiries->recursive = false;
        } else $this->values = $this->expiries = [];
        return true;
    }

    /** {@inheritdoc} */
    protected function doDelete(string ...$keys): bool {
        foreach ($keys as $key) {
            $hKey = $this->getHashedKey($key);
            unset($this->expiries[$hKey], $this->values[$hKey]);
        }
        return true;
    }

    /** {@inheritdoc} */
    protected function doFetch(string ...$keys) {
        foreach ($keys as $key) {
            $hKey = $this->getHashedKey($key);
            if (isset($this->expiries[$hKey])) {
                $value = $this->values[$hKey];
                if ($this->storeSerialized) $value = $this->unserializeIfNeeded($value);
                yield $key => $value;
            } else yield $key => null;
        }
    }

    /** {@inheritdoc} */
    protected function doSave($keysAndValues): bool {

        $r = true;
        foreach ($keysAndValues as $key => $value) {
            $hKey = $this->getHashedKey($key);
            $expire = $value['e'] ?? 0;
            if ($this->storeSerialized) {
                $value = $this->serializeIfNeeded($value);
            }
            if (null !== $value) {
                $this->expiries[$hKey] = $expire;
                $this->values[$hKey] = $value;
            } else $r = false;
        }
        return $r;
    }

    /** {@inheritdoc} */
    public function removeExpired(): bool {
        foreach ($this->expiries as $k => $expire) {
            if ($this->isExpired($expire)) {
                unset($this->expiries[$k], $this->values[$k]);
            }
        }
        return true;
    }

    ////////////////////////////   Utils   ////////////////////////////

    /**
     * Unserialize data if needed
     *
     * @param mixed $value
     * @return mixed
     */
    protected function unserializeIfNeeded($value) {

        try {
            $this->setErrorHandler();
            if (
                    is_string($value) and
                    isset($value[2]) and
                    ':' === $value[1]
            ) {

                if (($value = \unserialize($value)) === false) return null;
            }
            return $value;
        } catch (Throwable $error) {
            return null;
        } finally {
            \restore_error_handler();
        }
    }

    /**
     * Serialize data if needed
     *
     * @param mixed $value
     * @return mixed
     */
    protected function serializeIfNeeded($value) {

        try {
            $this->setErrorHandler();
            if (is_string($value)) {
                if (isset($value[2]) and ':' === $value[1]) {
                    return \serialize($value);
                }
            } elseif (!is_scalar($value)) {
                $serialized = \serialize($value);
                if ('C' === $serialized[0] || 'O' === $serialized[0] || preg_match('/;[OCRr]:[1-9]/', $serialized)) {
                    return $serialized;
                }
            }

            return $value;
        } catch (Throwable $error) {
            return null;
        } finally {
            \restore_error_handler();
        }
    }

    public function __debugInfo() {
        return [
            'expiries' => $this->expiries,
            'values' => $this->values,
            'taglist' => $this->taglist
        ];
    }

}
