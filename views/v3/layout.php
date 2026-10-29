<?php
declare(strict_types=1);

/**
 * Deep Coast (v3) public layout.
 *
 * @var \ICT\View\Page $page
 */

if (!isset($page) || !$page instanceof \ICT\View\Page) {
    throw new RuntimeException('The v3 layout requires an ICT\View\Page instance.');
}

require APP_ROOT . '/views/v3/partials/head.php';
require APP_ROOT . '/views/v3/partials/header.php';

require APP_ROOT . '/views/' . $page->template . '.php';

if ($page->schema !== []) {
    schema_output($page->schema);
}

require APP_ROOT . '/views/v3/partials/footer.php';
require APP_ROOT . '/views/v3/partials/scripts.php';
