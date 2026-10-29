<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/customer_auth.php';
$customer = customer_require_auth();
$plan = find_customer_plan((int) get_input('plan_id', 0), $customer);
if (!$plan) { http_response_code(404); exit; }
require_once __DIR__ . '/includes/quotation_booking.php';
$binding = quote_booking_binding((int) $plan['id']);
if (!$binding) { http_response_code(404); exit; }
$quote = quote_get((int) $binding['revision_id']);
if ($quote['state'] !== 'issued' || !is_string($quote['pdf_bytes']) || !hash_equals($quote['pdf_sha256'], hash('sha256', $quote['pdf_bytes']))) { http_response_code(404); exit; }
header('Content-Type: application/pdf'); header('Cache-Control: private, no-store'); header('X-Robots-Tag: noindex, nofollow'); header('X-Content-Type-Options: nosniff');
header('Content-Disposition: inline; filename="' . $quote['reference'] . '-R' . $quote['revision'] . '.pdf"'); echo $quote['pdf_bytes'];
