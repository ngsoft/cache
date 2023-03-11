# Documentation

## Table of Contents

| Method | Description |
|--------|-------------|
| [**ApcuDriver**](#ApcuDriver) |  |
| [ApcuDriver::isSupported](#ApcuDriverisSupported) |  |
| [ApcuDriver::__construct](#ApcuDriver__construct) |  |
| [ApcuDriver::clear](#ApcuDriverclear) |  |
| [ApcuDriver::delete](#ApcuDriverdelete) |  |
| [ApcuDriver::getCacheEntry](#ApcuDrivergetCacheEntry) |  |
| [ApcuDriver::has](#ApcuDriverhas) |  |
| [ApcuDriver::__debugInfo](#ApcuDriver__debugInfo) |  |
| [**ArrayDriver**](#ArrayDriver) |  |
| [ArrayDriver::__construct](#ArrayDriver__construct) |  |
| [ArrayDriver::clear](#ArrayDriverclear) | {@inheritdoc} |
| [ArrayDriver::purge](#ArrayDriverpurge) | Removes expired item entries if supported |
| [ArrayDriver::delete](#ArrayDriverdelete) | {@inheritdoc} |
| [ArrayDriver::getCacheEntry](#ArrayDrivergetCacheEntry) | {@inheritdoc} |
| [ArrayDriver::has](#ArrayDriverhas) | {@inheritdoc} |
| [ArrayDriver::__debugInfo](#ArrayDriver__debugInfo) |  |
| [**Cache**](#Cache) |  |
| [Cache::invalidateTags](#CacheinvalidateTags) | Invalidates cached items using tags. |
| [Cache::purge](#Cachepurge) | Removes expired item entries if supported |
| [Cache::get](#Cacheget) | Fetches a value from the pool or computes it if not found. |
| [Cache::increment](#Cacheincrement) | Increment the value of an item in the cache. |
| [Cache::decrement](#Cachedecrement) | Decrement the value of an item in the cache. |
| [Cache::add](#Cacheadd) | Adds data if it doesn&#039;t already exists |
| [Cache::clear](#Cacheclear) | {@inheritdoc} |
| [Cache::commit](#Cachecommit) | {@inheritdoc} |
| [Cache::deleteItem](#CachedeleteItem) | {@inheritdoc} |
| [Cache::deleteItems](#CachedeleteItems) | {@inheritdoc} |
| [Cache::getItem](#CachegetItem) | {@inheritdoc} |
| [Cache::getItems](#CachegetItems) | {@inheritdoc} |
| [Cache::hasItem](#CachehasItem) | {@inheritdoc} |
| [Cache::save](#Cachesave) | {@inheritdoc} |
| [Cache::saveDeferred](#CachesaveDeferred) | {@inheritdoc} |
| [Cache::lock](#Cachelock) | {@inheritdoc} |
| [Cache::setPrefix](#CachesetPrefix) | Change cache prefix |
| [Cache::getDriver](#CachegetDriver) | Access Cache driver directly |
| [Cache::invalidate](#Cacheinvalidate) | Increase prefix version, invalidating all prefixed entries |
| [**CacheEntry**](#CacheEntry) |  |
| [CacheEntry::__construct](#CacheEntry__construct) |  |
| [CacheEntry::getCacheItem](#CacheEntrygetCacheItem) |  |
| [CacheEntry::isHit](#CacheEntryisHit) |  |
| [CacheEntry::create](#CacheEntrycreate) |  |
| [CacheEntry::createEmpty](#CacheEntrycreateEmpty) |  |
| [CacheEntry::__serialize](#CacheEntry__serialize) |  |
| [CacheEntry::__unserialize](#CacheEntry__unserialize) |  |
| [CacheEntry::__toString](#CacheEntry__toString) |  |
| [**CacheError**](#CacheError) |  |
| [**CacheEvent**](#CacheEvent) |  |
| [CacheEvent::__construct](#CacheEvent__construct) |  |
| [CacheEvent::getCachePool](#CacheEventgetCachePool) |  |
| [**CacheHit**](#CacheHit) |  |
| [CacheHit::__construct](#CacheHit__construct) |  |
| [**CacheItem**](#CacheItem) | A Cache Item |
| [CacheItem::validateKey](#CacheItemvalidateKey) |  |
| [CacheItem::create](#CacheItemcreate) |  |
| [CacheItem::__construct](#CacheItem__construct) |  |
| [CacheItem::tag](#CacheItemtag) | Adds a tag to a cache item. |
| [CacheItem::getMetadata](#CacheItemgetMetadata) | Returns a list of metadata info that were saved alongside with the cached value. |
| [CacheItem::expiresAfter](#CacheItemexpiresAfter) | {@inheritdoc} |
| [CacheItem::expiresAt](#CacheItemexpiresAt) | {@inheritdoc} |
| [CacheItem::get](#CacheItemget) | {@inheritdoc} |
| [CacheItem::getKey](#CacheItemgetKey) | {@inheritdoc} |
| [CacheItem::isHit](#CacheItemisHit) | {@inheritdoc} |
| [CacheItem::set](#CacheItemset) | {@inheritdoc} |
| [CacheItem::__clone](#CacheItem__clone) | {@inheritdoc} |
| [CacheItem::__debugInfo](#CacheItem__debugInfo) |  |
| [**CacheMiss**](#CacheMiss) |  |
| [**CachePool**](#CachePool) | A PSR-6 cache pool |
| [CachePool::__construct](#CachePool__construct) |  |
| [CachePool::__destruct](#CachePool__destruct) |  |
| [CachePool::__debugInfo](#CachePool__debugInfo) |  |
| [CachePool::setLogger](#CachePoolsetLogger) |  |
| [CachePool::invalidateTags](#CachePoolinvalidateTags) | Invalidates cached items using tags. |
| [CachePool::purge](#CachePoolpurge) | Removes expired item entries if supported |
| [CachePool::get](#CachePoolget) | Fetches a value from the pool or computes it if not found. |
| [CachePool::increment](#CachePoolincrement) | Increment the value of an item in the cache. |
| [CachePool::decrement](#CachePooldecrement) | Decrement the value of an item in the cache. |
| [CachePool::add](#CachePooladd) | Adds data if it doesn&#039;t already exists |
| [CachePool::clear](#CachePoolclear) | {@inheritdoc} |
| [CachePool::commit](#CachePoolcommit) | {@inheritdoc} |
| [CachePool::deleteItem](#CachePooldeleteItem) | {@inheritdoc} |
| [CachePool::deleteItems](#CachePooldeleteItems) | {@inheritdoc} |
| [CachePool::getItem](#CachePoolgetItem) | {@inheritdoc} |
| [CachePool::getItems](#CachePoolgetItems) | {@inheritdoc} |
| [CachePool::hasItem](#CachePoolhasItem) | {@inheritdoc} |
| [CachePool::save](#CachePoolsave) | {@inheritdoc} |
| [CachePool::saveDeferred](#CachePoolsaveDeferred) | {@inheritdoc} |
| [CachePool::lock](#CachePoollock) | {@inheritdoc} |
| [**ChainDriver**](#ChainDriver) |  |
| [ChainDriver::__construct](#ChainDriver__construct) |  |
| [ChainDriver::setDefaultLifetime](#ChainDriversetDefaultLifetime) | set the default ttl |
| [ChainDriver::count](#ChainDrivercount) |  |
| [ChainDriver::getIterator](#ChainDrivergetIterator) |  |
| [ChainDriver::getReverseIterator](#ChainDrivergetReverseIterator) |  |
| [ChainDriver::set](#ChainDriverset) | Persists data in the cache |
| [ChainDriver::purge](#ChainDriverpurge) | Removes expired item entries if supported |
| [ChainDriver::clear](#ChainDriverclear) |  |
| [ChainDriver::delete](#ChainDriverdelete) |  |
| [ChainDriver::getCacheEntry](#ChainDrivergetCacheEntry) |  |
| [ChainDriver::has](#ChainDriverhas) |  |
| [ChainDriver::__debugInfo](#ChainDriver__debugInfo) |  |
| [**DoctrineCacheProvider**](#DoctrineCacheProvider) |  |
| [DoctrineCacheProvider::__construct](#DoctrineCacheProvider__construct) |  |
| [DoctrineCacheProvider::getNamespace](#DoctrineCacheProvidergetNamespace) |  |
| [DoctrineCacheProvider::setNamespace](#DoctrineCacheProvidersetNamespace) |  |
| [DoctrineCacheProvider::setLogger](#DoctrineCacheProvidersetLogger) | {@inheritdoc} |
| [DoctrineCacheProvider::contains](#DoctrineCacheProvidercontains) | {@inheritdoc} |
| [DoctrineCacheProvider::delete](#DoctrineCacheProviderdelete) | {@inheritdoc} |
| [DoctrineCacheProvider::fetch](#DoctrineCacheProviderfetch) | {@inheritdoc} |
| [DoctrineCacheProvider::save](#DoctrineCacheProvidersave) | {@inheritdoc} |
| [DoctrineCacheProvider::deleteAll](#DoctrineCacheProviderdeleteAll) | {@inheritdoc} |
| [DoctrineCacheProvider::flushAll](#DoctrineCacheProviderflushAll) | {@inheritdoc} |
| [DoctrineCacheProvider::deleteMultiple](#DoctrineCacheProviderdeleteMultiple) | {@inheritdoc} |
| [DoctrineCacheProvider::fetchMultiple](#DoctrineCacheProviderfetchMultiple) | {@inheritdoc} |
| [DoctrineCacheProvider::saveMultiple](#DoctrineCacheProvidersaveMultiple) | {@inheritdoc} |
| [DoctrineCacheProvider::getStats](#DoctrineCacheProvidergetStats) |  |
| [**DoctrineDriver**](#DoctrineDriver) |  |
| [DoctrineDriver::__construct](#DoctrineDriver__construct) |  |
| [DoctrineDriver::clear](#DoctrineDriverclear) |  |
| [DoctrineDriver::delete](#DoctrineDriverdelete) |  |
| [DoctrineDriver::getCacheEntry](#DoctrineDrivergetCacheEntry) |  |
| [DoctrineDriver::has](#DoctrineDriverhas) |  |
| [DoctrineDriver::__debugInfo](#DoctrineDriver__debugInfo) |  |
| [**FileCache**](#FileCache) |  |
| [FileCache::invalidateTags](#FileCacheinvalidateTags) | Invalidates cached items using tags. |
| [FileCache::purge](#FileCachepurge) | Removes expired item entries if supported |
| [FileCache::get](#FileCacheget) | Fetches a value from the pool or computes it if not found. |
| [FileCache::increment](#FileCacheincrement) | Increment the value of an item in the cache. |
| [FileCache::decrement](#FileCachedecrement) | Decrement the value of an item in the cache. |
| [FileCache::add](#FileCacheadd) | Adds data if it doesn&#039;t already exists |
| [FileCache::clear](#FileCacheclear) | {@inheritdoc} |
| [FileCache::commit](#FileCachecommit) | {@inheritdoc} |
| [FileCache::deleteItem](#FileCachedeleteItem) | {@inheritdoc} |
| [FileCache::deleteItems](#FileCachedeleteItems) | {@inheritdoc} |
| [FileCache::getItem](#FileCachegetItem) | {@inheritdoc} |
| [FileCache::getItems](#FileCachegetItems) | {@inheritdoc} |
| [FileCache::hasItem](#FileCachehasItem) | {@inheritdoc} |
| [FileCache::save](#FileCachesave) | {@inheritdoc} |
| [FileCache::saveDeferred](#FileCachesaveDeferred) | {@inheritdoc} |
| [FileCache::lock](#FileCachelock) | {@inheritdoc} |
| [FileCache::setPrefix](#FileCachesetPrefix) | Change cache prefix |
| [FileCache::getDriver](#FileCachegetDriver) | Access Cache driver directly |
| [FileCache::invalidate](#FileCacheinvalidate) | Increase prefix version, invalidating all prefixed entries |
| [**FileDriver**](#FileDriver) | The oldest cache driver that store binary datas |
| [FileDriver::onWindows](#FileDriveronWindows) |  |
| [FileDriver::__construct](#FileDriver__construct) |  |
| [FileDriver::__destruct](#FileDriver__destruct) |  |
| [FileDriver::purge](#FileDriverpurge) | Removes expired item entries if supported |
| [FileDriver::clear](#FileDriverclear) |  |
| [FileDriver::delete](#FileDriverdelete) |  |
| [FileDriver::getCacheEntry](#FileDrivergetCacheEntry) |  |
| [FileDriver::has](#FileDriverhas) |  |
| [FileDriver::__debugInfo](#FileDriver__debugInfo) |  |
| [**IlluminateDriver**](#IlluminateDriver) |  |
| [IlluminateDriver::__construct](#IlluminateDriver__construct) |  |
| [IlluminateDriver::clear](#IlluminateDriverclear) |  |
| [IlluminateDriver::delete](#IlluminateDriverdelete) |  |
| [IlluminateDriver::getCacheEntry](#IlluminateDrivergetCacheEntry) |  |
| [IlluminateDriver::has](#IlluminateDriverhas) |  |
| [IlluminateDriver::__debugInfo](#IlluminateDriver__debugInfo) |  |
| [**InvalidArgument**](#InvalidArgument) |  |
| [**JsonDriver**](#JsonDriver) | A driver that can be used for Cli applicationsCan store data inside a json config file for example |
| [JsonDriver::__construct](#JsonDriver__construct) |  |
| [JsonDriver::purge](#JsonDriverpurge) | Removes expired item entries if supported |
| [JsonDriver::clear](#JsonDriverclear) |  |
| [JsonDriver::delete](#JsonDriverdelete) |  |
| [JsonDriver::getCacheEntry](#JsonDrivergetCacheEntry) |  |
| [JsonDriver::has](#JsonDriverhas) |  |
| [JsonDriver::count](#JsonDrivercount) |  |
| [JsonDriver::__debugInfo](#JsonDriver__debugInfo) |  |
| [**KeyDeleted**](#KeyDeleted) |  |
| [**KeySaved**](#KeySaved) |  |
| [KeySaved::__construct](#KeySaved__construct) |  |
| [**LaravelStore**](#LaravelStore) |  |
| [LaravelStore::__construct](#LaravelStore__construct) |  |
| [LaravelStore::setLogger](#LaravelStoresetLogger) | {@inheritdoc} |
| [LaravelStore::increment](#LaravelStoreincrement) | {@inheritdoc} |
| [LaravelStore::decrement](#LaravelStoredecrement) | {@inheritdoc} |
| [LaravelStore::flush](#LaravelStoreflush) | {@inheritdoc} |
| [LaravelStore::forever](#LaravelStoreforever) | {@inheritdoc} |
| [LaravelStore::forget](#LaravelStoreforget) | {@inheritdoc} |
| [LaravelStore::get](#LaravelStoreget) | {@inheritdoc} |
| [LaravelStore::getPrefix](#LaravelStoregetPrefix) | {@inheritdoc} |
| [LaravelStore::many](#LaravelStoremany) | {@inheritdoc} |
| [LaravelStore::put](#LaravelStoreput) | {@inheritdoc} |
| [LaravelStore::putMany](#LaravelStoreputMany) | {@inheritdoc} |
| [**NullDriver**](#NullDriver) |  |
| [NullDriver::clear](#NullDriverclear) |  |
| [NullDriver::delete](#NullDriverdelete) |  |
| [NullDriver::getCacheEntry](#NullDrivergetCacheEntry) |  |
| [NullDriver::has](#NullDriverhas) |  |
| [**PDOAdapter**](#PDOAdapter) |  |
| [PDOAdapter::__construct](#PDOAdapter__construct) |  |
| [PDOAdapter::read](#PDOAdapterread) |  |
| [PDOAdapter::query](#PDOAdapterquery) |  |
| [**PHPCache**](#PHPCache) | A preconfigured cache poolChains ArrayDriver, ApcuDriver, PhpDriver |
| [PHPCache::__construct](#PHPCache__construct) |  |
| [PHPCache::appendDriver](#PHPCacheappendDriver) | Put a driver at the end of the chain |
| [PHPCache::prependDriver](#PHPCacheprependDriver) | Put a driver at the beginning of the chain |
| [**PhpDriver**](#PhpDriver) |  |
| [PhpDriver::opCacheSupported](#PhpDriveropCacheSupported) |  |
| [PhpDriver::onWindows](#PhpDriveronWindows) |  |
| [PhpDriver::__construct](#PhpDriver__construct) |  |
| [PhpDriver::__destruct](#PhpDriver__destruct) |  |
| [PhpDriver::purge](#PhpDriverpurge) | Removes expired item entries if supported |
| [PhpDriver::clear](#PhpDriverclear) |  |
| [PhpDriver::delete](#PhpDriverdelete) |  |
| [PhpDriver::getCacheEntry](#PhpDrivergetCacheEntry) |  |
| [PhpDriver::has](#PhpDriverhas) |  |
| [PhpDriver::__debugInfo](#PhpDriver__debugInfo) |  |
| [**PSR16Driver**](#PSR16Driver) |  |
| [PSR16Driver::__construct](#PSR16Driver__construct) |  |
| [PSR16Driver::clear](#PSR16Driverclear) |  |
| [PSR16Driver::delete](#PSR16Driverdelete) |  |
| [PSR16Driver::getCacheEntry](#PSR16DrivergetCacheEntry) |  |
| [PSR16Driver::has](#PSR16Driverhas) |  |
| [PSR16Driver::__debugInfo](#PSR16Driver__debugInfo) |  |
| [**PSR6Driver**](#PSR6Driver) |  |
| [PSR6Driver::__construct](#PSR6Driver__construct) |  |
| [PSR6Driver::getCacheEntry](#PSR6DrivergetCacheEntry) |  |
| [PSR6Driver::clear](#PSR6Driverclear) |  |
| [PSR6Driver::delete](#PSR6Driverdelete) |  |
| [PSR6Driver::has](#PSR6Driverhas) |  |
| [PSR6Driver::__debugInfo](#PSR6Driver__debugInfo) |  |
| [**ReactCache**](#ReactCache) |  |
| [ReactCache::__construct](#ReactCache__construct) |  |
| [ReactCache::setLogger](#ReactCachesetLogger) | {@inheritdoc} |
| [ReactCache::increment](#ReactCacheincrement) | Increment the value of an item in the cache. |
| [ReactCache::decrement](#ReactCachedecrement) | Decrement the value of an item in the cache. |
| [ReactCache::add](#ReactCacheadd) | Adds data if it doesn&#039;t already exists |
| [ReactCache::clear](#ReactCacheclear) | {@inheritdoc} |
| [ReactCache::delete](#ReactCachedelete) | {@inheritdoc} |
| [ReactCache::has](#ReactCachehas) | {@inheritdoc} |
| [ReactCache::get](#ReactCacheget) | {@inheritdoc} |
| [ReactCache::set](#ReactCacheset) | {@inheritdoc} |
| [ReactCache::deleteMultiple](#ReactCachedeleteMultiple) | {@inheritdoc} |
| [ReactCache::getMultiple](#ReactCachegetMultiple) | {@inheritdoc} |
| [ReactCache::setMultiple](#ReactCachesetMultiple) | {@inheritdoc} |
| [**SimpleCachePool**](#SimpleCachePool) | PSR-6 to PSR-16 Adapter |
| [SimpleCachePool::__construct](#SimpleCachePool__construct) |  |
| [SimpleCachePool::getCachePool](#SimpleCachePoolgetCachePool) | {@inheritdoc} |
| [SimpleCachePool::increment](#SimpleCachePoolincrement) | Increment the value of an item in the cache. |
| [SimpleCachePool::decrement](#SimpleCachePooldecrement) | Decrement the value of an item in the cache. |
| [SimpleCachePool::add](#SimpleCachePooladd) | Adds data if it doesn&#039;t already exists |
| [SimpleCachePool::clear](#SimpleCachePoolclear) | {@inheritdoc} |
| [SimpleCachePool::delete](#SimpleCachePooldelete) | {@inheritdoc} |
| [SimpleCachePool::deleteMultiple](#SimpleCachePooldeleteMultiple) | {@inheritdoc} |
| [SimpleCachePool::get](#SimpleCachePoolget) | {@inheritdoc} |
| [SimpleCachePool::getMultiple](#SimpleCachePoolgetMultiple) | {@inheritdoc} |
| [SimpleCachePool::has](#SimpleCachePoolhas) | {@inheritdoc} |
| [SimpleCachePool::set](#SimpleCachePoolset) | {@inheritdoc} |
| [SimpleCachePool::setMultiple](#SimpleCachePoolsetMultiple) | {@inheritdoc} |
| [SimpleCachePool::__debugInfo](#SimpleCachePool__debugInfo) |  |
| [**SQLite3Adapter**](#SQLite3Adapter) |  |
| [SQLite3Adapter::__construct](#SQLite3Adapter__construct) |  |
| [SQLite3Adapter::read](#SQLite3Adapterread) |  |
| [SQLite3Adapter::query](#SQLite3Adapterquery) |  |
| [**Sqlite3Driver**](#Sqlite3Driver) |  |
| [Sqlite3Driver::__construct](#Sqlite3Driver__construct) |  |
| [Sqlite3Driver::purge](#Sqlite3Driverpurge) | Removes expired item entries if supported |
| [Sqlite3Driver::clear](#Sqlite3Driverclear) |  |
| [Sqlite3Driver::delete](#Sqlite3Driverdelete) |  |
| [Sqlite3Driver::getCacheEntry](#Sqlite3DrivergetCacheEntry) |  |
| [Sqlite3Driver::has](#Sqlite3Driverhas) |  |
| [Sqlite3Driver::__debugInfo](#Sqlite3Driver__debugInfo) |  |

## ApcuDriver





* Full name: \NGSOFT\Cache\Drivers\ApcuDriver
* Parent class: \NGSOFT\Cache\Drivers\BaseDriver


### ApcuDriver::isSupported



```php
ApcuDriver::isSupported(  ): bool
```



* This method is **static**.

**Return Value:**





---
### ApcuDriver::__construct



```php
ApcuDriver::__construct(  ): mixed
```





**Return Value:**





---
### ApcuDriver::clear



```php
ApcuDriver::clear(  ): bool
```





**Return Value:**





---
### ApcuDriver::delete



```php
ApcuDriver::delete( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### ApcuDriver::getCacheEntry



```php
ApcuDriver::getCacheEntry( string key ): \NGSOFT\Cache\CacheEntry
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### ApcuDriver::has



```php
ApcuDriver::has( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### ApcuDriver::__debugInfo



```php
ApcuDriver::__debugInfo(  ): array
```





**Return Value:**





---
## ArrayDriver





* Full name: \NGSOFT\Cache\Drivers\ArrayDriver
* Parent class: \NGSOFT\Cache\Drivers\BaseDriver


### ArrayDriver::__construct



```php
ArrayDriver::__construct( int size = self::DEFAULT_SIZE ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `size` | **int** |  |


**Return Value:**





---
### ArrayDriver::clear

{@inheritdoc}

```php
ArrayDriver::clear(  ): bool
```





**Return Value:**





---
### ArrayDriver::purge

Removes expired item entries if supported

```php
ArrayDriver::purge(  ): void
```





**Return Value:**





---
### ArrayDriver::delete

{@inheritdoc}

```php
ArrayDriver::delete( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### ArrayDriver::getCacheEntry

{@inheritdoc}

```php
ArrayDriver::getCacheEntry( string key ): \NGSOFT\Cache\CacheEntry
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### ArrayDriver::has

{@inheritdoc}

```php
ArrayDriver::has( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### ArrayDriver::__debugInfo



```php
ArrayDriver::__debugInfo(  ): array
```





**Return Value:**





---
## Cache





* Full name: \NGSOFT\Facades\Cache
* Parent class: 


### Cache::invalidateTags

Invalidates cached items using tags.

```php
Cache::invalidateTags( string[]|string tags ): bool
```

When implemented on a PSR-6 pool, invalidation should not apply
to deferred items. Instead, they should be committed as usual.
This allows replacing old tagged values by new ones without
race conditions.

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `tags` | **string[]\|string** | An array of tags to invalidate |


**Return Value:**

True on success



---
### Cache::purge

Removes expired item entries if supported

```php
Cache::purge(  ): void
```



* This method is **static**.

**Return Value:**





---
### Cache::get

Fetches a value from the pool or computes it if not found.

```php
Cache::get( string key, mixed|\Closure default = null ): mixed
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `default` | **mixed\|\Closure** | if set the item will be saved with that value |


**Return Value:**





---
### Cache::increment

Increment the value of an item in the cache.

```php
Cache::increment( string key, int value = 1 ): int
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `value` | **int** |  |


**Return Value:**





---
### Cache::decrement

Decrement the value of an item in the cache.

```php
Cache::decrement( string key, int value = 1 ): int
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `value` | **int** |  |


**Return Value:**





---
### Cache::add

Adds data if it doesn't already exists

```php
Cache::add( string key, mixed|\Closure value ): bool
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `value` | **mixed\|\Closure** |  |


**Return Value:**

True if the data have been added, false otherwise



---
### Cache::clear

{@inheritdoc}

```php
Cache::clear(  ): bool
```



* This method is **static**.

**Return Value:**





---
### Cache::commit

{@inheritdoc}

```php
Cache::commit(  ): bool
```



* This method is **static**.

**Return Value:**





---
### Cache::deleteItem

{@inheritdoc}

```php
Cache::deleteItem( string key ): bool
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### Cache::deleteItems

{@inheritdoc}

```php
Cache::deleteItems( array keys ): bool
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `keys` | **array** |  |


**Return Value:**





---
### Cache::getItem

{@inheritdoc}

```php
Cache::getItem( string key ): \NGSOFT\Cache\Interfaces\TaggableCacheItem
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### Cache::getItems

{@inheritdoc}

```php
Cache::getItems( array keys = [] ): iterable
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `keys` | **array** |  |


**Return Value:**





---
### Cache::hasItem

{@inheritdoc}

```php
Cache::hasItem( string key ): bool
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### Cache::save

{@inheritdoc}

```php
Cache::save( \Psr\Cache\CacheItemInterface item ): bool
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `item` | **\Psr\Cache\CacheItemInterface** |  |


**Return Value:**





---
### Cache::saveDeferred

{@inheritdoc}

```php
Cache::saveDeferred( \Psr\Cache\CacheItemInterface item ): bool
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `item` | **\Psr\Cache\CacheItemInterface** |  |


**Return Value:**





---
### Cache::lock

{@inheritdoc}

```php
Cache::lock( string name, int|float seconds, string owner = '' ): \NGSOFT\Lock\LockStore
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `name` | **string** |  |
| `seconds` | **int\|float** |  |
| `owner` | **string** |  |


**Return Value:**





---
### Cache::setPrefix

Change cache prefix

```php
Cache::setPrefix( string prefix ): void
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `prefix` | **string** |  |


**Return Value:**





---
### Cache::getDriver

Access Cache driver directly

```php
Cache::getDriver(  ): \NGSOFT\Cache\Interfaces\CacheDriver
```



* This method is **static**.

**Return Value:**





---
### Cache::invalidate

Increase prefix version, invalidating all prefixed entries

```php
Cache::invalidate(  ): bool
```



* This method is **static**.

**Return Value:**





---
## CacheEntry





* Full name: \NGSOFT\Cache\CacheEntry
* This class implements: \Stringable


### CacheEntry::__construct



```php
CacheEntry::__construct( string key, int expiry, mixed value = null, array tags = [] ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `expiry` | **int** |  |
| `value` | **mixed** |  |
| `tags` | **array** |  |


**Return Value:**





---
### CacheEntry::getCacheItem



```php
CacheEntry::getCacheItem( string key ): \NGSOFT\Cache\CacheItem
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### CacheEntry::isHit



```php
CacheEntry::isHit(  ): bool
```





**Return Value:**





---
### CacheEntry::create



```php
CacheEntry::create( string key, int expiry, mixed value, array tags ): static
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `expiry` | **int** |  |
| `value` | **mixed** |  |
| `tags` | **array** |  |


**Return Value:**





---
### CacheEntry::createEmpty



```php
CacheEntry::createEmpty( string key ): static
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### CacheEntry::__serialize



```php
CacheEntry::__serialize(  ): array
```





**Return Value:**





---
### CacheEntry::__unserialize



```php
CacheEntry::__unserialize( array data ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `data` | **array** |  |


**Return Value:**





---
### CacheEntry::__toString



```php
CacheEntry::__toString(  ): string
```





**Return Value:**





---
## CacheError





* Full name: \NGSOFT\Cache\Exceptions\CacheError
* Parent class: 
* This class implements: \Psr\Cache\CacheException, \Psr\SimpleCache\CacheException


## CacheEvent





* Full name: \NGSOFT\Cache\Events\CacheEvent
* This class implements: \Psr\EventDispatcher\StoppableEventInterface


### CacheEvent::__construct



```php
CacheEvent::__construct( \Psr\Cache\CacheItemPoolInterface cachePool, string key ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `cachePool` | **\Psr\Cache\CacheItemPoolInterface** |  |
| `key` | **string** |  |


**Return Value:**





---
### CacheEvent::getCachePool



```php
CacheEvent::getCachePool(  ): \Psr\Cache\CacheItemPoolInterface
```





**Return Value:**





---
## CacheHit





* Full name: \NGSOFT\Cache\Events\CacheHit
* Parent class: \NGSOFT\Cache\Events\CacheEvent


### CacheHit::__construct



```php
CacheHit::__construct( \Psr\Cache\CacheItemPoolInterface cachePool, string key, mixed value ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `cachePool` | **\Psr\Cache\CacheItemPoolInterface** |  |
| `key` | **string** |  |
| `value` | **mixed** |  |


**Return Value:**





---
## CacheItem

A Cache Item



* Full name: \NGSOFT\Cache\CacheItem
* This class implements: \NGSOFT\Cache\Interfaces\TaggableCacheItem, \NGSOFT\Cache, \Stringable


### CacheItem::validateKey



```php
CacheItem::validateKey( mixed key ): void
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **mixed** |  |


**Return Value:**





---
### CacheItem::create



```php
CacheItem::create( string key, ?array metadata = null ): static
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `metadata` | **?array** |  |


**Return Value:**





---
### CacheItem::__construct



```php
CacheItem::__construct( string key, ?array metadata = null ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `metadata` | **?array** |  |


**Return Value:**





---
### CacheItem::tag

Adds a tag to a cache item.

```php
CacheItem::tag( string|iterable tags ): static
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `tags` | **string\|iterable** | A tag or array of tags |


**Return Value:**





---
### CacheItem::getMetadata

Returns a list of metadata info that were saved alongside with the cached value.

```php
CacheItem::getMetadata(  ): array
```





**Return Value:**





---
### CacheItem::expiresAfter

{@inheritdoc}

```php
CacheItem::expiresAfter( int|\DateInterval|null time ): static
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `time` | **int\|\DateInterval\|null** |  |


**Return Value:**





---
### CacheItem::expiresAt

{@inheritdoc}

```php
CacheItem::expiresAt( ?\DateTimeInterface expiration ): static
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `expiration` | **?\DateTimeInterface** |  |


**Return Value:**





---
### CacheItem::get

{@inheritdoc}

```php
CacheItem::get(  ): mixed
```





**Return Value:**





---
### CacheItem::getKey

{@inheritdoc}

```php
CacheItem::getKey(  ): string
```





**Return Value:**





---
### CacheItem::isHit

{@inheritdoc}

```php
CacheItem::isHit(  ): bool
```





**Return Value:**





---
### CacheItem::set

{@inheritdoc}

```php
CacheItem::set( mixed value ): static
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `value` | **mixed** |  |


**Return Value:**





---
### CacheItem::__clone

{@inheritdoc}

```php
CacheItem::__clone(  ): void
```





**Return Value:**





---
### CacheItem::__debugInfo



```php
CacheItem::__debugInfo(  ): array
```





**Return Value:**





---
## CacheMiss





* Full name: \NGSOFT\Cache\Events\CacheMiss
* Parent class: \NGSOFT\Cache\Events\CacheEvent


## CachePool

A PSR-6 cache pool



* Full name: \NGSOFT\Cache\CachePool
* This class implements: \Stringable, \Psr\Log\LoggerAwareInterface, \Psr\Cache\CacheItemPoolInterface, \NGSOFT\Cache, \NGSOFT\Lock\LockProvider


### CachePool::__construct



```php
CachePool::__construct( \NGSOFT\Cache\Interfaces\CacheDriver driver, string prefix = '', int defaultLifetime, ?\Psr\Log\LoggerInterface logger = null, ?\Psr\EventDispatcher\EventDispatcherInterface eventDispatcher = null ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `driver` | **\NGSOFT\Cache\Interfaces\CacheDriver** |  |
| `prefix` | **string** |  |
| `defaultLifetime` | **int** |  |
| `logger` | **?\Psr\Log\LoggerInterface** |  |
| `eventDispatcher` | **?\Psr\EventDispatcher\EventDispatcherInterface** |  |


**Return Value:**





---
### CachePool::__destruct



```php
CachePool::__destruct(  ): mixed
```





**Return Value:**





---
### CachePool::__debugInfo



```php
CachePool::__debugInfo(  ): array
```





**Return Value:**





---
### CachePool::setLogger



```php
CachePool::setLogger( \Psr\Log\LoggerInterface logger ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `logger` | **\Psr\Log\LoggerInterface** |  |


**Return Value:**





---
### CachePool::invalidateTags

Invalidates cached items using tags.

```php
CachePool::invalidateTags( string[]|string tags ): bool
```

When implemented on a PSR-6 pool, invalidation should not apply
to deferred items. Instead, they should be committed as usual.
This allows replacing old tagged values by new ones without
race conditions.


**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `tags` | **string[]\|string** | An array of tags to invalidate |


**Return Value:**

True on success



---
### CachePool::purge

Removes expired item entries if supported

```php
CachePool::purge(  ): void
```





**Return Value:**





---
### CachePool::get

Fetches a value from the pool or computes it if not found.

```php
CachePool::get( string key, mixed|\Closure default = null ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `default` | **mixed\|\Closure** | if set the item will be saved with that value |


**Return Value:**





---
### CachePool::increment

Increment the value of an item in the cache.

```php
CachePool::increment( string key, int value = 1 ): int
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `value` | **int** |  |


**Return Value:**





---
### CachePool::decrement

Decrement the value of an item in the cache.

```php
CachePool::decrement( string key, int value = 1 ): int
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `value` | **int** |  |


**Return Value:**





---
### CachePool::add

Adds data if it doesn't already exists

```php
CachePool::add( string key, mixed|\Closure value ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `value` | **mixed\|\Closure** |  |


**Return Value:**

True if the data have been added, false otherwise



---
### CachePool::clear

{@inheritdoc}

```php
CachePool::clear(  ): bool
```





**Return Value:**





---
### CachePool::commit

{@inheritdoc}

```php
CachePool::commit(  ): bool
```





**Return Value:**





---
### CachePool::deleteItem

{@inheritdoc}

```php
CachePool::deleteItem( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### CachePool::deleteItems

{@inheritdoc}

```php
CachePool::deleteItems( array keys ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `keys` | **array** |  |


**Return Value:**





---
### CachePool::getItem

{@inheritdoc}

```php
CachePool::getItem( string key ): \NGSOFT\Cache\Interfaces\TaggableCacheItem
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### CachePool::getItems

{@inheritdoc}

```php
CachePool::getItems( array keys = [] ): iterable
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `keys` | **array** |  |


**Return Value:**





---
### CachePool::hasItem

{@inheritdoc}

```php
CachePool::hasItem( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### CachePool::save

{@inheritdoc}

```php
CachePool::save( \Psr\Cache\CacheItemInterface item ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `item` | **\Psr\Cache\CacheItemInterface** |  |


**Return Value:**





---
### CachePool::saveDeferred

{@inheritdoc}

```php
CachePool::saveDeferred( \Psr\Cache\CacheItemInterface item ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `item` | **\Psr\Cache\CacheItemInterface** |  |


**Return Value:**





---
### CachePool::lock

{@inheritdoc}

```php
CachePool::lock( string name, int|float seconds, string owner = '' ): \NGSOFT\Lock\LockStore
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `name` | **string** |  |
| `seconds` | **int\|float** |  |
| `owner` | **string** |  |


**Return Value:**





---
## ChainDriver





* Full name: \NGSOFT\Cache\Drivers\ChainDriver
* Parent class: \NGSOFT\Cache\Drivers\BaseDriver
* This class implements: \Countable


### ChainDriver::__construct



```php
ChainDriver::__construct( iterable drivers ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `drivers` | **iterable** |  |


**Return Value:**





---
### ChainDriver::setDefaultLifetime

set the default ttl

```php
ChainDriver::setDefaultLifetime( int defaultLifetime ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `defaultLifetime` | **int** |  |


**Return Value:**





---
### ChainDriver::count



```php
ChainDriver::count(  ): int
```





**Return Value:**





---
### ChainDriver::getIterator



```php
ChainDriver::getIterator(  ): \Traversable
```





**Return Value:**





---
### ChainDriver::getReverseIterator



```php
ChainDriver::getReverseIterator( ?int current = null ): \Traversable
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `current` | **?int** |  |


**Return Value:**





---
### ChainDriver::set

Persists data in the cache

```php
ChainDriver::set( string key, mixed value, ?int ttl = null, string|array tags = [] ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `value` | **mixed** |  |
| `ttl` | **?int** | a value of 0 never expires, a null value uses the default value set in the driver |
| `tags` | **string\|array** |  |


**Return Value:**





---
### ChainDriver::purge

Removes expired item entries if supported

```php
ChainDriver::purge(  ): void
```





**Return Value:**





---
### ChainDriver::clear



```php
ChainDriver::clear(  ): bool
```





**Return Value:**





---
### ChainDriver::delete



```php
ChainDriver::delete( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### ChainDriver::getCacheEntry



```php
ChainDriver::getCacheEntry( string key ): \NGSOFT\Cache\CacheEntry
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### ChainDriver::has



```php
ChainDriver::has( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### ChainDriver::__debugInfo



```php
ChainDriver::__debugInfo(  ): array
```





**Return Value:**





---
## DoctrineCacheProvider





* Full name: \NGSOFT\Cache\Adapters\DoctrineCacheProvider
* Parent class: 
* This class implements: \NGSOFT\Cache, \Psr\Log\LoggerAwareInterface, \Stringable


### DoctrineCacheProvider::__construct



```php
DoctrineCacheProvider::__construct( \NGSOFT\Cache\Interfaces\CacheDriver driver, string prefix = '', int defaultLifetime ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `driver` | **\NGSOFT\Cache\Interfaces\CacheDriver** |  |
| `prefix` | **string** |  |
| `defaultLifetime` | **int** |  |


**Return Value:**





---
### DoctrineCacheProvider::getNamespace



```php
DoctrineCacheProvider::getNamespace(  ): string
```





**Return Value:**





---
### DoctrineCacheProvider::setNamespace



```php
DoctrineCacheProvider::setNamespace( mixed namespace ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `namespace` | **mixed** |  |


**Return Value:**





---
### DoctrineCacheProvider::setLogger

{@inheritdoc}

```php
DoctrineCacheProvider::setLogger( \Psr\Log\LoggerInterface logger ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `logger` | **\Psr\Log\LoggerInterface** |  |


**Return Value:**





---
### DoctrineCacheProvider::contains

{@inheritdoc}

```php
DoctrineCacheProvider::contains( mixed id ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | **mixed** |  |


**Return Value:**





---
### DoctrineCacheProvider::delete

{@inheritdoc}

```php
DoctrineCacheProvider::delete( mixed id ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | **mixed** |  |


**Return Value:**





---
### DoctrineCacheProvider::fetch

{@inheritdoc}

```php
DoctrineCacheProvider::fetch( mixed id ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | **mixed** |  |


**Return Value:**





---
### DoctrineCacheProvider::save

{@inheritdoc}

```php
DoctrineCacheProvider::save( mixed id, mixed data, mixed lifeTime ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | **mixed** |  |
| `data` | **mixed** |  |
| `lifeTime` | **mixed** |  |


**Return Value:**





---
### DoctrineCacheProvider::deleteAll

{@inheritdoc}

```php
DoctrineCacheProvider::deleteAll(  ): bool
```





**Return Value:**





---
### DoctrineCacheProvider::flushAll

{@inheritdoc}

```php
DoctrineCacheProvider::flushAll(  ): bool
```





**Return Value:**





---
### DoctrineCacheProvider::deleteMultiple

{@inheritdoc}

```php
DoctrineCacheProvider::deleteMultiple( array keys ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `keys` | **array** |  |


**Return Value:**





---
### DoctrineCacheProvider::fetchMultiple

{@inheritdoc}

```php
DoctrineCacheProvider::fetchMultiple( array keys ): array
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `keys` | **array** |  |


**Return Value:**





---
### DoctrineCacheProvider::saveMultiple

{@inheritdoc}

```php
DoctrineCacheProvider::saveMultiple( array keysAndValues, mixed lifetime ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `keysAndValues` | **array** |  |
| `lifetime` | **mixed** |  |


**Return Value:**





---
### DoctrineCacheProvider::getStats



```php
DoctrineCacheProvider::getStats(  ): mixed
```





**Return Value:**





---
## DoctrineDriver





* Full name: \NGSOFT\Cache\Drivers\DoctrineDriver
* Parent class: \NGSOFT\Cache\Drivers\BaseDriver


### DoctrineDriver::__construct



```php
DoctrineDriver::__construct( \Doctrine\Common\Cache\CacheProvider provider ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `provider` | **\Doctrine\Common\Cache\CacheProvider** |  |


**Return Value:**





---
### DoctrineDriver::clear



```php
DoctrineDriver::clear(  ): bool
```





**Return Value:**





---
### DoctrineDriver::delete



```php
DoctrineDriver::delete( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### DoctrineDriver::getCacheEntry



```php
DoctrineDriver::getCacheEntry( string key ): \NGSOFT\Cache\CacheEntry
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### DoctrineDriver::has



```php
DoctrineDriver::has( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### DoctrineDriver::__debugInfo



```php
DoctrineDriver::__debugInfo(  ): array
```





**Return Value:**





---
## FileCache





* Full name: \NGSOFT\Facades\FileCache
* Parent class: 


### FileCache::invalidateTags

Invalidates cached items using tags.

```php
FileCache::invalidateTags( string[]|string tags ): bool
```

When implemented on a PSR-6 pool, invalidation should not apply
to deferred items. Instead, they should be committed as usual.
This allows replacing old tagged values by new ones without
race conditions.

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `tags` | **string[]\|string** | An array of tags to invalidate |


**Return Value:**

True on success



---
### FileCache::purge

Removes expired item entries if supported

```php
FileCache::purge(  ): void
```



* This method is **static**.

**Return Value:**





---
### FileCache::get

Fetches a value from the pool or computes it if not found.

```php
FileCache::get( string key, mixed|\Closure default = null ): mixed
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `default` | **mixed\|\Closure** | if set the item will be saved with that value |


**Return Value:**





---
### FileCache::increment

Increment the value of an item in the cache.

```php
FileCache::increment( string key, int value = 1 ): int
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `value` | **int** |  |


**Return Value:**





---
### FileCache::decrement

Decrement the value of an item in the cache.

```php
FileCache::decrement( string key, int value = 1 ): int
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `value` | **int** |  |


**Return Value:**





---
### FileCache::add

Adds data if it doesn't already exists

```php
FileCache::add( string key, mixed|\Closure value ): bool
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `value` | **mixed\|\Closure** |  |


**Return Value:**

True if the data have been added, false otherwise



---
### FileCache::clear

{@inheritdoc}

```php
FileCache::clear(  ): bool
```



* This method is **static**.

**Return Value:**





---
### FileCache::commit

{@inheritdoc}

```php
FileCache::commit(  ): bool
```



* This method is **static**.

**Return Value:**





---
### FileCache::deleteItem

{@inheritdoc}

```php
FileCache::deleteItem( string key ): bool
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### FileCache::deleteItems

{@inheritdoc}

```php
FileCache::deleteItems( array keys ): bool
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `keys` | **array** |  |


**Return Value:**





---
### FileCache::getItem

{@inheritdoc}

```php
FileCache::getItem( string key ): \NGSOFT\Cache\Interfaces\TaggableCacheItem
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### FileCache::getItems

{@inheritdoc}

```php
FileCache::getItems( array keys = [] ): iterable
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `keys` | **array** |  |


**Return Value:**





---
### FileCache::hasItem

{@inheritdoc}

```php
FileCache::hasItem( string key ): bool
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### FileCache::save

{@inheritdoc}

```php
FileCache::save( \Psr\Cache\CacheItemInterface item ): bool
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `item` | **\Psr\Cache\CacheItemInterface** |  |


**Return Value:**





---
### FileCache::saveDeferred

{@inheritdoc}

```php
FileCache::saveDeferred( \Psr\Cache\CacheItemInterface item ): bool
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `item` | **\Psr\Cache\CacheItemInterface** |  |


**Return Value:**





---
### FileCache::lock

{@inheritdoc}

```php
FileCache::lock( string name, int|float seconds, string owner = '' ): \NGSOFT\Lock\LockStore
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `name` | **string** |  |
| `seconds` | **int\|float** |  |
| `owner` | **string** |  |


**Return Value:**





---
### FileCache::setPrefix

Change cache prefix

```php
FileCache::setPrefix( string prefix ): void
```



* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `prefix` | **string** |  |


**Return Value:**





---
### FileCache::getDriver

Access Cache driver directly

```php
FileCache::getDriver(  ): \NGSOFT\Cache\Interfaces\CacheDriver
```



* This method is **static**.

**Return Value:**





---
### FileCache::invalidate

Increase prefix version, invalidating all prefixed entries

```php
FileCache::invalidate(  ): bool
```



* This method is **static**.

**Return Value:**





---
## FileDriver

The oldest cache driver that store binary datas



* Full name: \NGSOFT\Cache\Drivers\FileDriver
* Parent class: \NGSOFT\Cache\Drivers\BaseDriver


### FileDriver::onWindows



```php
FileDriver::onWindows(  ): bool
```



* This method is **static**.

**Return Value:**





---
### FileDriver::__construct



```php
FileDriver::__construct( string root = '', string prefix = '' ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `root` | **string** |  |
| `prefix` | **string** |  |


**Return Value:**





---
### FileDriver::__destruct



```php
FileDriver::__destruct(  ): mixed
```





**Return Value:**





---
### FileDriver::purge

Removes expired item entries if supported

```php
FileDriver::purge(  ): void
```





**Return Value:**





---
### FileDriver::clear



```php
FileDriver::clear(  ): bool
```





**Return Value:**





---
### FileDriver::delete



```php
FileDriver::delete( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### FileDriver::getCacheEntry



```php
FileDriver::getCacheEntry( string key ): \NGSOFT\Cache\CacheEntry
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### FileDriver::has



```php
FileDriver::has( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### FileDriver::__debugInfo



```php
FileDriver::__debugInfo(  ): array
```





**Return Value:**





---
## IlluminateDriver





* Full name: \NGSOFT\Cache\Drivers\IlluminateDriver
* Parent class: \NGSOFT\Cache\Drivers\BaseDriver


### IlluminateDriver::__construct



```php
IlluminateDriver::__construct( \Illuminate\Contracts\Cache\Store provider ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `provider` | **\Illuminate\Contracts\Cache\Store** |  |


**Return Value:**





---
### IlluminateDriver::clear



```php
IlluminateDriver::clear(  ): bool
```





**Return Value:**





---
### IlluminateDriver::delete



```php
IlluminateDriver::delete( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### IlluminateDriver::getCacheEntry



```php
IlluminateDriver::getCacheEntry( string key ): \NGSOFT\Cache\CacheEntry
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### IlluminateDriver::has



```php
IlluminateDriver::has( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### IlluminateDriver::__debugInfo



```php
IlluminateDriver::__debugInfo(  ): array
```





**Return Value:**





---
## InvalidArgument





* Full name: \NGSOFT\Cache\Exceptions\InvalidArgument
* Parent class: \NGSOFT\Cache\Exceptions\CacheError
* This class implements: \Psr\Cache\InvalidArgumentException, \Psr\SimpleCache\InvalidArgumentException


## JsonDriver

A driver that can be used for Cli applications
Can store data inside a json config file for example



* Full name: \NGSOFT\Cache\Drivers\JsonDriver
* Parent class: \NGSOFT\Cache\Drivers\BaseDriver
* This class implements: \Countable


### JsonDriver::__construct



```php
JsonDriver::__construct( string|\NGSOFT\Filesystem\File file = '', string key = 'cache' ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `file` | **string\|\NGSOFT\Filesystem\File** |  |
| `key` | **string** | Key to use inside the object |


**Return Value:**





---
### JsonDriver::purge

Removes expired item entries if supported

```php
JsonDriver::purge(  ): void
```





**Return Value:**





---
### JsonDriver::clear



```php
JsonDriver::clear(  ): bool
```





**Return Value:**





---
### JsonDriver::delete



```php
JsonDriver::delete( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### JsonDriver::getCacheEntry



```php
JsonDriver::getCacheEntry( string key ): \NGSOFT\Cache\CacheEntry
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### JsonDriver::has



```php
JsonDriver::has( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### JsonDriver::count



```php
JsonDriver::count(  ): int
```





**Return Value:**





---
### JsonDriver::__debugInfo



```php
JsonDriver::__debugInfo(  ): array
```





**Return Value:**





---
## KeyDeleted





* Full name: \NGSOFT\Cache\Events\KeyDeleted
* Parent class: \NGSOFT\Cache\Events\CacheEvent


## KeySaved





* Full name: \NGSOFT\Cache\Events\KeySaved
* Parent class: \NGSOFT\Cache\Events\CacheEvent


### KeySaved::__construct



```php
KeySaved::__construct( \Psr\Cache\CacheItemPoolInterface cachePool, string key, mixed value ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `cachePool` | **\Psr\Cache\CacheItemPoolInterface** |  |
| `key` | **string** |  |
| `value` | **mixed** |  |


**Return Value:**





---
## LaravelStore





* Full name: \NGSOFT\Cache\Adapters\LaravelStore
* This class implements: \NGSOFT\Cache, \Illuminate\Contracts\Cache\Store, \Psr\Log\LoggerAwareInterface, \Stringable, \Illuminate\Contracts\Cache\LockProvider


### LaravelStore::__construct



```php
LaravelStore::__construct( \NGSOFT\Cache\Interfaces\CacheDriver driver, string prefix = '', int defaultLifetime ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `driver` | **\NGSOFT\Cache\Interfaces\CacheDriver** |  |
| `prefix` | **string** |  |
| `defaultLifetime` | **int** |  |


**Return Value:**





---
### LaravelStore::setLogger

{@inheritdoc}

```php
LaravelStore::setLogger( \Psr\Log\LoggerInterface logger ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `logger` | **\Psr\Log\LoggerInterface** |  |


**Return Value:**





---
### LaravelStore::increment

{@inheritdoc}

```php
LaravelStore::increment( mixed key, mixed value = 1 ): int
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **mixed** |  |
| `value` | **mixed** |  |


**Return Value:**





---
### LaravelStore::decrement

{@inheritdoc}

```php
LaravelStore::decrement( mixed key, mixed value = 1 ): int
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **mixed** |  |
| `value` | **mixed** |  |


**Return Value:**





---
### LaravelStore::flush

{@inheritdoc}

```php
LaravelStore::flush(  ): bool
```





**Return Value:**





---
### LaravelStore::forever

{@inheritdoc}

```php
LaravelStore::forever( mixed key, mixed value ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **mixed** |  |
| `value` | **mixed** |  |


**Return Value:**





---
### LaravelStore::forget

{@inheritdoc}

```php
LaravelStore::forget( mixed key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **mixed** |  |


**Return Value:**





---
### LaravelStore::get

{@inheritdoc}

```php
LaravelStore::get( mixed key ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **mixed** |  |


**Return Value:**





---
### LaravelStore::getPrefix

{@inheritdoc}

```php
LaravelStore::getPrefix(  ): string
```





**Return Value:**





---
### LaravelStore::many

{@inheritdoc}

```php
LaravelStore::many( array keys ): array
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `keys` | **array** |  |


**Return Value:**





---
### LaravelStore::put

{@inheritdoc}

```php
LaravelStore::put( mixed key, mixed value, mixed seconds ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **mixed** |  |
| `value` | **mixed** |  |
| `seconds` | **mixed** |  |


**Return Value:**





---
### LaravelStore::putMany

{@inheritdoc}

```php
LaravelStore::putMany( array values, mixed seconds ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `values` | **array** |  |
| `seconds` | **mixed** |  |


**Return Value:**





---
## NullDriver





* Full name: \NGSOFT\Cache\Drivers\NullDriver
* Parent class: \NGSOFT\Cache\Drivers\BaseDriver


### NullDriver::clear



```php
NullDriver::clear(  ): bool
```





**Return Value:**





---
### NullDriver::delete



```php
NullDriver::delete( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### NullDriver::getCacheEntry



```php
NullDriver::getCacheEntry( string key ): \NGSOFT\Cache\CacheEntry
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### NullDriver::has



```php
NullDriver::has( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
## PDOAdapter





* Full name: \NGSOFT\Cache\Databases\SQLite\PDOAdapter
* Parent class: \NGSOFT\Cache\Databases\SQLite\QueryEngine


### PDOAdapter::__construct



```php
PDOAdapter::__construct( \PDO driver, string table ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `driver` | **\PDO** |  |
| `table` | **string** |  |


**Return Value:**





---
### PDOAdapter::read



```php
PDOAdapter::read( string key, bool data = true ): array|false
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `data` | **bool** |  |


**Return Value:**





---
### PDOAdapter::query



```php
PDOAdapter::query( string query ): array|bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `query` | **string** |  |


**Return Value:**





---
## PHPCache

A preconfigured cache pool
Chains ArrayDriver, ApcuDriver, PhpDriver



* Full name: \NGSOFT\Cache\PHPCache
* Parent class: \NGSOFT\Cache\CachePool


### PHPCache::__construct



```php
PHPCache::__construct( string rootpath = '', string prefix = '', int defaultLifetime, ?\Psr\Log\LoggerInterface logger = null, ?\Psr\EventDispatcher\EventDispatcherInterface eventDispatcher = null ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `rootpath` | **string** |  |
| `prefix` | **string** |  |
| `defaultLifetime` | **int** |  |
| `logger` | **?\Psr\Log\LoggerInterface** |  |
| `eventDispatcher` | **?\Psr\EventDispatcher\EventDispatcherInterface** |  |


**Return Value:**





---
### PHPCache::appendDriver

Put a driver at the end of the chain

```php
PHPCache::appendDriver( \NGSOFT\Cache\Interfaces\CacheDriver driver ): static
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `driver` | **\NGSOFT\Cache\Interfaces\CacheDriver** |  |


**Return Value:**





---
### PHPCache::prependDriver

Put a driver at the beginning of the chain

```php
PHPCache::prependDriver( \NGSOFT\Cache\Interfaces\CacheDriver driver ): static
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `driver` | **\NGSOFT\Cache\Interfaces\CacheDriver** |  |


**Return Value:**





---
## PhpDriver





* Full name: \NGSOFT\Cache\Drivers\PhpDriver
* Parent class: \NGSOFT\Cache\Drivers\BaseDriver


### PhpDriver::opCacheSupported



```php
PhpDriver::opCacheSupported(  ): bool
```



* This method is **static**.

**Return Value:**





---
### PhpDriver::onWindows



```php
PhpDriver::onWindows(  ): bool
```



* This method is **static**.

**Return Value:**





---
### PhpDriver::__construct



```php
PhpDriver::__construct( string root = '', string prefix = '' ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `root` | **string** |  |
| `prefix` | **string** |  |


**Return Value:**





---
### PhpDriver::__destruct



```php
PhpDriver::__destruct(  ): mixed
```





**Return Value:**





---
### PhpDriver::purge

Removes expired item entries if supported

```php
PhpDriver::purge(  ): void
```





**Return Value:**





---
### PhpDriver::clear



```php
PhpDriver::clear(  ): bool
```





**Return Value:**





---
### PhpDriver::delete



```php
PhpDriver::delete( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### PhpDriver::getCacheEntry



```php
PhpDriver::getCacheEntry( string key ): \NGSOFT\Cache\CacheEntry
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### PhpDriver::has



```php
PhpDriver::has( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### PhpDriver::__debugInfo



```php
PhpDriver::__debugInfo(  ): array
```





**Return Value:**





---
## PSR16Driver





* Full name: \NGSOFT\Cache\Drivers\PSR16Driver
* Parent class: \NGSOFT\Cache\Drivers\BaseDriver


### PSR16Driver::__construct



```php
PSR16Driver::__construct( \Psr\SimpleCache\CacheInterface provider ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `provider` | **\Psr\SimpleCache\CacheInterface** |  |


**Return Value:**





---
### PSR16Driver::clear



```php
PSR16Driver::clear(  ): bool
```





**Return Value:**





---
### PSR16Driver::delete



```php
PSR16Driver::delete( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### PSR16Driver::getCacheEntry



```php
PSR16Driver::getCacheEntry( string key ): \NGSOFT\Cache\CacheEntry
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### PSR16Driver::has



```php
PSR16Driver::has( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### PSR16Driver::__debugInfo



```php
PSR16Driver::__debugInfo(  ): array
```





**Return Value:**





---
## PSR6Driver





* Full name: \NGSOFT\Cache\Drivers\PSR6Driver
* Parent class: \NGSOFT\Cache\Drivers\BaseDriver


### PSR6Driver::__construct



```php
PSR6Driver::__construct( \Psr\Cache\CacheItemPoolInterface provider ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `provider` | **\Psr\Cache\CacheItemPoolInterface** |  |


**Return Value:**





---
### PSR6Driver::getCacheEntry



```php
PSR6Driver::getCacheEntry( string key ): \NGSOFT\Cache\CacheEntry
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### PSR6Driver::clear



```php
PSR6Driver::clear(  ): bool
```





**Return Value:**





---
### PSR6Driver::delete



```php
PSR6Driver::delete( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### PSR6Driver::has



```php
PSR6Driver::has( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### PSR6Driver::__debugInfo



```php
PSR6Driver::__debugInfo(  ): array
```





**Return Value:**





---
## ReactCache





* Full name: \NGSOFT\Cache\Adapters\ReactCache
* This class implements: \NGSOFT\Cache, \React\Cache\CacheInterface, \Stringable, \Psr\Log\LoggerAwareInterface


### ReactCache::__construct



```php
ReactCache::__construct( \NGSOFT\Cache\Interfaces\CacheDriver driver, string prefix = '', int defaultLifetime ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `driver` | **\NGSOFT\Cache\Interfaces\CacheDriver** |  |
| `prefix` | **string** |  |
| `defaultLifetime` | **int** |  |


**Return Value:**





---
### ReactCache::setLogger

{@inheritdoc}

```php
ReactCache::setLogger( \Psr\Log\LoggerInterface logger ): void
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `logger` | **\Psr\Log\LoggerInterface** |  |


**Return Value:**





---
### ReactCache::increment

Increment the value of an item in the cache.

```php
ReactCache::increment( string key, int value = 1 ): \React\Promise\PromiseInterface&lt;int&gt;
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `value` | **int** |  |


**Return Value:**

new value



---
### ReactCache::decrement

Decrement the value of an item in the cache.

```php
ReactCache::decrement( string key, int value = 1 ): \React\Promise\PromiseInterface&lt;int&gt;
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `value` | **int** |  |


**Return Value:**

new value



---
### ReactCache::add

Adds data if it doesn't already exists

```php
ReactCache::add( string key, mixed|\Closure value ): \React\Promise\PromiseInterface&lt;bool&gt;
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `value` | **mixed\|\Closure** |  |


**Return Value:**

True if the data have been added, false otherwise



---
### ReactCache::clear

{@inheritdoc}

```php
ReactCache::clear(  ): \React\Promise\PromiseInterface
```





**Return Value:**





---
### ReactCache::delete

{@inheritdoc}

```php
ReactCache::delete( mixed key ): \React\Promise\PromiseInterface
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **mixed** |  |


**Return Value:**





---
### ReactCache::has

{@inheritdoc}

```php
ReactCache::has( mixed key ): \React\Promise\PromiseInterface
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **mixed** |  |


**Return Value:**





---
### ReactCache::get

{@inheritdoc}

```php
ReactCache::get( mixed key, mixed default = null ): \React\Promise\PromiseInterface
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **mixed** |  |
| `default` | **mixed** |  |


**Return Value:**





---
### ReactCache::set

{@inheritdoc}

```php
ReactCache::set( mixed key, mixed value, mixed ttl = null ): \React\Promise\PromiseInterface
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **mixed** |  |
| `value` | **mixed** |  |
| `ttl` | **mixed** |  |


**Return Value:**





---
### ReactCache::deleteMultiple

{@inheritdoc}

```php
ReactCache::deleteMultiple( array keys ): \React\Promise\PromiseInterface
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `keys` | **array** |  |


**Return Value:**





---
### ReactCache::getMultiple

{@inheritdoc}

```php
ReactCache::getMultiple( array keys, mixed default = null ): \React\Promise\PromiseInterface
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `keys` | **array** |  |
| `default` | **mixed** |  |


**Return Value:**





---
### ReactCache::setMultiple

{@inheritdoc}

```php
ReactCache::setMultiple( array values, mixed ttl = null ): \React\Promise\PromiseInterface
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `values` | **array** |  |
| `ttl` | **mixed** |  |


**Return Value:**





---
## SimpleCachePool

PSR-6 to PSR-16 Adapter



* Full name: \NGSOFT\Cache\Adapters\SimpleCachePool
* This class implements: \Psr\SimpleCache\CacheInterface, \Psr\Log\LoggerAwareInterface, \Stringable, \NGSOFT\Cache


### SimpleCachePool::__construct



```php
SimpleCachePool::__construct( \Psr\Cache\CacheItemPoolInterface cachePool, ?int defaultLifetime = null ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `cachePool` | **\Psr\Cache\CacheItemPoolInterface** |  |
| `defaultLifetime` | **?int** |  |


**Return Value:**





---
### SimpleCachePool::getCachePool

{@inheritdoc}

```php
SimpleCachePool::getCachePool(  ): \Psr\Cache\CacheItemPoolInterface
```





**Return Value:**





---
### SimpleCachePool::increment

Increment the value of an item in the cache.

```php
SimpleCachePool::increment( string key, int value = 1 ): int
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `value` | **int** |  |


**Return Value:**





---
### SimpleCachePool::decrement

Decrement the value of an item in the cache.

```php
SimpleCachePool::decrement( string key, int value = 1 ): int
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `value` | **int** |  |


**Return Value:**





---
### SimpleCachePool::add

Adds data if it doesn't already exists

```php
SimpleCachePool::add( string key, mixed|\Closure value ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `value` | **mixed\|\Closure** |  |


**Return Value:**

True if the data have been added, false otherwise



---
### SimpleCachePool::clear

{@inheritdoc}

```php
SimpleCachePool::clear(  ): bool
```





**Return Value:**





---
### SimpleCachePool::delete

{@inheritdoc}

```php
SimpleCachePool::delete( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### SimpleCachePool::deleteMultiple

{@inheritdoc}

```php
SimpleCachePool::deleteMultiple( iterable keys ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `keys` | **iterable** |  |


**Return Value:**





---
### SimpleCachePool::get

{@inheritdoc}

```php
SimpleCachePool::get( string key, mixed default = null ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `default` | **mixed** |  |


**Return Value:**





---
### SimpleCachePool::getMultiple

{@inheritdoc}

```php
SimpleCachePool::getMultiple( iterable keys, mixed default = null ): iterable
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `keys` | **iterable** |  |
| `default` | **mixed** |  |


**Return Value:**





---
### SimpleCachePool::has

{@inheritdoc}

```php
SimpleCachePool::has( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### SimpleCachePool::set

{@inheritdoc}

```php
SimpleCachePool::set( string key, mixed value, null|int|\DateInterval ttl = null ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `value` | **mixed** |  |
| `ttl` | **null\|int\|\DateInterval** |  |


**Return Value:**





---
### SimpleCachePool::setMultiple

{@inheritdoc}

```php
SimpleCachePool::setMultiple( iterable values, null|int|\DateInterval ttl = null ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `values` | **iterable** |  |
| `ttl` | **null\|int\|\DateInterval** |  |


**Return Value:**





---
### SimpleCachePool::__debugInfo



```php
SimpleCachePool::__debugInfo(  ): array
```





**Return Value:**





---
## SQLite3Adapter





* Full name: \NGSOFT\Cache\Databases\SQLite\SQLite3Adapter
* Parent class: \NGSOFT\Cache\Databases\SQLite\QueryEngine


### SQLite3Adapter::__construct



```php
SQLite3Adapter::__construct( \SQLite3 driver, string table ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `driver` | **\SQLite3** |  |
| `table` | **string** |  |


**Return Value:**





---
### SQLite3Adapter::read



```php
SQLite3Adapter::read( string key, bool data = true ): array|false
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |
| `data` | **bool** |  |


**Return Value:**





---
### SQLite3Adapter::query



```php
SQLite3Adapter::query( string query ): array|bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `query` | **string** |  |


**Return Value:**





---
## Sqlite3Driver





* Full name: \NGSOFT\Cache\Drivers\Sqlite3Driver
* Parent class: \NGSOFT\Cache\Drivers\BaseDriver


### Sqlite3Driver::__construct



```php
Sqlite3Driver::__construct( \SQLite3|\PDO|string driver = '', string table = 'cache' ): mixed
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `driver` | **\SQLite3\|\PDO\|string** | A SQLite3 instance or a filename |
| `table` | **string** |  |


**Return Value:**





---
### Sqlite3Driver::purge

Removes expired item entries if supported

```php
Sqlite3Driver::purge(  ): void
```





**Return Value:**





---
### Sqlite3Driver::clear



```php
Sqlite3Driver::clear(  ): bool
```





**Return Value:**





---
### Sqlite3Driver::delete



```php
Sqlite3Driver::delete( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### Sqlite3Driver::getCacheEntry



```php
Sqlite3Driver::getCacheEntry( string key ): \NGSOFT\Cache\CacheEntry
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### Sqlite3Driver::has



```php
Sqlite3Driver::has( string key ): bool
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | **string** |  |


**Return Value:**





---
### Sqlite3Driver::__debugInfo



```php
Sqlite3Driver::__debugInfo(  ): array
```





**Return Value:**





---
