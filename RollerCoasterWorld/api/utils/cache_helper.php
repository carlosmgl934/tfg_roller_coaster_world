<?php

class CacheHelper
{
    private static $cache_dir = __DIR__ . '/../../api/cache';

    /**
     * Get data from cache or execute callback to refresh it.
     * 
     * @param string $key Cache key (filename)
     * @param int $ttl Time to live in seconds
     * @param callable $callback Function to execute if cache is invalid/missing
     * @return mixed Cached or fresh data
     */
    public static function get($key, $ttl, $callback)
    {
        if (!is_dir(self::$cache_dir)) {
            mkdir(self::$cache_dir, 0755, true);
        }

        $cache_file = self::$cache_dir . '/' . $key . '.json';

        if (file_exists($cache_file) && (time() - filemtime($cache_file) < $ttl)) {
            $data = json_decode(file_get_contents($cache_file), true);
            if ($data !== null) {
                return $data;
            }
        }

        // Cache missing or expired
        $fresh_data = $callback();
        file_put_contents($cache_file, json_encode($fresh_data));
        
        return $fresh_data;
    }

    /**
     * Clear a specific cache key.
     */
    public static function forget($key)
    {
        $cache_file = self::$cache_dir . '/' . $key . '.json';
        if (file_exists($cache_file)) {
            unlink($cache_file);
        }
    }
}
