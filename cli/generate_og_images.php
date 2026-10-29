<?php
/**
 * Generate Open Graph images for published itineraries.
 *
 * Uses GD with imagettftext() and a bundled font so the output is consistent
 * regardless of what fonts are installed on the server.
 *
 * If no TTF font can be found, it gracefully falls back to imagestring().
 *
 * Run via: php cli/generate_og_images.php
 *
 * The output directory is public/assets/images/og/ (or PUBLIC_PATH/assets/images/og).
 * Image dimensions: 1200×630 px (Twitter/LinkedIn optimal).
 */

require_once __DIR__ . '/../config/bootstrap.php';

echo "Generating Open Graph images for itineraries...\n";

// ── Font setup ────────────────────────────────────────────────────────────────
// We bundle Open Sans (body) and Playfair Display (headings) — both SIL OFL.
// Download if not present.  Falls back to GD built-in fonts on any failure.

$fontDir = BASE_PATH . '/assets/fonts';
if (!is_dir($fontDir)) {
    mkdir($fontDir, 0755, true);
}

$fontRegular = $fontDir . '/OpenSans-Regular.ttf';
$fontBold    = $fontDir . '/OpenSans-Bold.ttf';
$fontItalic  = $fontDir . '/OpenSans-Italic.ttf';
$fontHeading = $fontDir . '/PlayfairDisplay-Bold.ttf';

$fontUrls = [
    $fontRegular => 'https://github.com/googlefonts/opensans/raw/main/fonts/ttf/OpenSans-Regular.ttf',
    $fontBold    => 'https://github.com/googlefonts/opensans/raw/main/fonts/ttf/OpenSans-Bold.ttf',
    $fontItalic  => 'https://github.com/googlefonts/opensans/raw/main/fonts/ttf/OpenSans-Italic.ttf',
    $fontHeading => 'https://raw.githubusercontent.com/googlefonts/playfair/main/fonts/static/PlayfairDisplay-Bold.ttf',
];

foreach ($fontUrls as $path => $url) {
    if (!is_file($path)) {
        echo "Downloading " . basename($path) . "...\n";
        $ch = curl_init($url);
        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
            ]);
            $data = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($status === 200 && $data !== false && strlen($data) > 1024) {
                file_put_contents($path, $data);
                echo "  -> saved\n";
            } else {
                echo "  -> download failed (HTTP $status), will use fallback\n";
            }
        }
    }
}

$hasFonts = is_file($fontRegular) && is_file($fontBold) && is_file($fontHeading);

// ── Colour palette (matches brand) ────────────────────────────────────────────
define('OG_BG_TOP',    [20, 52, 88]);   // #143458 deep blue
define('OG_BG_BOTTOM', [26, 95, 58]);   // #1a5f3a forest green
define('OG_GOLD',      [139, 125, 58]); // #8b7d3a warm gold
define('OG_WHITE',     [255, 255, 255]);
define('OG_CREAM',     [245, 240, 232]);
define('OG_DARK',      [15, 25, 40]);   // #0f1928

// ── Output directory ───────────────────────────────────────────────────────────
$ogDir = PUBLIC_PATH . '/assets/images/og';
if (!is_dir($ogDir)) {
    mkdir($ogDir, 0755, true);
}

// ── Logo ──────────────────────────────────────────────────────────────────────
$logoPath = PUBLIC_PATH . '/assets/images/island_culture_travels_logo.png';
$hasLogo = is_file($logoPath);

// ── Helpers ──────────────────────────────────────────────────────────────────

function og_gradient_image(int $width, int $height, array $top, array $bottom)
{
    $img = imagecreatetruecolor($width, $height);
    imagealphablending($img, false);
    imagesavealpha($img, true);

    for ($y = 0; $y < $height; $y++) {
        $t = $y / $height;
        $r = (int) ($top[0] + ($bottom[0] - $top[0]) * $t);
        $g = (int) ($top[1] + ($bottom[1] - $top[1]) * $t);
        $b = (int) ($top[2] + ($bottom[2] - $top[2]) * $t);
        $color = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $width, $y, $color);
    }

    return $img;
}

function og_hex_color(string $hex, $img): int
{
    $hex = ltrim($hex, '#');
    $r = (int) hexdec(substr($hex, 0, 2));
    $g = (int) hexdec(substr($hex, 2, 2));
    $b = (int) hexdec(substr($hex, 4, 2));
    return imagecolorallocate($img, $r, $g, $b);
}

function og_text(string $text, int $size, int $color, $img, string $font = ''): array
{
    $text = trim($text);
    if ($text === '') {
        return [0, 0, 0, 0];
    }

    if ($font !== '' && is_file($font)) {
        $bbox = imagettfbbox($size, 0, $font, $text);
        if ($bbox === false) {
            return [0, 0, 0, 0];
        }
        $width  = abs($bbox[2] - $bbox[0]);
        $height = abs($bbox[1] - $bbox[7]);
        imagettftext($img, $size, 0, 0, $height, $color, $font, $text);
        return [$width, $height, $bbox[0], $bbox[1]];
    }

    // Fallback to GD built-in font
    $len = strlen($text);
    $width  = imagefontwidth(5) * $len;
    $height = imagefontheight(5);
    $color  = $color & 0xFFFFFF; // strip alpha for imagestring
    imagestring($img, 5, 0, 0, $text, $color);
    return [$width, $height, 0, $height];
}

function og_wrap_text(string $text, int $maxWidth, int $size, string $font): array
{
    $words = preg_split('/\s+/', trim($text)) ?: [];
    $lines = [];
    $current = '';

    foreach ($words as $word) {
        $test = $current === '' ? $word : $current . ' ' . $word;
        if ($font !== '' && is_file($font)) {
            $bbox = @imagettfbbox($size, 0, $font, $test);
            $w = $bbox ? abs($bbox[2] - $bbox[0]) : strlen($test) * ($size * 0.55);
        } else {
            $w = strlen($test) * ($size * 0.55);
        }

        if ($w <= $maxWidth) {
            $current = $test;
        } else {
            if ($current !== '') {
                $lines[] = $current;
            }
            $current = $word;
        }
    }

    if ($current !== '') {
        $lines[] = $current;
    }

    return $lines;
}

function og_render_image(
    $img,
    string $title,
    string $summary,
    bool   $hasFonts,
    string $fontBold,
    string $fontRegular,
    bool   $hasLogo,
    string $logoPath
): void {
    $W = imagesx($img);
    $H = imagesy($img);

    // ── Decorative element: diagonal stripe ──────────────────────────────────
    $stripe = imagecreatetruecolor(400, $H);
    imagealphablending($stripe, false);
    imagesavealpha($stripe, true);
    $transparent = imagecolorallocatealpha($stripe, 0, 0, 0, 127);
    imagefill($stripe, 0, 0, $transparent);
    $gold = imagecolorallocate($stripe, ...OG_GOLD);
    // A subtle vertical gradient stripe on the right
    for ($x = 0; $x < 400; $x++) {
        $alpha = (int) (($x / 400) * 60); // 0→60
        $c = imagecolorallocatealpha($stripe, OG_GOLD[0], OG_GOLD[1], OG_GOLD[2], $alpha);
        imageline($stripe, $x, 0, $x, $H, $c);
    }
    imagecopy($img, $stripe, $W - 400, 0, 0, 0, 400, $H);
    imagedestroy($stripe);

    // ── Gold top bar ──────────────────────────────────────────────────────────
    $barH = 10;
    $barColor = og_hex_color('#8b7d3a', $img);
    imagefilledrectangle($img, 0, 0, $W, $barH, $barColor);

    // ── Logo ───────────────────────────────────────────────────────────────────
    $logoH = 0;
    if ($hasLogo) {
        $logo = null;
        // Try PNG first, then JPEG
        foreach ([$logoPath, str_replace('.png', '.jpg', $logoPath)] as $lp) {
            if (is_file($lp)) {
                $ext = strtolower(pathinfo($lp, PATHINFO_EXTENSION));
                if ($ext === 'png') {
                    $logo = @imagecreatefrompng($lp);
                } elseif ($ext === 'jpg' || $ext === 'jpeg') {
                    $logo = @imagecreatefromjpeg($lp);
                }
                break;
            }
        }
        if ($logo !== false && $logo !== null) {
            $lw = imagesx($logo);
            $lh = imagesy($logo);
            $targetH = 48;
            $scale = $targetH / $lh;
            $lw2 = (int) ($lw * $scale);
            $lh2 = $targetH;
            $resized = imagecreatetruecolor($lw2, $lh2);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $logo, 0, 0, 0, 0, $lw2, $lh2, $lw, $lh);
            // White logo via imagefilter (convert dark logo to white)
            imagefilter($resized, IMG_FILTER_GRAYSCALE);
            imagefilter($resized, IMG_FILTER_BRIGHTNESS, -100); // make near-black become white
            imagecopy($img, $resized, 60, 20, 0, 0, $lw2, $lh2);
            $logoH = $lh2 + 20;
            imagedestroy($resized);
            imagedestroy($logo);
        }
    }

    // ── Text layout ────────────────────────────────────────────────────────────
    $leftPad = $hasLogo ? 60 : 80;
    $topPad = $logoH > 0 ? $logoH + 30 : 100;

    // Label: "SRI LANKA ITINERARY"
    $labelText = '✦  SRI LANKA ITINERARY  ✦';
    $labelSize = $hasFonts ? 20 : 4;
    $labelColor = og_hex_color('#c9b87a', $img);
    if ($hasFonts) {
        imagettftext($img, $labelSize, 0, $leftPad, $topPad, $labelColor, $fontRegular, $labelText);
    } else {
        $labelColor = $labelColor & 0xFFFFFF;
        imagestring($img, 4, $leftPad, $topPad - 14, $labelText, $labelColor);
    }

    // Title (Playfair Display Bold, large)
    $titleSize = $hasFonts ? 64 : 5;
    $titleColor = imagecolorallocate($img, ...OG_WHITE);
    $maxTitleW = $W - $leftPad - 440; // leave room for stripe

    $titleLines = og_wrap_text($title, $maxTitleW, $titleSize, $hasFonts ? $fontBold : '');
    $lineH = $titleSize * 1.2;
    $titleY = $topPad + 30;

    foreach ($titleLines as $i => $line) {
        $y = $titleY + $i * (int) $lineH;
        if ($hasFonts) {
            imagettftext($img, $titleSize, 0, $leftPad, $y, $titleColor, $fontBold, $line);
        } else {
            imagestring($img, 5, $leftPad, $y - 14, substr($line, 0, 30), $titleColor & 0xFFFFFF);
        }
    }

    // Gold separator line
    $sepY = $titleY + count($titleLines) * (int) $lineH + 16;
    $sepW = 200;
    $sepColor = og_hex_color('#8b7d3a', $img);
    imagefilledrectangle($img, $leftPad, $sepY, $leftPad + $sepW, $sepY + 3, $sepColor);

    // Summary text
    $sumSize = $hasFonts ? 26 : 3;
    $sumColor = imagecolorallocate($img, ...OG_CREAM);
    $maxSumW = $W - $leftPad - 440;
    $sumLines = og_wrap_text($summary, $maxSumW, $sumSize, $hasFonts ? $fontRegular : '');

    $sumY = $sepY + 20;
    $sumLineH = $sumSize * 1.6;
    foreach (array_slice($sumLines, 0, 3) as $i => $line) {
        $y = $sumY + $i * (int) $sumLineH;
        if ($hasFonts) {
            imagettftext($img, $sumSize, 0, $leftPad, $y, $sumColor, $fontRegular, $line);
        } else {
            imagestring($img, 3, $leftPad, $y - 10, substr($line, 0, 60), $sumColor & 0xFFFFFF);
        }
    }

    // Bottom bar with CTA
    $ctaH = 80;
    $ctaY = $H - $ctaH;
    $ctaBg = og_hex_color('#0f1928', $img);
    imagefilledrectangle($img, 0, $ctaY, $W, $H, $ctaBg);

    $ctaColor = og_hex_color('#c9b87a', $img);
    $ctaText = 'islandculturetravels.com  ·  Private Sri Lanka Tours';
    $ctaSize = $hasFonts ? 24 : 3;
    if ($hasFonts) {
        $cbbox = @imagettfbbox($ctaSize, 0, $fontRegular, $ctaText);
        $cw = $cbbox ? abs($cbbox[2] - $cbbox[0]) : strlen($ctaText) * 12;
        imagettftext($img, $ctaSize, 0, (int) (($W - $cw) / 2), $ctaY + 50, $ctaColor, $fontRegular, $ctaText);
    } else {
        $ctaColor = $ctaColor & 0xFFFFFF;
        imagestring($img, 3, (int) (($W - strlen($ctaText) * 6) / 2), $ctaY + 28, $ctaText, $ctaColor);
    }

    // Bottom gold accent line
    $goldLine = og_hex_color('#8b7d3a', $img);
    imagefilledrectangle($img, 0, $ctaY, $W, $ctaY + 5, $goldLine);
}

/**
 * Render and save one OG image.
 */
function og_save_image(
    string $outputPath,
    string $title,
    string $summary,
    bool   $hasFonts,
    string $fontBold,
    string $fontRegular
): void {
    $W = 1200;
    $H = 630;

    $img = og_gradient_image($W, $H, OG_BG_TOP, OG_BG_BOTTOM);

    og_render_image(
        $img, $title, $summary,
        $hasFonts, $fontBold, $fontRegular,
        false, '' // no logo compositing for speed — logo is in white via CSS on the page
    );

    // Save as JPEG at 92% quality (good balance of size/clarity)
    imagejpeg($img, $outputPath, 92);
    imagedestroy($img);
}

// ── Main ───────────────────────────────────────────────────────────────────────

$itineraries = fetch_all(
    "SELECT id, name, slug, excerpt FROM itineraries WHERE is_published = 1"
);

$count = 0;
foreach ($itineraries as $itin) {
    $slug  = $itin['slug'];
    $title = trim((string) ($itin['name'] ?? $itin['title'] ?? ''));
    $summary = trim((string) ($itin['excerpt'] ?? $itin['summary'] ?? ''));
    if ($title === '') {
        continue;
    }

    $imgPath = $ogDir . '/' . $slug . '.jpg';

    $force = in_array('--force', $argv, true);
    if (!$force && is_file($imgPath)) {
        echo "  [skip] {$slug} — already exists\n";
        continue;
    }

    echo "  {$slug}...\n";

    og_save_image(
        $imgPath,
        $title,
        $summary,
        $hasFonts,
        $fontBold,
        $fontRegular
    );

    $size = is_file($imgPath) ? round(filesize($imgPath) / 1024, 1) : 0;
    echo "    -> {$imgPath}  ({$size} KB)\n";
    $count++;
}

echo "\nDone. {$count} image(s) generated.\n";
echo "Output: {$ogDir}\n";
