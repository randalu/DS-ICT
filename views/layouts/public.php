<?php
declare(strict_types=1);

/**
 * Public layout for the v2 template system.
 *
 * @var \ICT\View\Page $page
 */

if (!isset($page) || !$page instanceof \ICT\View\Page) {
    throw new RuntimeException('Public layout requires an ICT\View\Page instance.');
}

require APP_ROOT . '/views/partials/head.php';
require APP_ROOT . '/views/partials/header.php';

require APP_ROOT . '/views/' . $page->template . '.php';

if ($page->schema !== []) {
    schema_output($page->schema);
}

require APP_ROOT . '/views/partials/footer.php';
require APP_ROOT . '/views/partials/scripts.php';
