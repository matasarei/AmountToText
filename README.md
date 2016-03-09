# AmountToText
Amount to text converter

## Usage
```php
//new converter
$toText = new AmountToText(125.00); 
$toText->convert(); // one hundred and twenty-five 00/100 US dollars

//change language
$toText->setLanguage('uk'); //uk, ru, en (default)
$toText->convert(); // сто двадцять п'ять гривень 00 копійок

//change amount
$text->setAmount(777); //семьсот сімдесят сім гривень 00 копійок

//result mode
$toText->setResultMode(AmountToText::RESULT_FIRST); //RESULT_UPPER, RESULT_DECIMAL, RESULT_NORMAL (default)
$toText->convert(); // Сто двадцять п'ять гривень 00 копійок

$toText->setResultMode(AmountToText::RESULT_DECIMAL);
$toText->convert(); // 00 копійок

//single line
(string)new AmountToText(125.00, 'ru'); //сто двадцать пять рублей 00 копеек

//procedural style
amount_to_text(125.00, 'uk'); // сто двадцять п'ять гривень 00 копійок
```
