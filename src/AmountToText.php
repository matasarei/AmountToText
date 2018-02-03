<?php
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by 
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Amount to text converter
 * 
 * @author  Yevhen Matasar <matasar.ei@gmail.com>
 * @version 2018020300
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class AmountToText 
{
    /** @var float $amount */
    private $amount = 0.00;

    /** @var string $countryData */
    private $countryData = null;

    /** @var string $country */
    private static $country = 'us';

    /** @const int Price / money */
    const RESULT_NORMAL = 1;
    
    /** @const int Normal + ucfirst */
    const RESULT_FIRST = 2;
    
    /** @const int Normal + uppercase */
    const RESULT_UPPER = 3;
    
    /** @const int Decimal part only */
    const RESULT_DECIMAL = 4;
    
    /** @const int Items (integer values only) */
    const RESULT_ITEMS = 5;
    
    /** @var int $result */
    private $result = self::RESULT_NORMAL;

    /**
     * Set result mode
     * 
     * @param int $mode RESULT_* const
     */
    function setResultMode($mode)
    {
        $this->result = (int) $mode;
    }

    /**
     * @param float $amount Amount
     * @param string $country Country iso code
     */
    function __construct($amount = 0, $country = null) 
    {
        if (function_exists('locale_get_default')) {
            self::$country = preg_replace("/^([a-z]{2,})(.*)/i", "$1", locale_get_default(), -1);
        }

        $this->setAmount($amount);
        $this->setCountry($country ? (string) $country : self::$country);
    }

    /**
     * @return string
     */
    function __toString()
    {
        return $this->convert();
    }

    /**
     * Set current country code
     * 
     * @param string $country Country iso code (ru, ua, us)
     */
    function setCountry($country)
    {
        $config = __DIR__ . '/' . __CLASS__ . "_{$country}.json";
        if (!is_readable($config)) {
            throw new Exception('Country config is not readable or does not exists');
        }

        $this->countryData = json_decode(file_get_contents($config));
        if (!$this->countryData) {
            throw new Exception('Country config is empty or corrupted');
        }
    }

    /**
     * Amount setter
     * 
     * @param float $amount Amount
     */
    function setAmount($amount) 
    {
        $this->amount = (float) $amount;
    }

    /**
     * Fix offset of a country config array
     * to make it compatible for the convert algorithm
     */
    private function fixOffset(array $array, $offset = 1) 
    {
        return array_merge(array_fill(0, $offset, null), $array);
    }

    /**
     * Return amount as text
     * @link https://habrahabr.ru/post/53210/
     * @author runcore
     * 
     * @return string 
     */
    function convert() 
    {
        //strings
        $strings = $this->countryData;
        $ten = [
            $this->fixOffset($strings->unit[0]),
            $this->fixOffset($strings->unit[1])
        ];
        $a20 = $strings->ten;
        $tens = $this->fixOffset($strings->tens, 2);
        $hundred = $this->fixOffset($strings->hundred);
        $unit = $strings->units;

        //convert
        list($int, $dec) = preg_split("/[,.]/", sprintf("%015.2f", floatval($this->amount)), -1, PREG_SPLIT_NO_EMPTY);

        $decimal = '';
        
        if ($this->result !== self::RESULT_ITEMS) {
            $decimal = $dec . $this->morph($dec, $unit[0][0], $unit[0][1], $unit[0][2]); // decimal
            
            if ($this->result === self::RESULT_DECIMAL) {
                return $decimal;
            }
        }

        $out = [];
        if (intval($int) > 0) {
            $splited = str_split($int, 3);
            foreach ($splited as $uk => $v) { // by 3 symbols
                
                if (!intval($v)) {
                    continue;
                }
                
                $last = sizeof($splited) == $uk + 1;
                $uk = sizeof($unit) - $uk - 1; // unit key
                $gender = $unit[$uk][3];
                
                list($i1, $i2, $i3) = array_map('intval', str_split($v, 1));
                
                $out[] = $hundred[$i1]; // 1xx-9xx
                
                if ($i2 > 1) {
                    $last && $out[] = $strings->htseparator;
                    $part = $tens[$i2]; // 20-99
                    $i3 && $part .= $strings->tseparator . $ten[$gender][$i3];
                    $out[] = $part;
                } else {
                    $out[] = $i2 > 0 ? $a20[$i3] : $ten[$gender][$i3];# 10-19 | 1-9
                }
                
                if ($uk > 1 && $this->result !== self::RESULT_ITEMS) {
                    $out[] = $this->morph($v, $unit[$uk][0], $unit[$uk][1], $unit[$uk][2]);
                }
            } //foreach
        } else {
            $out[] = $strings->nul;
        }
        
        if ($this->result !== self::RESULT_ITEMS) {
            $out[] = $strings->dcseparator;
            $out[] = $this->morph(intval($int), $unit[1][0], $unit[1][1], $unit[1][2]) . " {$decimal}";
        }
        
        $text = trim(preg_replace('/\s{2,}/', ' ', join(' ', $out)));
        
        if ($this->result === self::RESULT_FIRST) {
            preg_match("/(\w)(.*)/ui", $text, $parts, null, 0);
            return mb_convert_case($parts[1], MB_CASE_TITLE, 'UTF-8') . $parts[2];
        }
        
        if ($this->result === self::RESULT_UPPER) {
            return mb_convert_case($text, MB_CASE_UPPER, 'UTF-8');
        }
        
        return $text;
    }

    /**
     * Склоняем словоформу
     * @author runcore
     */
    private function morph($n, $f1, $f2, $f5) 
    {
        $n = abs(intval($n)) % 100;
        
        if ($n > 10 && $n < 20) {
            return $f5;
        }
        
        $n = $n % 10;
        
        if ($n > 1 && $n < 5) {
            return $f2;
        }
            
        if ($n == 1) {
            return $f1;
        }
        
        return $f5;
    }

}

/**
 * Convert amount to words
 * Procedural style
 * 
 * @param float $amount Amount
 * @param string $country Country iso code
 */
function amount_to_text($amount, $country = null) 
{
    return (string) new AmountToText($amount, $country);
}
