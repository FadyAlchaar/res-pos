<?php
/**
 * ArabicShaper – Converts Arabic text to presentation forms.
 * Uses Unicode presentation forms (U+FB50..U+FEFF).
 * Order: [isolated, final, initial, medial]
 */
class ArabicShaper
{
    // Mapping: base character -> [isolated, final, initial, medial]
    private static $map = [
        'ء' => ['FE80', 'FE80', 'FE80', 'FE80'], // Hamza
        'آ' => ['FE81', 'FE82', 'FE81', 'FE82'], // Alef with Madda
        'أ' => ['FE83', 'FE84', 'FE83', 'FE84'], // Alef with Hamza above
        'ؤ' => ['FE85', 'FE86', 'FE85', 'FE86'], // Waw with Hamza
        'إ' => ['FE87', 'FE88', 'FE87', 'FE88'], // Alef with Hamza below
        'ئ' => ['FE89', 'FE8A', 'FE8B', 'FE8C'], // Yeh with Hamza
        'ا' => ['FE8D', 'FE8E', 'FE8D', 'FE8E'], // Alef
        'ب' => ['FE8F', 'FE90', 'FE91', 'FE92'], // Beh
        'ة' => ['FE93', 'FE94', 'FE93', 'FE94'], // Teh Marbuta
        'ت' => ['FE95', 'FE96', 'FE97', 'FE98'], // Teh
        'ث' => ['FE99', 'FE9A', 'FE9B', 'FE9C'], // Theh
        'ج' => ['FE9D', 'FE9E', 'FE9F', 'FEA0'], // Jeem
        'ح' => ['FEA1', 'FEA2', 'FEA3', 'FEA4'], // Hah
        'خ' => ['FEA5', 'FEA6', 'FEA7', 'FEA8'], // Khah
        'د' => ['FEA9', 'FEAA', 'FEA9', 'FEAA'], // Dal (no initial/medial)
        'ذ' => ['FEAB', 'FEAC', 'FEAB', 'FEAC'], // Thal
        'ر' => ['FEAD', 'FEAE', 'FEAD', 'FEAE'], // Reh
        'ز' => ['FEAF', 'FEB0', 'FEAF', 'FEB0'], // Zain
        'س' => ['FEB1', 'FEB2', 'FEB3', 'FEB4'], // Seen
        'ش' => ['FEB5', 'FEB6', 'FEB7', 'FEB8'], // Sheen
        'ص' => ['FEB9', 'FEBA', 'FEBB', 'FEBC'], // Sad
        'ض' => ['FEBD', 'FEBE', 'FEBF', 'FEC0'], // Dad
        'ط' => ['FEC1', 'FEC2', 'FEC3', 'FEC4'], // Tah
        'ظ' => ['FEC5', 'FEC6', 'FEC7', 'FEC8'], // Zah
        'ع' => ['FEC9', 'FECA', 'FECB', 'FECC'], // Ain
        'غ' => ['FECD', 'FECE', 'FECF', 'FED0'], // Ghain
        'ف' => ['FED1', 'FED2', 'FED3', 'FED4'], // Feh
        'ق' => ['FED5', 'FED6', 'FED7', 'FED8'], // Qaf
        'ك' => ['FED9', 'FEDA', 'FEDB', 'FEDC'], // Kaf
        'ل' => ['FEDD', 'FEDE', 'FEDF', 'FEE0'], // Lam
        'م' => ['FEE1', 'FEE2', 'FEE3', 'FEE4'], // Meem
        'ن' => ['FEE5', 'FEE6', 'FEE7', 'FEE8'], // Noon
        'ه' => ['FEE9', 'FEEA', 'FEEB', 'FEEC'], // Heh
        'و' => ['FEED', 'FEEE', 'FEED', 'FEEE'], // Waw
        'ى' => ['FEEF', 'FEF0', 'FEEF', 'FEF0'], // Alef Maksura
        'ي' => ['FEF1', 'FEF2', 'FEF3', 'FEF4'], // Yeh
        // Ligatures
        'لا' => ['FEF5', 'FEF6', 'FEF5', 'FEF6'],
        'لأ' => ['FEF7', 'FEF8', 'FEF7', 'FEF8'],
        'لإ' => ['FEF9', 'FEFA', 'FEF9', 'FEFA'],
        'لآ' => ['FEFB', 'FEFC', 'FEFB', 'FEFC'],
    ];
    // Characters that never connect to the next character (non‑joining)
    private static $nonConnecting = [
        'ء', 'آ', 'أ', 'ؤ', 'إ', 'ئ', 'ا', 'د', 'ذ', 'ر', 'ز', 'و', 'ى'
    ];

    /**
     * Reshape Arabic text to presentation forms (logical order).
     */
    public static function reshape($text)
    {
        $output = '';
        $len = mb_strlen($text, 'UTF-8');
        $i = 0;
        while ($i < $len) {
            // Check for two‑character ligatures
            if ($i + 1 < $len) {
                $two = mb_substr($text, $i, 2, 'UTF-8');
                if (isset(self::$map[$two])) {
                    // Use isolated form (index 0) – ligatures are context‑insensitive
                    $output .= self::unicodeChar(self::$map[$two][0]);
                    $i += 2;
                    continue;
                }
            }

            $char = mb_substr($text, $i, 1, 'UTF-8');
            if (isset(self::$map[$char])) {
                $prev = ($i > 0) ? mb_substr($text, $i - 1, 1, 'UTF-8') : null;
                $next = ($i + 1 < $len) ? mb_substr($text, $i + 1, 1, 'UTF-8') : null;
                $form = self::getForm($char, $prev, $next);
                $output .= self::unicodeChar(self::$map[$char][$form]);
            } else {
                $output .= $char;
            }
            $i++;
        }
        return $output;
    }

    /**
     * Determine form: 0=isolated, 1=final, 2=initial, 3=medial.
     */
    private static function getForm($char, $prevChar, $nextChar)
    {
        $prevConnects = $prevChar && isset(self::$map[$prevChar]) && !in_array($prevChar, self::$nonConnecting);
        $nextConnects = $nextChar && isset(self::$map[$nextChar]) && !in_array($nextChar, self::$nonConnecting);

        if ($prevConnects && $nextConnects) return 3; // medial
        if ($prevConnects && !$nextConnects) return 1; // final
        if (!$prevConnects && $nextConnects) return 2; // initial
        return 0; // isolated
    }

    private static function unicodeChar($hex)
    {
        return html_entity_decode('&#x' . $hex . ';', ENT_NOQUOTES, 'UTF-8');
    }
}
?>