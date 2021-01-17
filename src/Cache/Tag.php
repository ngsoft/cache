<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

use Countable,
    Generator,
    IteratorAggregate,
    JsonSerializable,
    NGSOFT\Traits\Exportable,
    Stringable;

/**
 * A Tag Item
 */
class Tag implements IteratorAggregate, Countable, Stringable, JsonSerializable {

    use Exportable,
        CacheUtils {
        Exportable::jsonSerialize insteadof CacheUtils;
        Exportable::__debugInfo insteadof CacheUtils;
    }

    /** @var string */
    protected $label;

    /** @var Key[] */
    protected $items = [];

    /** @var string */
    protected $type = 'tag';

    /**
     * @param string $label Name
     */
    public function __construct(string $label) {
        $this->label = self::getValidName($label, $this->type);
    }

    /** {@inheritdoc} */
    public static function __set_state($array) {
        static $item;
        if (!$item) $item = new static(uniqid(''));
        if (!array_key_exists('label', $array) or!array_key_exists('items', $array)) throw new InvalidArgumentException();
        $c = clone $item;
        $c->label = $array['label'];
        $c->items = $array['items'];
        return $c;
    }

    /** @return string */
    public function getLabel(): string {
        if (empty($this->label)) throw new CacheException(sprintf('%s cannot be instantiated directly.', static::class));
        return $this->label;
    }

    public function getItems(): array {
        return $this->items;
    }

    /**
     * Clears Item list
     * @return void
     */
    public function clear(): void {
        $this->items = [];
    }

    /**
     * @return int
     */
    public function count() {
        return count($this->items);
    }

    /**
     * @return Generator|Key[]
     */
    public function getIterator() {

        foreach ($this->items as $item) {
            yield $item->getLabel() => $item;
        }
    }

    /** {@inheritdoc} */
    protected function export(): array {
        return [
            $this->label => $this->items
        ];
    }

    /** {@inheritdoc} */
    public function __serialize() {
        //compact keys and values to take less space
        return [
            'l' => $this->label,
            'i' => array_map(fn($i) => $i->getLabel(), array_values($this->items))
        ];
    }

    /** {@inheritdoc} */
    protected function import(array $array): void {
        $this->label = $array['l'] ?? $this->label;
        $i = $array['i'] ?? [];
        $this->clear();
        foreach ($i as $v) {
            $this->items[$v] = new Key($v);
        }
    }

    /** {@inheritdoc} */
    public function __toString() {
        return $this->label;
    }

}
