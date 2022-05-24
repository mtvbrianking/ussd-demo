<?php

namespace App\Ussd\Traits;

use Illuminate\Support\Str;

trait ExpManipulators
{
    protected function incExp(string $exp, int $step = 1): string
    {
        return preg_replace_callback("|(\d+)(?!.*\d)|", function($matches) use($step) { 
            return $matches[1] + $step; 
        }, $exp);
    }

    protected function decExp(string $exp, int $step = 1): string
    {
        return preg_replace_callback("|(\d+)(?!.*\d)|", function($matches) use($step) { 
            return $matches[1] - $step; 
        }, $exp);
    }

    /**
     * @see https://stackoverflow.com/q/413071/2732184
     */
    protected function translate(string $text, string $pattern = '/[^{\}]+(?=})/'): string
    {
        preg_match_all($pattern, $text, $matches);

        if(count($matches[0]) === 0) {
            return $text;
        }

        $replace_vars = [];

        foreach($matches[0] as $match) {
            $var = Str::slug($match, '_');
            $replace_vars["{{$match}}"] = $this->cache->get("{$this->prefix}_{$var}", "{{$var}}");
        }

        return strtr($text, $replace_vars);
    }
}
