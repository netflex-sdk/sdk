<?php

if (!function_exists('md5_to_uuid')) {
    /**
     * Generates a UUID from a md5 hash
     * @return string
     */
    function md5_to_uuid($md5)
    {
        return substr($md5, 0, 8) . '-' .
            substr($md5, 8, 4) . '-' .
            substr($md5, 12, 4) . '-' .
            substr($md5, 16, 4) . '-' .
            substr($md5, 20);
    }
}

if (!function_exists('uuid')) {
    /**
     * Generates a unique id
     * @param string|null $from
     * @return string
     */
    function uuid($from = null)
    {
        $md5 = $from ? $from : (microtime() . uniqid());
        return md5_to_uuid(md5($md5));
    }
}
