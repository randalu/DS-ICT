<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/functions.php';

class ExcerptWordsTest extends TestCase
{
    public function testReturnsEmptyStringForEmptyHtmlOrNonPositiveLimit(): void
    {
        $this->assertSame('', excerpt_words('   '));
        $this->assertSame('', excerpt_words('<p><br></p>'));
        $this->assertSame('', excerpt_words('Sri Lanka private tour planning', 0));
        $this->assertSame('', excerpt_words('Sri Lanka private tour planning', -3));
    }

    public function testReturnsCleanTextWhenWordCountDoesNotExceedLimit(): void
    {
        $this->assertSame(
            'Private Sri Lanka tours made easy.',
            excerpt_words("<p>Private\nSri Lanka   tours made easy.</p>", 6)
        );
    }

    public function testTruncatesAtWordBoundaryAndAddsEllipsis(): void
    {
        $this->assertSame(
            'Plan a flexible Sri Lanka...',
            excerpt_words('Plan a flexible Sri Lanka private chauffeur tour with beaches and wildlife.', 5)
        );
    }

    public function testPreservesMultibyteWordsWhileTruncating(): void
    {
        $this->assertSame(
            'Explore Sigiriya, Ella, and...',
            excerpt_words('Explore Sigiriya, Ella, and Galle with ආයුබෝවන් hospitality.', 4)
        );
    }
}
