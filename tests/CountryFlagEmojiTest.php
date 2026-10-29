<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/functions.php';

class CountryFlagEmojiTest extends TestCase
{
    public function testValidCountryCode()
    {
        // US
        $this->assertEquals('🇺🇸', country_flag_emoji('US'));
        // LK
        $this->assertEquals('🇱🇰', country_flag_emoji('LK'));
        // GB
        $this->assertEquals('🇬🇧', country_flag_emoji('GB'));
    }

    public function testLowercaseCountryCode()
    {
        $this->assertEquals('🇺🇸', country_flag_emoji('us'));
    }

    public function testPaddedCountryCode()
    {
        $this->assertEquals('🇺🇸', country_flag_emoji(' US '));
    }

    public function testInvalidCountryCodeLength()
    {
        $this->assertEquals('', country_flag_emoji('USA'));
        $this->assertEquals('', country_flag_emoji('U'));
    }

    public function testInvalidCountryCodeCharacters()
    {
        $this->assertEquals('', country_flag_emoji('12'));
        $this->assertEquals('', country_flag_emoji('U-'));
        $this->assertEquals('', country_flag_emoji('$$'));
    }

    public function testNullCountryCode()
    {
        $this->assertEquals('', country_flag_emoji(null));
    }

    public function testEmptyString()
    {
        $this->assertEquals('', country_flag_emoji(''));
    }
}
