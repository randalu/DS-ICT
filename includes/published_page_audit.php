<?php
declare(strict_types=1);

/**
 * Trust-page routes that are expected to use the static-page CMS.
 *
 * The list is intentionally kept in one place so the release audit and its
 * tests cannot drift apart while these pages remain unpublished.
 */
function published_static_page_trust_routes(): array
{
    return [
        'booking-terms-and-conditions' => '/booking-terms-and-conditions',
        'cancellation-refund-policy' => '/cancellation-refund-policy',
        'payment-deposit-policy' => '/payment-deposit-policy',
        'book-with-confidence' => '/book-with-confidence',
        'trust-safety' => '/trust-safety',
        'drivers-vehicles' => '/drivers-vehicles',
        'how-booking-works' => '/how-booking-works',
        'tour-inclusions-exclusions' => '/tour-inclusions-exclusions',
    ];
}

/** Root-relative URLs are valid when they retain the configured deployment path. */
function published_static_page_has_unsafe_root_link(string $html, string $appUrl): bool
{
    $prefix=rtrim((string)parse_url($appUrl,PHP_URL_PATH),'/');
    if ($prefix==='') { return false; }
    preg_match_all('~(?:href|src)=["\'](/(?!/)[^"\']*)["\']~i',$html,$matches);
    foreach ($matches[1] as $url) {
        $path=(string)parse_url(html_entity_decode($url,ENT_QUOTES|ENT_HTML5,'UTF-8'),PHP_URL_PATH);
        if ($path!==$prefix && !str_starts_with($path,$prefix . '/')) { return true; }
    }
    return false;
}

/**
 * Find unresolved editorial placeholders without returning the page body.
 *
 * @return list<string>
 */
function published_static_page_placeholder_tokens(string $text): array
{
    $tokens = [];

    if (preg_match_all('/\{\{\s*[^{}\r\n]{1,120}\s*\}\}/', $text, $matches)) {
        $tokens = array_merge($tokens, $matches[0]);
    }

    if (preg_match_all('/\b(?:TODO|FIXME|TBD)\b/i', $text, $matches)) {
        $tokens = array_merge($tokens, $matches[0]);
    }

    return array_values(array_unique(array_map('trim', $tokens)));
}

/**
 * Audit one published static-page record for unresolved content placeholders.
 *
 * Empty optional CMS fields are allowed because the public renderer has
 * intentional fallbacks. Title and visible content are required for a page
 * to be publishable. Reasons avoid including full page content in CLI output.
 *
 * @return list<array{field:string,reason:string}>
 */
function published_static_page_placeholder_issues(array $page): array
{
    $issues = [];
    $seen = [];

    $addIssue = static function (string $field, string $reason) use (&$issues, &$seen): void {
        $key = $field . "\0" . $reason;
        if (isset($seen[$key])) {
            return;
        }

        $seen[$key] = true;
        $issues[] = [
            'field' => $field,
            'reason' => $reason,
        ];
    };

    $textFields = [
        'title',
        'content',
        'meta_title',
        'meta_description',
        'canonical_url',
        'cta_title',
        'cta_text',
        'faq_json',
    ];

    foreach ($textFields as $field) {
        $value = trim((string) ($page[$field] ?? ''));
        $visibleValue = trim((string) (preg_replace('/\s+/', ' ', strip_tags($value)) ?? ''));

        if (in_array($field, ['title', 'content'], true) && $visibleValue === '') {
            $addIssue($field, 'missing publishable value');
        }

        if ($value !== '' && $field !== 'faq_json' && is_placeholder_text($value)) {
            $addIssue($field, 'placeholder text');
        }

        foreach (published_static_page_placeholder_tokens($value) as $token) {
            $addIssue($field, 'unresolved placeholder token: ' . $token);
        }
    }

    foreach (['hero_image', 'hero_video_poster', 'og_image'] as $field) {
        $value = trim((string) ($page[$field] ?? ''));
        if ($value === '') {
            continue;
        }

        if (is_placeholder_media_url($value)) {
            $addIssue($field, 'placeholder media');
        }

        foreach (published_static_page_placeholder_tokens($value) as $token) {
            $addIssue($field, 'unresolved placeholder token: ' . $token);
        }
    }

    $faqJson = trim((string) ($page['faq_json'] ?? ''));
    if ($faqJson !== '') {
        $decodedFaq = json_decode($faqJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $addIssue('faq_json', 'invalid JSON');
        } elseif (is_array($decodedFaq)) {
            foreach ($decodedFaq as $index => $item) {
                if (!is_array($item)) {
                    continue;
                }

                foreach (['question', 'answer'] as $key) {
                    $value = trim((string) ($item[$key] ?? $item[$key === 'question' ? 'q' : 'a'] ?? ''));
                    if ($value !== '' && is_placeholder_text($value)) {
                        $addIssue('faq_json', sprintf('FAQ item %s %s is placeholder text', (string) $index, $key));
                    }
                }
            }
        }
    }

    // Scan future string columns for token syntax without making their empty
    // or legacy values publish blockers by default.
    foreach ($page as $field => $value) {
        if (!is_string($value) || in_array((string) $field, $textFields, true)) {
            continue;
        }

        foreach (published_static_page_placeholder_tokens($value) as $token) {
            $addIssue((string) $field, 'unresolved placeholder token: ' . $token);
        }
    }

    return $issues;
}
