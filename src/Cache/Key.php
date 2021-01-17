<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

/**
 * A Key Item
 */
class Key extends Tag {

    /** @var string */
    protected $label;

    /** @var Tag[] */
    protected $items = [];

    /** @var string */
    protected $type = 'key';

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

    protected function import(array $array): void {
        $this->label = $array['l'] ?? $this->label;
        $i = $array['i'] ?? [];
        $this->clear();
        foreach ($i as $v) {
            $this->items[$v] = new Tag($v);
        }
    }

    public function getItems(): array {
        return $this->items;
    }

    /**
     * @return \Generator|Tag[]
     */
    public function getIterator() {
        foreach ($this->items as $item) {
            yield $item->getLabel() => $item;
        }
    }

}
