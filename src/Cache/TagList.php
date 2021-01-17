<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Closure,
    Countable,
    Generator,
    IteratorAggregate,
    JsonSerializable;
use NGSOFT\{
    Tools\SharedList, Traits\Exportable
};
use Stringable;

/**
 * Manages Tags and Keys
 */
class TagList implements Stringable, JsonSerializable, Countable, IteratorAggregate {

    use CacheUtils,
        Exportable {
        Exportable::__debugInfo insteadof CacheUtils;
        Exportable::jsonSerialize insteadof CacheUtils;
        CacheUtils::__toString insteadof Exportable;
    }

    /** @var SharedList */
    private $list;

    /** @var Closure */
    private $tagManager;

    /** @var Closure */
    private $keyManager;

    /** @var string[] */
    private $removedKeys;

    /** @var string[] */
    private $removedTags;

    ////////////////////////////   Init   ////////////////////////////

    /**
     * Creates a new TagList
     * @return static
     */
    public static function create(): self {
        return new static();
    }

    public function __construct() {
        $this->clear();
    }

    /**
     * Reinitialize the instance
     *
     * @return void
     */
    public function clear(): void {
        $this->list = new SharedList();
        $tm = function(Tag $tag, array $properties): Tag {
            $tag->extract($properties);
            return $tag;
        };
        $km = function(Key $key, array $properties): Key {
            $key->extract($properties);
            return $key;
        };
        $this->tagManager = $tm->bindTo($this, Tag::class);
        $this->keyManager = $km->bindTo($this, Key::class);
        $this->removedKeys = $this->removedTags = [];
    }

    ////////////////////////////   API   ////////////////////////////

    /**
     * Adds association between key and tag
     * @param string $key
     * @param string $tag
     * @return self
     */
    public function add(string $key, string $tag): self {
        $this->list->set($key, $tag);
        return $this;
    }

    /**
     * Removes association between Key and Tag
     * @param string $key
     * @param string $tag
     * @return self
     */
    public function remove(string $key, string $tag): self {
        $this->list->delete($key, $tag);
        //keep track of the removed keys/tags for the iterators as they are removed from the SharedList
        $this->removedKeys[$key] = $key;
        $this->removedTags[$tag] = $tag;
        return $this;
    }

    /**
     * Removes all associations with a key
     *
     * @suppress PhanUnusedPublicNoOverrideMethodParameter
     * @param string $key
     * @return self
     */
    public function clearKey(string $key): self {
        $this->list = $this->list->filter(fn($t, $k) => $k != $key);
        $this->removedKeys[$key] = $key;
        return $this;
    }

    /**
     * Removes all associations with a tag
     *
     * @param string $tag
     * @return self
     */
    public function clearTag(string $tag): self {
        $this->list = $this->list->filter(fn($t) => $t != $tag);
        $this->removedTags[$tag] = $tag;
        return $this;
    }

    /**
     * Get Key instance linked with the corresponding tags
     *
     * @param string $key
     * @return Key
     */
    public function getKey(string $key): Key {
        $i = new Key($key);
        $tags = [];
        foreach ($this->list->get($key) as $tagName) {
            $tags[$tagName] = new Tag($tagName);
        }
        return $this->setKeyProperties($i, ['items' => $tags]);
    }

    /**
     * Get Tag instance linked with the corresponding keys
     *
     * @param string $tag
     * @return Tag
     */
    public function getTag(string $tag): Tag {
        $i = new Tag($tag);
        $keys = [];
        foreach ($this->list->getKeys($tag) as $keyName) {
            $keys[$keyName] = new Key($keyName);
        }
        return $this->setTagProperties($i, ['items' => $keys]);
    }

    /**
     * Iterates all the Key Objects
     *
     * @return \Generator<string, Key>
     */
    public function getKeys(): Generator {

        foreach ($this->list->keys() as $keyName) {
            yield $keyName => $this->getKey($keyName);
        }
    }

    /**
     * Iterates all the Tags Objects
     *
     * @return \Generator<string, Tag>
     */
    public function getTags(): Generator {
        foreach ($this->list->values() as $tagName) {
            yield $tagName => $this->getTag($tagName);
        }
    }

    /**
     * Iterates tags marked for removal
     *
     * @return \Generator<string,Tag>
     */
    public function getRemovedTags(): Generator {
        foreach ($this->removedTags as $tagName) {
            if (count($this->list->getKeys($tagName)) == 0) {
                yield $tagName => new Tag($tagName);
            }
        }
    }

    /**
     * Iterates keys marked for removal
     *
     * @return \Generator<string,Key>
     */
    public function getRemovedKeys(): Generator {
        foreach ($this->removedKeys as $keyName) {
            if (count($this->list->get($keyName)) == 0) {
                yield $keyName => new Key($keyName);
            }
        }
    }

    /**
     * Adds Tag Data to Shared List
     * Used to load tag from the cache
     *
     * @param Tag $tag
     * @return self
     */
    public function loadTag(Tag $tag): self {
        foreach ($tag as $key) {
            $this->list->set($key->getLabel(), $tag->getLabel());
        }
        return $this;
    }

    /**
     * Adds Tag Data to Shared List
     *
     *
     * @param Key $key
     * @return self
     */
    public function loadKey(Key $key): self {
        foreach ($key as $tag) {
            $this->list->set($key->getLabel(), $tag->getLabel());
        }
        return $this;
    }

    ////////////////////////////   Helpers   ////////////////////////////

    /**
     * Set Tag Properties
     *
     * @param Tag $instance
     * @param array $properties
     * @return Tag
     */
    private function setTagProperties(Tag $instance, array $properties): Tag {
        $h = &$this->tagManager;
        return $h($instance, $properties);
    }

    /**
     * Set Key properties (the hacky way)
     *
     * @param Key $instance
     * @param array $properties
     * @return Key
     */
    private function setKeyProperties(Key $instance, array $properties): Key {
        $h = &$this->keyManager;
        return $h($instance, $properties);
    }

    /**
     * Exports all the objects
     *
     * @return array
     */
    public function toArray(): array {
        return [
            Key::class => iterator_to_array($this->getKeys()),
            Tag::class => iterator_to_array($this->getTags()),
            'orphaned' => [
                Key::class => iterator_to_array($this->getRemovedKeys()),
                Tag::class => iterator_to_array($this->getRemovedTags())
            ]
        ];
    }

    ////////////////////////////   Interfaces   ////////////////////////////

    /** {@inheritdoc} */
    public function count(): int {
        return count($this->list);
    }

    /** {@inheritdoc} */
    protected function export(): array {
        return $this->toArray();
    }

    /** {@inheritdoc} */
    protected function import(array $array): void {
        $this->clear();
        $this->extract($array);
    }

    /** @return \Generator<string,string> */
    public function getIterator() {
        yield from $this->list;
    }

    /** {@inheritdoc} */
    public function __serialize() {
        return $this->compact('list');
    }

    /** {@inheritdoc} */
    public function __clone() {
        $this->list = clone $this->list;
    }

}
