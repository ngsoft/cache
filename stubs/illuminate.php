<?php

declare(strict_types=1);

namespace Illuminate\Contracts\Cache {

    interface Store
    {

        /**
         * Retrieve an item from the cache by key.
         *
         * @param  string|array  $key
         * @return mixed
         */
        public function get($key);

        /**
         * Retrieve multiple items from the cache by key.
         *
         * Items not found in the cache will have a null value.
         *
         * @param  array  $keys
         * @return array
         */
        public function many(array $keys);

        /**
         * Store an item in the cache for a given number of seconds.
         *
         * @param  string  $key
         * @param  mixed  $value
         * @param  int  $seconds
         * @return bool
         */
        public function put($key, $value, $seconds);

        /**
         * Store multiple items in the cache for a given number of seconds.
         *
         * @param  array  $values
         * @param  int  $seconds
         * @return bool
         */
        public function putMany(array $values, $seconds);

        /**
         * Increment the value of an item in the cache.
         *
         * @param  string  $key
         * @param  mixed  $value
         * @return int|bool
         */
        public function increment($key, $value = 1);

        /**
         * Decrement the value of an item in the cache.
         *
         * @param  string  $key
         * @param  mixed  $value
         * @return int|bool
         */
        public function decrement($key, $value = 1);

        /**
         * Store an item in the cache indefinitely.
         *
         * @param  string  $key
         * @param  mixed  $value
         * @return bool
         */
        public function forever($key, $value);

        /**
         * Remove an item from the cache.
         *
         * @param  string  $key
         * @return bool
         */
        public function forget($key);

        /**
         * Remove all items from the cache.
         *
         * @return bool
         */
        public function flush();

        /**
         * Get the cache key prefix.
         *
         * @return string
         */
        public function getPrefix();
    }

    interface LockProvider
    {

        /**
         * Get a lock instance.
         *
         * @param  string  $name
         * @param  int  $seconds
         * @param  string|null  $owner
         * @return \Illuminate\Contracts\Cache\Lock
         */
        public function lock($name, $seconds = 0, $owner = null);

        /**
         * Restore a lock instance using the owner identifier.
         *
         * @param  string  $name
         * @param  string  $owner
         * @return \Illuminate\Contracts\Cache\Lock
         */
        public function restoreLock($name, $owner);
    }

    interface Lock
    {

        /**
         * Attempt to acquire the lock.
         *
         * @param  callable|null  $callback
         * @return mixed
         */
        public function get($callback = null);

        /**
         * Attempt to acquire the lock for the given number of seconds.
         *
         * @param  int  $seconds
         * @param  callable|null  $callback
         * @return mixed
         */
        public function block($seconds, $callback = null);

        /**
         * Release the lock.
         *
         * @return bool
         */
        public function release();

        /**
         * Returns the current owner of the lock.
         *
         * @return string
         */
        public function owner();

        /**
         * Releases this lock in disregard of ownership.
         *
         * @return void
         */
        public function forceRelease();
    }

}

namespace Illuminate\Cache {

    trait HasCacheLock
    {

        /**
         * Get a lock instance.
         *
         * @param  string  $name
         * @param  int  $seconds
         * @param  string|null  $owner
         * @return \Illuminate\Contracts\Cache\Lock
         */
        public function lock($name, $seconds = 0, $owner = null)
        {

        }

        /**
         * Restore a lock instance using the owner identifier.
         *
         * @param  string  $name
         * @param  string  $owner
         * @return \Illuminate\Contracts\Cache\Lock
         */
        public function restoreLock($name, $owner)
        {

        }

    }

}


