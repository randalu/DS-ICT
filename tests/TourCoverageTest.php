<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/tour_coverage.php';

final class TourCoverageTest extends TestCase
{
    private function card(array $changes = []): array
    {
        return $changes + ['title' => 'Sunrise safari', 'location' => 'Yala National Park', 'description' => 'A private morning game drive.', 'status' => 'proposed'];
    }

    public function testOrderedCardsKeepTheirOwnLocationsLinksAndOptionalStatus(): void
    {
        $resolved = [];
        $cards = tour_coverage_normalize([
            8 => $this->card(['destination_id' => '12', 'image_asset_id' => '9', 'image_alt' => 'Elephants beside a Yala waterhole']),
            2 => $this->card(['title' => 'Tea tasting', 'location' => 'Nuwara Eliya', 'experience_id' => '4', 'status' => 'optional']),
        ], static function ($type, $id) use (&$resolved): array { $resolved[] = [$type, $id]; return ['id' => $id]; });
        $this->assertSame(['Sunrise safari', 'Tea tasting'], array_column($cards, 'title'));
        $this->assertSame('optional', $cards[1]['status']);
        $this->assertSame([['destination', 12], ['image', 9], ['experience', 4]], $resolved);
        $this->assertSame(0, $cards[1]['image_asset_id']);
        $this->assertArrayNotHasKey('price_from', $cards[0]);
    }

    public function testEmptyEditorCanExplicitlyClearCardsWithoutResolvingAnything(): void
    {
        $this->assertSame([], tour_coverage_normalize([], static function (): never { throw new LogicException('Unexpected reference lookup'); }));
    }

    public function testMalformedOversizedAndIncompleteCardsAreRejected(): void
    {
        $bad = [null, '[]', array_fill(0, 41, $this->card()), ['invalid'], [$this->card(['title' => ''])], [$this->card(['location' => ''])], [$this->card(['description' => []])], [$this->card(['title' => str_repeat('x', 181)])], [$this->card(['description' => '<script>alert(1)</script>'])], [$this->card(['location' => "Yala\0Park"])], [$this->card(['status' => 'guaranteed'])], [$this->card(['destination_id' => '-1'])], [$this->card(['experience_id' => '1.5'])], [$this->card(['image_asset_id' => ['9']])]];
        foreach ($bad as $input) {
            try {
                tour_coverage_normalize($input, static fn(): array => ['id' => 1]);
                $this->fail('Invalid coverage accepted.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testForgedReferencesAndImagesWithoutAltTextAreRejected(): void
    {
        foreach (['destination_id', 'experience_id', 'image_asset_id'] as $key) {
            try {
                tour_coverage_normalize([$this->card([$key => '999'])], static fn(): null => null);
                $this->fail('Missing reference accepted.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->expectException(InvalidArgumentException::class);
        tour_coverage_normalize([$this->card(['image_asset_id' => '1'])], static fn(): array => ['id' => 1]);
    }

    public function testBrokenLegacyJsonDoesNotBreakThePublicLayout(): void
    {
        foreach ([null, '', 'broken', '{}', '"text"', '[null, 2, {"title": []}]'] as $json) {
            $this->assertSame([], tour_coverage_decode($json));
        }
        $this->assertCount(1, tour_coverage_decode(json_encode([$this->card()], JSON_THROW_ON_ERROR)));
    }

    public function testRevisionDetectsAnyCoverageChange(): void
    {
        $this->assertSame(tour_coverage_revision(null), tour_coverage_revision(''));
        $this->assertNotSame(tour_coverage_revision('[]'), tour_coverage_revision('[{"title":"Changed"}]'));
    }
}
