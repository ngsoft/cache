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

    use CacheUtils;
    use Exportable;

    /** @var SharedList */
    private $list;

    /** @var array */
    private $orphaned;

    /** @var Closure */
    private $tagManager;

    /** @var Closure */
    private $keyManager;

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
        $this->orphaned = [Tag::class => [], Key::class => []];
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
        //keep track of the tags marked for removal
        unset($this->orphaned[Tag::class][$tag], $this->orphaned[Key::class][$key]);
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
        //keep track of the tags marked for removal
        if (count($this->list->getKeys($tag)) == 0) {
            $this->orphaned[Tag::class][$tag] = $tag;
        }
        if (count($this->list->get($key)) == 0) {
            $this->orphaned[Key::class][$key] = $key;
        }
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
        foreach ($this->orphaned[Tag::class] as $tagName) {
            yield $tagName => new Tag($tagName);
        }
    }

    /**
     * Iterates keys marked for removal
     *
     * @return \Generator<string,Key>
     */
    public function getRemovedKeys(): Generator {
        foreach ($this->orphaned[Key::class] as $keyName) {
            yield $keyName => new Key($keyName);
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
        $result = [
            Key::class => [],
            Tag::class => [],
            'orphaned' => $this->orphaned
        ];
        $keys = &$result[Key::class];
        $tags = &$result[Tag::class];
        foreach ($this->list->keys() as $keyName) {
            $keys[$keyName] = $this->getKey($keyName);
        }

        foreach ($this->list->values() as $tagName) {
            $tags[$tagName] = $this->getTag($tagName);
        }
        return $result;
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
