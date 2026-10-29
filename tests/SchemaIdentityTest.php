<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/schema.php';

final class SchemaIdentityTest extends TestCase
{
    public function testWhatsappFallbackIsIndependentOfTheCallNumber(): void
    {
        $before = $GLOBALS['_site_settings_cache'] ?? null;
        try {
            foreach ([[], ['contact_whatsapp_number' => '']] as $values) {
                $GLOBALS['_site_settings_cache'] = ['contact_phone_primary' => '+44 20 7946 0123'] + $values;
                $this->assertSame(WHATSAPP_NUMBER, site_whatsapp_display_number());
                $this->assertSame(normalize_whatsapp_number(WHATSAPP_NUMBER), site_whatsapp_number());
                $this->assertNotSame(site_primary_phone(), site_whatsapp_display_number());
            }
            $GLOBALS['_site_settings_cache'] = ['contact_phone_primary' => '+94 77 949 2427', 'contact_whatsapp_number' => '+94 76 919 2427'];
            $this->assertStringStartsWith('https://wa.me/94769192427?', site_whatsapp_url());
            $this->assertSame('+94 77 949 2427', schema_travel_agency()['telephone']);
        } finally {
            if ($before === null) { unset($GLOBALS['_site_settings_cache']); }
            else { $GLOBALS['_site_settings_cache'] = $before; }
        }
    }

    public function testTravelAgencySchemaUsesProvidedIdentityWithoutUnsupportedClaims(): void
    {
        $schema = schema_travel_agency();

        $this->assertSame('Island Culture Travels', $schema['name']);
        $this->assertSame('163/3 Niwasa Udyana Mawatha', $schema['address']['streetAddress']);
        $this->assertSame('Raddolugama (Katunayake)', $schema['address']['addressLocality']);
        $this->assertSame('11390', $schema['address']['postalCode']);
        $this->assertArrayNotHasKey('alternateName', $schema);
        $this->assertArrayNotHasKey('priceRange', $schema);
    }
}
