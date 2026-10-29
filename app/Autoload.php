<?php
declare(strict_types=1);

/**
 * Minimal PSR-4 autoloader for the ICT namespace.
 *
 * The project has no Composer autoload path for application code (vendor/ only
 * carries PHPUnit and dompdf), so this keeps the new app/ layer dependency-free
 * and able to coexist with the legacy root pages during the migration.
 *
 * ICT\View\Page  ->  app/View/Page.php
 */

spl_autoload_register(static function (string $class): void {
    $prefix = 'ICT\\';
    $length = strlen($prefix);

    if (strncasecmp($class, $prefix, $length) !== 0) {
        return;
    }

    $relative = substr($class, $length);
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});
