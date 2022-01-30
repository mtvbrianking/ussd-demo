<?php
namespace App\Cache;

use Illuminate\Cache\DatabaseStore;
use Illuminate\Support\Facades\Config;

/**
 * SqliteStore delegates to DatabaseStore but with an sqlite connection instead
 * 
 * @see https://ohdoylerules.com/web/laravel-sqlite-cache/
 */
class SqliteStore extends DatabaseStore
{
    public function __construct()
    {
        // load the config or use the default
        $config = config('cache.stores.sqlite', [
            'driver' => 'sqlite',
            'table' => 'cache',
            'database' => env('SQLITE_CACHE_DATABASE', database_path('cache.sqlite')),
            'prefix' => '',
        ]);

        // Set the temporary configuration
        Config::set('database.connections.sqlite_cache', [
            'driver' => 'sqlite',
            'database' => $config['database'],
            'prefix' => $config['prefix'],
        ]);

        $connection = app('db')->connection('sqlite_cache');
        
        parent::__construct($connection, $config['table'], $config['prefix']);
    }

    public function flushLike($pattern)
    {
        $matched = $this->connection
            ->table('cache')
            ->select('key')
            ->where('key', 'like', $pattern)
            ->pluck('key');

        $matched->each(function($key) {
            $this->connection->table('cache')->where('key', $key)->delete();
        });
    }
}