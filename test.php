<?php
declare(strict_types=1);

// Diagnostic endpoint disabled in production.
http_response_code(404);
exit;
