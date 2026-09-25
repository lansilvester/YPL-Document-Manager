<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

class HtmlSanitizer
{
    public static function clean(string $html): string
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,strong,em,u,blockquote,ul,ol,li,h1,h2,h3,h4,a[href|title|target],code,pre');
        $config->set('URI.DisableExternalResources', true);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);

        return (new HTMLPurifier($config))->purify($html);
    }
}
