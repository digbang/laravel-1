<?php
use Symfony\Component\Finder\Finder;

$binders = function(){
    foreach (Finder::create()->files()->in(app_path('Http/Routes')) as $file) {
        yield 'App' . str_replace([app_path(), '/', '.php'], ['', '\\', ''], $file);
    }
};

return [ 'binders' => $binders() ];
