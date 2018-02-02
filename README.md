# AmountToText
Amount to text converter

## Usage
```php
//new converter
$toText = new AmountToText(125.00);
$toText->convert(); // one hundred and twenty-five and 00/100 USD

//change language
$toText->setLanguage('uk'); //uk, ru, en (default)
$toText->convert(); // сто двадцять п'ять гривень 00 копійок

//change amount
$text->setAmount(777); //сімсот сімдесят сім гривень 00 копійок

//result mode
$toText->setResultMode(AmountToText::RESULT_FIRST); //RESULT_ITEMS, RESULT_UPPER, RESULT_DECIMAL, RESULT_NORMAL (default)
$toText->convert(); // One hundred and twenty-five and 00/100 USD

$toText->setResultMode(AmountToText::RESULT_DECIMAL);
$toText->convert(); // 00/100 USD

$toText->setResultMode(AmountToText::RESULT_ITEMS);
$toText->convert(); // one hundred and twenty-five

//single line
(string)new AmountToText(125.00, 'ru'); //сто двадцать пять рублей 00 копеек

//procedural style
amount_to_text(125.00, 'uk'); // сто двадцять п'ять гривень 00 копійок
```
