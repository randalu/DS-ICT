<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
fwrite(STDERR, "This command targets retired Tour reference pages. Use cli/consolidate_tour_details.php for the current content transfer.\n");
exit(1);
