<?php

namespace App\Services;

/**
 * Generates 1200×630 OG images for posts that have no featured image.
 * Images are cached in public/og/ (direct web-accessible, no routing needed).
 */
class OgImageService
{
    private string $outDir;
    private string $outUrl = 'og/';

    public function __construct()
    {
        $this->outDir = FCPATH . 'og/';
        if (! is_dir($this->outDir)) {
            mkdir($this->outDir, 0755, true);
        }
    }

    /**
     * Return a URL to a cached OG image for the given title.
     * Generates one on first call, then serves from cache.
     */
    public function generate(string $title, string $slug): string
    {
        if (! extension_loaded('gd')) {
            return '';
        }

        $filename = md5($title . '|' . (setting('App.siteName') ?? '')) . '.png';
        $path     = $this->outDir . $filename;

        if (! file_exists($path)) {
            $this->createImage($title, $path);
        }

        return file_exists($path) ? base_url($this->outUrl . $filename) : '';
    }

    /**
     * Delete a cached image (call when post title changes).
     */
    public function bust(string $title): void
    {
        $filename = md5($title . '|' . (setting('App.siteName') ?? '')) . '.png';
        $path     = $this->outDir . $filename;
        if (file_exists($path)) {
            unlink($path);
        }
    }

    // -------------------------------------------------------------------------

    private function createImage(string $title, string $outPath): void
    {
        $w   = 1200;
        $h   = 630;
        $img = imagecreatetruecolor($w, $h);

        // Vertical gradient: #1a2744 → #2a4a8a
        for ($y = 0; $y < $h; $y++) {
            $t = $y / $h;
            $r = (int) (26  + (42  - 26)  * $t);
            $g = (int) (39  + (74  - 39)  * $t);
            $b = (int) (68  + (138 - 68)  * $t);
            $c = imagecolorallocate($img, $r, $g, $b);
            imagefilledrectangle($img, 0, $y, $w, $y, $c);
        }

        // Accent strip at bottom
        $accent = imagecolorallocate($img, 78, 115, 223); // #4e73df
        imagefilledrectangle($img, 0, $h - 10, $w, $h, $accent);

        $white  = imagecolorallocate($img, 255, 255, 255);
        $silver = imagecolorallocate($img, 160, 190, 230);

        $siteName = setting('App.siteName') ?? 'Pubvana';
        $font     = $this->findFont();

        if ($font) {
            $this->drawTtf($img, $title, $siteName, $font, $w, $h, $white, $silver);
        } else {
            $this->drawBuiltin($img, $title, $siteName, $w, $h, $white, $silver);
        }

        imagepng($img, $outPath);
        imagedestroy($img);
    }

    private function drawTtf(
        \GdImage $img,
        string $title,
        string $siteName,
        string $font,
        int $w,
        int $h,
        int $white,
        int $silver
    ): void {
        // Site name — top-left
        imagettftext($img, 26, 0, 60, 90, $silver, $font, $siteName);

        // Divider line
        $accentLine = imagecolorallocate($img, 78, 115, 223);
        imagefilledrectangle($img, 60, 110, 200, 114, $accentLine);

        // Title — word-wrapped, vertically centred in lower portion
        $fontSize = 58;
        $maxWidth = $w - 120;
        $lines    = $this->wrapTtf($title, $font, $fontSize, $maxWidth);

        // Reduce font size if too many lines
        while (count($lines) > 3 && $fontSize > 32) {
            $fontSize -= 6;
            $lines = $this->wrapTtf($title, $font, $fontSize, $maxWidth);
        }

        $lineHeight = (int) ($fontSize * 1.35);
        $totalH     = count($lines) * $lineHeight;
        $startY     = (int) (($h - $totalH) / 2 + 80);  // push slightly below centre

        foreach ($lines as $i => $line) {
            $bbox = imagettfbbox($fontSize, 0, $font, $line);
            $tw   = abs($bbox[4] - $bbox[0]);
            $x    = (int) (($w - $tw) / 2);
            $y    = $startY + ($i + 1) * $lineHeight;
            imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $line);
        }
    }

    private function drawBuiltin(
        \GdImage $img,
        string $title,
        string $siteName,
        int $w,
        int $h,
        int $white,
        int $silver
    ): void {
        // Built-in GD font 5 is ~9×15px — scale up the image then resize for better quality
        $scale = 3;
        $sw    = $w / $scale;
        $sh    = $h / $scale;
        $small = imagecreatetruecolor((int) $sw, (int) $sh);

        // Vertical gradient on small canvas
        for ($y = 0; $y < $sh; $y++) {
            $t  = $y / $sh;
            $r  = (int) (26 + (42  - 26)  * $t);
            $g  = (int) (39 + (74  - 39)  * $t);
            $b  = (int) (68 + (138 - 68)  * $t);
            $c  = imagecolorallocate($small, $r, $g, $b);
            imagefilledrectangle($small, 0, $y, (int) $sw, $y, $c);
        }

        $ws   = imagecolorallocate($small, 255, 255, 255);
        $ss   = imagecolorallocate($small, 160, 190, 230);
        $fh   = imagefontheight(5);
        $fw   = imagefontwidth(5);

        // Site name
        imagestring($small, 5, 20, 20, $siteName, $ss);

        // Wrap title
        $maxCols = (int) (($sw - 40) / $fw);
        $lines   = $this->wrapText($title, $maxCols);
        $startY  = (int) (($sh / 2) - (count($lines) * ($fh + 4) / 2)) + 10;

        foreach ($lines as $i => $line) {
            $tw = strlen($line) * $fw;
            $x  = (int) (($sw - $tw) / 2);
            imagestring($small, 5, $x, $startY + $i * ($fh + 4), $line, $ws);
        }

        // Accent strip
        $ac = imagecolorallocate($small, 78, 115, 223);
        imagefilledrectangle($small, 0, (int) $sh - 4, (int) $sw, (int) $sh, $ac);

        // Copy scaled-up version to main canvas
        imagecopyresampled($img, $small, 0, 0, 0, 0, $w, $h, (int) $sw, (int) $sh);
        imagedestroy($small);
    }

    // -------------------------------------------------------------------------

    private function findFont(): ?string
    {
        $candidates = [
            // Project bundled (drop any .ttf here)
            FCPATH . 'assets/fonts/Inter-Bold.ttf',
            FCPATH . 'assets/fonts/OpenSans-Bold.ttf',
            FCPATH . 'assets/fonts/Roboto-Bold.ttf',
            // Common Linux paths
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
            '/usr/share/fonts/truetype/ubuntu/Ubuntu-B.ttf',
            '/usr/share/fonts/truetype/noto/NotoSans-Bold.ttf',
            '/usr/share/fonts/truetype/open-sans/OpenSans-Bold.ttf',
            // macOS
            '/System/Library/Fonts/Helvetica.ttc',
            '/Library/Fonts/Arial Bold.ttf',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function wrapTtf(string $text, string $font, int $size, int $maxWidth): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $line  = '';

        foreach ($words as $word) {
            $test = $line !== '' ? ($line . ' ' . $word) : $word;
            $bbox = imagettfbbox($size, 0, $font, $test);
            $tw   = abs($bbox[4] - $bbox[0]);

            if ($tw > $maxWidth && $line !== '') {
                $lines[] = $line;
                $line    = $word;
            } else {
                $line = $test;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines ?: [''];
    }

    private function wrapText(string $text, int $maxCols): array
    {
        $words = explode(' ', $text);
        $lines = [];
        $line  = '';

        foreach ($words as $word) {
            $test = $line !== '' ? ($line . ' ' . $word) : $word;
            if (strlen($test) > $maxCols && $line !== '') {
                $lines[] = $line;
                $line    = $word;
            } else {
                $line = $test;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines ?: [''];
    }
}
