<?php
// includes/print_helper.php – Safe Arabic printing with mike42/escpos-php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/arabic_shaper.php';

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\CapabilityProfile;
use Mike42\Escpos\EscposImage;

class PrintHelper {
    private $ip;
    private $port;

    public function __construct($ip, $port = 9100) {
        $this->ip = $ip;
        $this->port = $port;
    }

    /**
     * Print Arabic text as image using the library
     */
    public function printTextAsImage($text) {
        try {
            // 1. Create a PNG image from text using GD
            $pngPath = $this->createPngFromText($text);
            if (!$pngPath) {
                error_log("Failed to create PNG from text");
                return false;
            }

            // 2. Load image with EscposImage (auto-slicing)
            $image = EscposImage::load($pngPath);

            // 3. Connect and print
            $connector = new NetworkPrintConnector($this->ip, $this->port);
            $profile = CapabilityProfile::load("simple");
            $printer = new Printer($connector, $profile);
            
            $printer->bitImage($image);
            $printer->cut();
            $printer->close();
            
            unlink($pngPath);
            return true;
            
        } catch (Exception $e) {
            error_log("Image print error: " . $e->getMessage());
            if (isset($printer)) $printer->close();
            return false;
        }
    }

    /**
     * Create a PNG from multi-line text using GD
     * Returns file path or false
     */
    public function createPngFromText($text, $fontSize = 18, $maxWidth = 576, $useBold = true, $alignRight = false) {
        if (!extension_loaded('gd')) {
            error_log("GD extension not loaded");
            return false;
        }

        require_once __DIR__ . '/arabic_shaper.php';

        // Split text into lines
        $lines = explode("\n", str_replace("\r", "", $text));
        if (empty($lines)) return false;

        // Font file: use bold if requested and exists
        $fontFile = 'C:/Windows/Fonts/segoeui.ttf';
        if ($useBold && file_exists('C:/Windows/Fonts/segoeui.ttf')) {
            $fontFile = 'C:/Windows/Fonts/segoeui.ttf';
        }
        if (!file_exists($fontFile)) {
            $fontFile = 'C:/Windows/Fonts/segoeui.ttf';
        }
        if (!file_exists($fontFile)) {
            error_log("No TTF font found");
            return false;
        }

        $lineHeight = $fontSize + 6;   // spacing between lines
        $padding = 15;                 // left/right margin

        // Pre‑calculate total image height
        $totalHeight = $padding;
        $tempImg = imagecreatetruecolor($maxWidth, 100);
        $tempDraw = imagecolorallocate($tempImg, 255, 255, 255);
        imagedestroy($tempImg);
        // We'll just approximate height with lineHeight * number of lines
        // More accurate: we could measure each line, but for simplicity:
        $totalHeight = $padding + count($lines) * $lineHeight;

        // Create the actual image
        $img = imagecreatetruecolor($maxWidth, $totalHeight);
        if (!$img) return false;

        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefilledrectangle($img, 0, 0, $maxWidth, $totalHeight, $white);

        $y = $padding;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                $y += $lineHeight;
                continue;
            }

            // Apply Arabic reshaping if needed and right‑alignment requested
            if ($alignRight && preg_match('/[\x{0600}-\x{06FF}]/u', $line)) {
                $line = ArabicShaper::reshape($line);
                // Reverse the UTF-8 string (only if still inverted)
                preg_match_all('/./us', $line, $ar);
                $line = implode('', array_reverse($ar[0]));
            }

            // Get text bounding box
            $bbox = imagettfbbox($fontSize, 0, $fontFile, $line);
            $textWidth = abs($bbox[2] - $bbox[0]);

            // Calculate X position (right‑align or left‑align)
            if ($alignRight) {
                $x = $maxWidth - $textWidth - $padding;
            } else {
                $x = $padding;
            }

            // Draw the text
            imagettftext($img, $fontSize, 0, $x, $y, $black, $fontFile, $line);
            $y += $lineHeight;
        }

        // Save to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'receipt_') . '.png';
        if (!imagepng($img, $tempFile)) {
            imagedestroy($img);
            return false;
        }
        imagedestroy($img);
        return $tempFile;
    }

    private function getFontFile() {
        $possibleFonts = [
            'C:/Windows/Fonts/arial.ttf',
            'C:/Windows/Fonts/tahoma.ttf',
            'C:/Windows/Fonts/consola.ttf',
            'C:/Windows/Fonts/simsun.ttc',
        ];
        foreach ($possibleFonts as $font) {
            if (file_exists($font)) return $font;
        }
        return false;
    }
}
?>