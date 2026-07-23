<?php
/**
 * Hardware ID Generator - No Database Dependency
 * This file should work without database connection
 */

class HardwareID {
    
    // SECRET SALT - CHANGE THIS TO YOUR OWN UNIQUE VALUE!
    private static $secret = 'YourSuperSecretSalt_H3r3!@#$%^&*_2026';
    
    /**
     * Get Motherboard Serial Number
     */
    public static function getMotherboardSerial() {
        $serial = 'UNKNOWN';
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $output = shell_exec('wmic baseboard get serialnumber 2>&1');
            if ($output) {
                $lines = explode("\n", trim($output));
                if (count($lines) >= 2) {
                    $serial = trim($lines[1]);
                }
            }
        } else {
            if (file_exists('/sys/class/dmi/id/board_serial')) {
                $serial = trim(file_get_contents('/sys/class/dmi/id/board_serial'));
            }
        }
        
        // Filter out invalid values
        if (empty($serial) || $serial == 'To be filled by O.E.M.' || $serial == 'Default string') {
            $serial = 'UNKNOWN';
        }
        
        return $serial;
    }
    
    /**
     * Get CPU Serial Number / Processor ID
     */
    public static function getCpuSerial() {
        $serial = 'UNKNOWN';
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $output = shell_exec('wmic cpu get processorid 2>&1');
            if ($output) {
                $lines = explode("\n", trim($output));
                if (count($lines) >= 2) {
                    $serial = trim($lines[1]);
                }
            }
        } else {
            if (file_exists('/proc/cpuinfo')) {
                $cpuinfo = file_get_contents('/proc/cpuinfo');
                preg_match('/serial\s*:\s*([^\n]+)/', $cpuinfo, $matches);
                $serial = $matches[1] ?? 'UNKNOWN';
            }
        }
        
        return $serial;
    }
    
    /**
     * Generate Hardware ID using CPU + Motherboard + SECRET
     */
    public static function generateHardwareId() {
        $cpu = self::getCpuSerial();
        $mb = self::getMotherboardSerial();
        
        $combined = $cpu . '|' . $mb . '|' . self::$secret;
        $hardware_id = hash('sha256', $combined);
        
        return [
            'hardware_id' => $hardware_id,
            'components' => [
                'cpu' => $cpu,
                'motherboard' => $mb
            ]
        ];
    }
    
    /**
     * Verify if current hardware matches stored ID
     */
    public static function verify($stored_id) {
        $current = self::generateHardwareId()['hardware_id'];
        return hash_equals($stored_id, $current);
    }
    
    /**
     * Generate a license key for this hardware
     */
    public static function generateLicenseKey() {
        $hardware = self::generateHardwareId();
        $data = $hardware['hardware_id'] . '|' . self::$secret;
        return hash('sha256', $data);
    }
}
?>