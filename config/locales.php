<?php

$path = __DIR__.'/locales.json';
$contents = file_get_contents($path);

if ($contents === false) {
    throw new RuntimeException("Unable to read locale registry: {$path}");
}

/** @var array<string, mixed> $registry */
$registry = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

return $registry;
