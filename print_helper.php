<?php
// res-pos - Reusable image printing functions

class PrintHelper {
    private $printer_ip;
    private $printer_port;
    private $font_path;
    
    public function __construct($ip = '192.168.1.87', $port = 9100) {
        $this->printer_ip = $ip;
        $this->printer_port = $port;
        $this->findFont();
    }
    
    private function findFont() {
        $fonts = [
            'C:/Windows/Fonts/arial.ttf',
            'C:/Windows/Fonts/tahoma.ttf',
            'C:/Windows/Fonts/msgothic.ttc'
        ];
        
        foreach ($fonts as $font) {
            if (file_exists($font)) {
                $this->font_path = $font;
                return;
            }
        }
        
        die("No font found");
    }
    
    public function printText($text, $font_size = 10, $width = 384) {
        // Create image
        $lines = explode("\n", $text);
        $line_height = $font_size + 5;
        $height = count($lines) * $line_height + 20;
        
        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, $width, $height, $white);
        
        // Draw text
        $y = 15;
        foreach ($lines as $line) {
            imagettftext($image, $font_size, 0, 10, $y, $black, $this->font_path, $line);
            $y += $line_height;
        }
        
        // Convert to ESC/POS
        $data = $this->imageToEscPos($image);
        
        // Send to printer
        return $this->send($data);
    }
    
    private function imageToEscPos($image) {
        $width = imagesx($image);
        $height = imagesy($image);
        $bytes_per_row = intval(($width + 7) / 8);
        
        $header = chr(29) . "v" . chr(48) . 
                  chr($bytes_per_row % 256) . 
                  chr(floor($bytes_per_row / 256)) . 
                  chr($height % 256) . 
                  chr(floor($height / 256));
        
        $bitmap_data = '';
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $bytes_per_row * 8; $x += 8) {
                $byte = 0;
                for ($b = 0; $b < 8; $b++) {
                    $px = $x + $b;
                    if ($px < $width) {
                        $pixel = imagecolorat($image, $px, $y);
                        $r = ($pixel >> 16) & 0xFF;
                        $g = ($pixel >> 8) & 0xFF;
                        $b = $pixel & 0xFF;
                        $byte = ($byte << 1) | (($r + $g + $b < 384) ? 1 : 0);
                    } else {
                        $byte = ($byte << 1) | 0;
                    }
                }
                $bitmap_data .= chr($byte);
            }
        }
        
        return $header . $bitmap_data;
    }
    
    private function send($data) {
        $fp = @fsockopen($this->printer_ip, $this->printer_port, $errno, $errstr, 10);
        if ($fp) {
            fwrite($fp, $data);
            fwrite($fp, chr(29) . "V" . chr(66) . chr(0)); // Cut
            fclose($fp);
            return true;
        }
        return false;
    }
}

// Example usage:
// $printer = new PrintHelper('192.168.1.87', 9100);
// $printer->printText("Your text here\nWith Arabic مرحبا");
?>