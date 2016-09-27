<?php
use Symfony\Component\Finder\Finder;

$binders = [];

foreach (Finder::create()->files()->in(app_path('Http/Routes')) as $file) {
    $binders[] = 'App' . str_replace([app_path(), '/', '.php'], ['', '\\', ''], $file);
}

return ['binders' => $binders];
