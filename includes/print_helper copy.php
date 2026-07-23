<?php
// includes/print_helper.php – Safe Arabic printing with mike42/escpos-php
require_once __DIR__ . '/../vendor/autoload.php';

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
    private function createPngFromText($text) {
        if (!extension_loaded('gd')) {
            error_log("GD extension not loaded");
            return false;
        }

        $lines = explode("\n", str_replace("\r", "", $text));
        if (empty($lines)) return false;

        // Find a font that supports Arabic
        $fontFile = $this->getFontFile();
        if (!$fontFile) {
            error_log("No Arabic font found");
            return false;
        }

        $fontSize = 12;
        $lineHeight = $fontSize + 4;
        $maxWidth = 550; // 80mm paper at 203 DPI

        // Calculate image height
        $totalHeight = count($lines) * $lineHeight + 20;
        
        // Create image
        $img = imagecreatetruecolor($maxWidth, $totalHeight);
        if (!$img) return false;
        
        // White background, black text
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefilledrectangle($img, 0, 0, $maxWidth, $totalHeight, $white);
        
        // Draw each line
        $y = 10;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                $y += $lineHeight;
                continue;
            }
            // For Arabic, ensure UTF-8. imagettftext handles RTL poorly but at least shows glyphs.
            imagettftext($img, $fontSize, 0, 10, $y, $black, $fontFile, $line);
            $y += $lineHeight;
        }
        
        // Save to temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'receipt_') . '.png';
        if (!imagepng($img, $tempFile)) {
            error_log("Failed to save PNG to $tempFile");
            imagedestroy($img);
            return false;
        }
        imagedestroy($img);
        
        // Verify file is not empty
        if (filesize($tempFile) == 0) {
            unlink($tempFile);
            return false;
        }
        
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