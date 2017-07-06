Moscow Exchange ISS Client
=======================

[![Build Status](https://travis-ci.org/panychek/moex.svg?branch=master)](https://travis-ci.org/panychek/moex)

A PHP library that provides easy access to [Moscow Exchange](https://www.moex.com/) data via its [Informational & Statistical Server](https://www.moex.com/a2920).

## Installation

Download via [Composer](https://getcomposer.org):

```sh
composer require panychek/moex
```

Include the autoloader:

```php
require_once 'vendor/autoload.php';
```

## Usage

### Basic usage

The basic usage pattern is to instantiate a `Security` object for the security you want to interact with. You can pass any string you want (in Russian or English) - the library will perform a search and pick the best match.

```php
use Panychek\MoEx\Security;
use Panychek\MoEx\Exception\ExceptionInterface as MoexException;

try {
    $security = new Security('Sberbank');
    $last_price = $security->getLastPrice();
    
} catch (MoexException $e) {
    echo $e->getMessage();
}
```

You can also access the security by its code (prefix it with a hash symbol (#)). This will be slightly faster, since you avoid an extra API call to find it.

```php
$security = new Security('#SBER');
```

The default language is **Russian**. You can switch to English like this:

```php
$security->setLanguage('en');
```

### Market data

#### Shares, currency pairs and futures

Available methods:

* `getLastPrice`
* `getOpeningPrice`
* `getClosingPrice`
* `getDailyHigh`
* `getDailyLow`
* `getLastUpdate`
* `getVolume($currency = 'RUB')`
* `getChange($range = 'day', $measure = 'currency')`

###### Example usage

```php
$daily_change = $security->getChange('day');
$daily_percentage_change = $security->getChange('day', '%');

$volume_rub = $security->getVolume('RUB');
$volume_usd = $security->getVolume('USD');
```

#### Bonds

Bonds also support the following methods:

* `getYield`
* `getDuration`
* `getFaceValue`
* `getCouponValue`
* `getCouponRate`
* `getCouponDate`
* `getMaturityDate`

#### Indices

Available methods:

* `getValue`
* `getOpeningValue`
* `getPreviousClose`
* `getDailyHigh`
* `getDailyLow`
* `getLastUpdate`
* `getVolume($currency = 'RUB')`
* `getChange($range = 'day', $measure = 'bp')`
* `getCapitalization($currency = 'RUB')`

###### Example usage

```php
$rtsi = new Security('RTS Index');

$year_to_date_return = $rtsi->getChange('YTD', '%');
$month_to_date_return = $rtsi->getChange('MTD', '%');

$capitalization_rub = $rtsi->getCapitalization('RUB');
$capitalization_usd = $rtsi->getCapitalization('USD');
```

### Profile

```php
$security = new Security('Gazprom');
$security->setLanguage('en');

$code = $security->getId(); // "GAZP"
$name = $security->getName(); // "Gazprom"
$short_name = $security->getShortName(); // "Gazprom"
$isin_code = $security->getIsin(); // "RU0007661625"

$market_title = $security->getMarket()->getTitle(); // "Equities Market"
$engine_title = $security->getEngine()->getTitle(); // "Securities Market"
$board_title = $security->getBoard()->getTitle(); // "T+: Stocks, DRs"
$capitalization = $security->getEngine()->getCapitalization(); // 33015281259414 RUB

$indices = $security->getIndices();
foreach ($indices as $index) {
    $code = $index->getId(); // "MICEX10INDEX", "MICEXBMI", "MICEXINDEXCF", etc.
    $short_name = $index->getShortName(); // "MICEX10 Index", "Moscow Exchange Broad Market Index", "MICEX Index", etc.
}
```

### Historical quotes

For a specific date range:

```php
$security = new Security('MICEX Index');
$data = $security->getHistoricalQuotes('2014-01-01', '2014-12-31');
```

Starting from a particular day:

```php
$data = $security->getHistoricalQuotes('2017-01-01');
```

### Handling errors
A `Panychek\MoEx\Exception\DataException` exception is thrown in the event of any data related error.
The following codes indicate the reason for the failure:

 * `FAILED_REQUEST` - all network errors
 * `INVALID_RESPONSE` - the response is not a valid JSON string or its format is not supported
 * `EMPTY_RESULT` - the response contains no actual data

All exceptions thrown by the library implement the `Panychek\MoEx\Exception\ExceptionInterface` interface.