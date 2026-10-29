<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/functions.php';

class SiteSettingsCacheTest extends TestCase
{
    private ?array $originalCache = null;

    protected function setUp(): void
    {
        // Backup any existing cache
        if (isset($GLOBALS['_site_settings_cache'])) {
            $this->originalCache = $GLOBALS['_site_settings_cache'];
        }
        $GLOBALS['_site_settings_cache'] = null;
    }

    protected function tearDown(): void
    {
        // Restore original cache
        if ($this->originalCache !== null) {
            $GLOBALS['_site_settings_cache'] = $this->originalCache;
        } else {
            unset($GLOBALS['_site_settings_cache']);
        }
    }

    public function testSiteSettingGetReturnsFromCache(): void
    {
        // Pre-populate the cache manually
        $GLOBALS['_site_settings_cache'] = [
            'test_key_1' => 'cached_value_1',
            'test_key_2' => 'cached_value_2'
        ];

        // site_setting_get should fetch directly from cache without hitting database
        $this->assertSame('cached_value_1', site_setting_get('test_key_1'));
        $this->assertSame('cached_value_2', site_setting_get('test_key_2'));
    }

    public function testSiteSettingGetReturnsDefaultWhenNotCached(): void
    {
        // Pre-populate the cache manuals with specific keys
        $GLOBALS['_site_settings_cache'] = [
            'test_key_1' => 'cached_value_1'
        ];

        // Asking for an uncached key should fall back to the default value
        $this->assertSame('my_default', site_setting_get('uncached_key', 'my_default'));
        $this->assertNull(site_setting_get('uncached_key'));
    }

    public function testSiteSettingSetSynchronizesCache(): void
    {
        // Initialize cache
        $GLOBALS['_site_settings_cache'] = [
            'test_key' => 'initial'
        ];

        try {
            // This might trigger a database connection error because no live DB is running.
            // But we catch the exception to verify that the cache synchronization still runs
            // or we check if the cache update part is executed successfully.
            site_setting_set('test_key', 'updated_value');
        } catch (Throwable) {
            // Ignore database exception in the test environment
        }

        // The cache should still have been updated/populated
        $this->assertSame('updated_value', $GLOBALS['_site_settings_cache']['test_key']);
    }
}
