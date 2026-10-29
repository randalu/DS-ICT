<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/site_audit.php';

final class SiteAuditTest extends TestCase
{
    private string $logPath = '';
    private string $activityLogPath = '';

    protected function tearDown(): void
    {
        if ($this->logPath !== '' && is_file($this->logPath)) {
            @unlink($this->logPath);
        }
        $this->logPath = '';
        if ($this->activityLogPath !== '' && is_file($this->activityLogPath)) {
            @unlink($this->activityLogPath);
        }
        $this->activityLogPath = '';
    }

    public function testBaseUrlAcceptsHttpAndHttpsWithoutUnsafeComponents(): void
    {
        $this->assertSame('https://example.test/site', site_audit_base_url('https://example.test/site/'));
        $this->assertSame('http://localhost:8080/island', site_audit_base_url('http://localhost:8080/island/'));
        $this->assertSame('', site_audit_base_url('javascript:alert(1)'));
        $this->assertSame('', site_audit_base_url('https://user:pass@example.test'));
        $this->assertSame('', site_audit_base_url('https://example.test/?target=other'));
    }

    public function testIssueSanitizationAppliesAllowListsAndBounds(): void
    {
        $issue = site_audit_sanitize_issue([
            'severity' => 'URGENT',
            'type' => 'Missing CSS! 404',
            'page_url' => 'https://example.test/' . str_repeat('u', 3000),
            'detail' => str_repeat('d', 3000),
        ]);

        $this->assertIsArray($issue);
        $this->assertSame('low', $issue['severity']);
        $this->assertSame('missing_css_404', $issue['type']);
        $this->assertSame(2048, strlen($issue['page_url']));
        $this->assertSame(SITE_AUDIT_MAX_DETAIL_LENGTH, strlen($issue['detail']));
        $this->assertNull(site_audit_sanitize_issue(['page_url' => '']));
        $this->assertNull(site_audit_sanitize_issue(['page_url' => 'javascript:alert(1)']));
        $this->assertNull(site_audit_sanitize_issue(['page_url' => 'https://user:pass@example.test']));
    }

    public function testLogReaderDoesNotConsumeAnUnterminatedWorkerLine(): void
    {
        $this->logPath = (string) tempnam(sys_get_temp_dir(), 'ict-audit-test-');
        $complete = json_encode(['total_pages' => 2]) . "\n";
        file_put_contents($this->logPath, $complete . '{"progress":1');

        $first = site_audit_read_complete_log($this->logPath, 0);
        $this->assertCount(1, $first['events']);
        $this->assertSame(strlen($complete), $first['next_offset']);

        file_put_contents($this->logPath, "}\n", FILE_APPEND);
        $second = site_audit_read_complete_log($this->logPath, (int) $first['next_offset']);
        $this->assertCount(1, $second['events']);
        $this->assertSame(1, $second['events'][0]['progress']);
    }

    public function testRecentWorkerLogActivityKeepsAnOlderRunFresh(): void
    {
        $runId = 987654321;
        $this->activityLogPath = site_audit_log_path($runId);
        file_put_contents($this->activityLogPath, "{\"progress\":1}\n");
        touch($this->activityLogPath, time());

        $lastActivity = site_audit_run_last_activity_at([
            'started_at' => date('Y-m-d H:i:s', time() - 3600),
        ], $runId);

        $this->assertGreaterThan(time() - 10, $lastActivity);
    }
}
