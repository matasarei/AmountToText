<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/AmountToText.php';

final class AmountToTextTest extends TestCase 
{
    private $convertCases = [
        [
            'value' => 1,
            'mode' => AmountToText::RESULT_ITEMS,
            'country' => 'us',
            'result' => 'one'
        ],
        [
            'value' => 1,
            'mode' => AmountToText::RESULT_ITEMS,
            'country' => 'ru',
            'result' => 'один'
        ],
        [
            'value' => 12,
            'mode' => AmountToText::RESULT_ITEMS,
            'country' => 'us',
            'result' => 'twelve'
        ],
        [
            'value' => 12,
            'mode' => AmountToText::RESULT_ITEMS,
            'country' => 'ru',
            'result' => 'двенадцать'
        ],
        [
            'value' => 123,
            'mode' => AmountToText::RESULT_ITEMS,
            'country' => 'us',
            'result' => 'one hundred and twenty-three'
        ],
        [
            'value' => 1000,
            'mode' => AmountToText::RESULT_NORMAL,
            'country' => 'us',
            'result' => 'one thousand and 00/100 USD'
        ],
        [
            'value' => 56000,
            'mode' => AmountToText::RESULT_NORMAL,
            'country' => 'ru',
            'result' => 'пятьдесят шесть тысяч рублей 00 копеек'
        ],
        [
            'value' => 27700,
            'mode' => AmountToText::RESULT_NORMAL,
            'country' => 'ua',
            'result' => 'двадцять сім тисяч сімсот гривень 00 копійок'
        ]
    ];
    
    /**
     * Test convert results
     */
    public function testConvert()
    {
        foreach ($this->convertCases as $case) {
            $converter = new AmountToText($case['value'], $case['country']);
            $converter->setResultMode($case['mode']);
            $this->assertEquals($case['result'], $converter->convert());
        }
    }
    
    /**
     * Test accessors and modes
     */
    public function testAccessorsAndModes()
    {
        $converter = new AmountToText();
        $this->assertEquals('zero and 00/100 USD', $converter->convert());
        
        $converter->setAmount(1);
        $this->assertEquals('one and 00/100 USD', $converter->convert());
        
        $converter->setCountry('ru');
        $this->assertEquals('один рубль 00 копеек', $converter->convert());
        
        $converter->setResultMode(AmountToText::RESULT_FIRST);
        $this->assertEquals('Один рубль 00 копеек', $converter->convert());
        
        $converter->setResultMode(AmountToText::RESULT_UPPER);
        $this->assertEquals('ОДИН РУБЛЬ 00 КОПЕЕК', $converter->convert());
        
        $converter->setResultMode(AmountToText::RESULT_DECIMAL);
        $this->assertEquals('00 копеек', $converter->convert());
        
        $converter->setResultMode(AmountToText::RESULT_ITEMS);
        $this->assertEquals('один', $converter->convert());
    }
}
