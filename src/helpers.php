<?php

use Larawatcher\Facades\Larawatcher;

if (! function_exists('lw_tag')) {
    function lw_tag(string $tag)
    {
        Larawatcher::tag($tag);
    }
}

if (! function_exists('lw_untag')) {
    function lw_untag(string $tag)
    {
        Larawatcher::untag($tag);
    }
}
