<?php

if(!function_exists('buildCacheKey')) {
    function buildCacheKey(string $prefix, array $params = []): string
    {
        if (!empty($params)) {
            // Sort query params by key
            ksort($params);

            // Build normalized string
            $query = http_build_query($params);
            
            return $prefix . ':' . sha1($query);
        }

        return $prefix;
    }
}