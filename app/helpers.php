<?php

if (!function_exists('msg')) {
    /**
     * Get message from MessagesHelper
     */
    function msg(string $key, array $replace = [], ?string $locale = null): string
    {
        return \App\Helpers\MessagesHelper::replace($key, $replace, $locale);
    }
}

