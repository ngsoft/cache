<?php

declare(strict_types=1);

namespace NGSOFT\Cache;

/**
 * A CacheObject
 *
 * These are the values that are stored in the cache using the CachePool
 *  I created that method of storage to make it future proof as if it need other metadatas (for other features),
 *  I just need to add a property, also it makes it easier to lazy-load the datas
 */
class CacheObject {

    /** @var string */
    public $key;

    /** @var mixed */
    public $value;

    /** @var int|null */
    public $expiry = null;

    /** @var string[] */
    public $tags = [];

    /**
     * @param string $key The Namespaced key
     * @param mixed $value
     * @param int|null $expiry
     * @param array $tags
     */
    public function __construct(string $key, $value = null, int $expiry = null, array $tags = []) {

        $this->key = $key;
        $this->value = $value;
        $this->expiry = $expiry ?? 0;
        $this->tags = $tags;
    }

    /**
     * Exports object as an array
     *
     * @return array
     */
    public function toArray(): array {
        return [
            'key' => $this->key,
            'expiry' => $this->expiry,
            'tags' => array_values($this->tags),
            'value' => $this->value
        ];
    }

    /** {@inheritdoc} */
    public static function __set_state($data) {
        static $obj;
        if (!$obj) $obj = new static('key');
        //we gain some microseconds when cloning multiple times compared to instantiating
        $c = clone $obj;
        $c->key = $data['key'] ?? '';
        $c->value = $data['value'] ?? null;
        $c->expiry = $data['expiry'] ?? null;
        $c->tags = $data['tags'] ?? [];
        return $c;
    }

    /** {@inheritdoc} */
    public function __unserialize(array $data) {
        $this->key = $data['k'] ?? '';
        $this->value = $data['v'] ?? null;
        $this->expiry = $data['e'] ?? null;
        $this->tags = $data['t'] ?? [];
    }

    /** {@inheritdoc} */
    public function __serialize() {
        return [
            'k' => $this->key,
            'v' => $this->value,
            'e' => $this->expiry,
            't' => $this->tags
        ];
    }

}
