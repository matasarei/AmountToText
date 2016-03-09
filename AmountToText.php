<?php
/**
 * Amount to text converter
 * 
 * @author Yevhen Matasar <matasar.ei@gmail.com>, 2016
 * @version 2016090300
 */
class AmountToText {
    
    /**
     * @var float $amount Amount
     */
    private $amount = 0.00;
    
    /**
     * @var string $langData Language Data
     */
    private $langData = null;
    
    /**
     * @var string $lang Default language
     */
    private static $lang = 'en';
    
    const RESULT_NORMAL = 1;
    const RESULT_FIRST = 2;
    const RESULT_UPPER = 3;
    const RESULT_DECIMAL = 4;
    /**
     * @var int $result Result type
     */
    private $result = self::RESULT_NORMAL;
    
    /**
     * Set result mode
     * @param int $mode RESULT_* const
     */
    function setResultMode($mode) {
        $this->result = (int)$mode;
    }
    
    /**
     * @param float $amount Amount
     * @param string $lang Language code
     */
    function __construct($amount = 0, $lang = null) {
        
        if (function_exists('locale_get_default')) {
            self::$lang = preg_replace("/^([a-z]{2,})(.*)/i", "$1", locale_get_default(), -1);   
        }
        
        $this->setAmount($amount);
        $this->setLanguage($lang ? (string)$lang : self::$lang);
    }
    
    function __toString() {
        return $this->convert();
    }
    
    /**
     * Set current language
     * @param string $lang Language code (uk, ru, en)
     */
    function setLanguage($lang) {
        $langfile = __DIR__ . '/' .  __CLASS__ . "_{$lang}.json";
        if (!is_readable($langfile)) {
            throw new Exception('Language file is not readable or does not exists');
        }
        
        $this->langData = json_decode(file_get_contents($langfile));
        if (!$this->langData) {
            throw new Exception('Language configuration is empty or language file is corrupted');
        }
    }
    
    /**
     * Amount setter
     * @param float $amount Amount
     */
    function setAmount($amount) {
        $this->amount = (float)$amount;
    }
    
    /**
     * Fix offset of a language array
     * to make it compatible for the convert algorithm
     */
    private function fixOffset(array $array, $offset = 1) {
        return array_merge(array_fill(0, $offset, null), $array);
    }
        
    /**
     * Return amount in words
     * @link https://habrahabr.ru/post/53210/
     * @author runcore
     */
    function convert() {
        
        //strings
        $strings = $this->langData;
    	$ten = [
            $this->fixOffset($strings->unit[0]),
            $this->fixOffset($strings->unit[1])
	    ];
    	$a20  = $strings->ten;
    	$tens = $this->fixOffset($strings->tens, 2);
    	$hundred = $this->fixOffset($strings->hundred);
    	$unit = $strings->units;
    	
    	//convert
    	list($int, $dec) = preg_split("/[,.]/", sprintf("%015.2f", floatval($this->amount)), -1, PREG_SPLIT_NO_EMPTY);
    	
    	$decimal = $dec . $this->morph($dec, $unit[0][0], $unit[0][1], $unit[0][2]); // decimal
    	if ($this->result == self::RESULT_DECIMAL) {
    	    return $decimal;
    	}

    	$out = [];
    	if (intval($int)>0) {
    	    $splited = str_split($int, 3);
    		foreach($splited as $uk => $v) { // by 3 symbols
    			if (!intval($v)) continue;
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
    			}
    			else $out[]= $i2 > 0 ? $a20[$i3] : $ten[$gender][$i3]; # 10-19 | 1-9
    			if ($uk>1) $out[]= $this->morph($v, $unit[$uk][0], $unit[$uk][1], $unit[$uk][2]);
    		} //foreach
    	}
    	else $out[] = $strings->nul;
    	$out[] = $this->morph(intval($int), $unit[1][0],$unit[1][1],$unit[1][2]) . " {$decimal}";
        $text = trim(preg_replace('/ {2,}/', ' ', join(' ', $out)));
        if ($this->result == self::RESULT_FIRST) {
            preg_match("/(\w)(.*)/ui", $text, $parts, null, 0);
            return mb_convert_case($parts[1], MB_CASE_TITLE, 'UTF-8') . $parts[2];
        } elseif ($this->result == self::RESULT_UPPER) {
            return mb_convert_case($text, MB_CASE_UPPER, 'UTF-8');
        }
        return $text;
    }
    
    /**
     * Склоняем словоформу
     * @author runcore
     */
    private function morph($n, $f1, $f2, $f5) {
    	$n = abs(intval($n)) % 100;
    	if ($n > 10 && $n < 20) return $f5;
    	$n = $n % 10;
    	if ($n > 1 && $n < 5) return $f2;
    	if ($n == 1) return $f1;
    	return $f5;
    }
    
}

/**
 * Convert amount to words
 * Procedural style
 * @param float $amount Amount
 * @param string $lang Language code (uk, ru, en)
 */
function amount_to_text($amount, $lang = null) {
    return (string)new AmountToText($amount, $lang);
}