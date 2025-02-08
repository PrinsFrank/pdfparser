<?php declare(strict_types=1);

/**
 * This file contains an alternative autoloader for when you can't, or don't want to, use the Composer autoloader.
 * To use this file, simply add `require_once 'path/to/this/package/.al-custom.php'` at the top of
 * your custom bootstrap file or the file you want to use this package in.
 * If you are using the Composer autoloader, you don't need to include this file at all.
 */
spl_autoload_register(
    fn (string $FQN) => str_starts_with($FQN, 'PrinsFrank\\PdfParser\\')
        ? require_once __DIR__ . '/src/' . str_replace('\\', DIRECTORY_SEPARATOR, substr($FQN, 21)) . '.php'
        : null
);
