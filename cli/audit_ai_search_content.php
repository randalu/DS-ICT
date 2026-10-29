<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/ai_search_audit.php';

$targets = ai_search_content_target_status();
$issues = ai_search_quality_issues(3);
$missingImageQueue = ai_search_missing_image_queue(5);
$placeholderImageQueue = ai_search_placeholder_image_queue(5);
$nextBlogPosts = ai_search_next_blog_posts(20);

echo "AI/search content coverage\n";
foreach ($targets as $target) {
    $status = !empty($target['complete']) ? 'ready' : 'needs-work';
    echo sprintf(
        "- %s: %d/%d (%s)\n",
        (string) $target['label'],
        (int) $target['value'],
        (int) $target['target'],
        $status
    );
}

echo "\nQuality issues\n";
if (!$issues) {
    echo "- none\n";
} else {
    foreach ($issues as $issue) {
        echo sprintf(
            "- [%s] %s: %s (%d)\n",
            (string) ($issue['severity'] ?? 'Medium'),
            (string) ($issue['area'] ?? 'Content'),
            (string) ($issue['issue'] ?? 'Review needed'),
            (int) ($issue['count'] ?? 0)
        );
        foreach (($issue['samples'] ?? []) as $sample) {
            echo sprintf("  - %s (%s)\n", (string) ($sample['title'] ?? 'Untitled'), (string) ($sample['slug'] ?? ''));
        }
    }
}

echo "\nImage replacement queue\n";
if (!$missingImageQueue) {
    echo "- none\n";
} else {
    foreach ($missingImageQueue as $group) {
        echo sprintf("- %s: %d missing image records\n", (string) $group['area'], (int) $group['count']);
        foreach (($group['records'] ?? []) as $record) {
            echo sprintf("  - %s (%s)\n", (string) ($record['title'] ?? 'Untitled'), (string) ($record['slug'] ?? ''));
        }
    }
}

echo "\nPlaceholder replacement queue\n";
if (!$placeholderImageQueue) {
    echo "- none\n";
} else {
    foreach ($placeholderImageQueue as $group) {
        echo sprintf("- %s: %d placeholder records\n", (string) $group['area'], (int) $group['count']);
        foreach (($group['records'] ?? []) as $record) {
            echo sprintf("  - %s (%s)\n", (string) ($record['title'] ?? 'Untitled'), (string) ($record['slug'] ?? ''));
        }
    }
}

echo "\nScheduled blog posts\n";
echo sprintf("- future scheduled posts visible to admin preview: %d\n", count($nextBlogPosts));
