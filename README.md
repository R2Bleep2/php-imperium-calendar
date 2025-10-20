# ImperiumCalendar

A PHP module for an implementation of the Imperial Dating System from Warhammer 40,000. This is the dating system used by the Imperium of Man which commonly appears in Warhammer 40,000 media.

An example of an Imperial Date would be "999.M41", meaning the 999th year of the 41st Millenium, which is approximately the "present" of the setting.

The source for this module is [the "Imperial Dating System" article from the Warhammer 40,000 Fandom wiki, retrieved Thursday, October 5th, 2023](https://warhammer40k.fandom.com/wiki/Imperial_Dating_System).

There are features for conversion to HTML and a subsection of the module for conversion to and from Gregorian dates (the standard PHP `DateTime` definitions).

Version: 0.3.0 (2025-10-12)

PHP Version: 8.4.11

## Installation

Copy the .php files from the "modules" directory in this GitHub repository into [your PHP include directory](https://www.php.net/manual/en/ini.core.php#ini.include-path). These files are the ImperiumCalendar module itself ("imperium-calendar-0.3.0.php") and dependencies for it.

## Usage

Load the ImperiumCalendar module from the include directory into your PHP script.

```php
<?php

namespace ImperiumCalendar;

include "imperium-calendar-0.3.0.php";
```

To create an Imperial Date, you can construct it from a code form, which is the form these dates are usually printed in.

```php
$imperialDateCode = "3.996.636.M41";
$imperialDate = ImperialDate::fromCode($imperialDateCode);
var_dump("Imperial Date from code:", $imperialDate);
```

Expected output (on a command line):
```bash
string(24) "Imperial Date from code:"
object(ImperiumCalendar\ImperialDate)#10 (4) {
  ["millenium"]=>
  object(ImperiumCalendar\Millenium)#11 (1) {
    ["count"]=>
    int(41)
  }
  ["year"]=>
  object(ImperiumCalendar\Year)#14 (1) {
    ["count"]=>
    int(636)
  }
  ["yearFraction"]=>
  object(ImperiumCalendar\YearFraction)#13 (1) {
    ["count"]=>
    int(996)
  }
  ["checkNumber"]=>
  object(ImperiumCalendar\CheckNumber)#12 (1) {
    ["index"]=>
    int(3)
  }
}
```

An Imperial Date is internally built from a series of elements - the millenium, year, year fraction and check number elements. The millenium element is mandatory; the others are optional. Imperial Dates appearing in the setting often omit elements, usually the year fraction and check number.

```php
$millenium = Millenium::fromCode("M35");
$imperialDate = ImperialDate::fromElements($millenium);
$imperialDateCode = $imperialDate->code;
var_dump("Code of Imperial Date from just a millenium": $imperialDateCode);
```

Expected output:

```bash
string(44) "Code of Imperial Date with just a millenium:"
string(3) "M35"
```

The code form of an Imperial Date can be set and gotten by the `code` property. When set, the code is parsed into elements. When gotten, the code is reconstructed from the elements.

```php
// Setting code.
$imperialDate->code = "8.234.567.M12";

// Getting that code back (reconstructed).
var_dump("Code of Imperial Date:", $imperialDate->code);
```

Expected output:

```php
string(22) "Code of Imperial Date:"
string(13) "8.234.567.M12"
```

### Gregorian conversion

An Imperial Date can be converted to a Gregorian date considered equivalent using the `ImperialGregorianConverter` class. The resulting Gregorian date will be a standard PHP `DateTime` instance.

```php
$imperialDate = ImperialDate::fromCode("3.996.636.M41");
$converter = ImperialGregorianConverter::fromImperialDate($imperialDate);
$gregorianDate = $converter->gregorianDate;
var_dump("Imperial Date converted to Gregorian:", $gregorianDate);
```

Expected output:

```bash
string(37) "Imperial Date converted to Gregorian:"
string(26) "40636-12-29T17:00:00+00:00"
```

Alternatively, one can just use the procedural-style conversion function `imperialToGregorian` provided in this ImperiumCalendar module for convenience.

```php
$imperialDate = ImperialDate::fromCode("9.001.001.M41");
$gregorianDate = imperialToGregorian($imperialDate);
var_dump("Imperial date converted to Gregorian date using `imperialToGregorian`:", $gregorianDate->format(DATE_ATOM));
```

Expected output:

```bash
string(70) "Imperial date converted to Gregorian date using `imperialToGregorian`:"
string(26) "40001-01-01T07:00:00+00:00"
```

Similarly, the `ImperialGregorianConverter` class can convert the other way, from Gregorian to Imperial. Or, the `gregorianToImperial` function can be used.

```php

// The UNIX start time is used here as an example.
$gregorianDate = new \DateTime("1970-1-1");

$imperialDate = ImperialGregorianConverter::fromGregorianDate($gregorianDate)->imperialDate;

// For convenience, the string conversion of an `ImperialDate` is its code, and the same goes for the elements therein.
var_dump("Gregorian date converted to imperial date:", (string)$imperialDate);
```

Expected output:

```bash
string(42) "Gregorian date converted to imperial date:"
string(12) "9.001.970.M2"
```

#### Check number conversion

When converting an Imperial Date to a Gregorian date, the default behaviour is to append the check number 9 to the resulting date, because in the Imperial Calendar dates from foreign calendars are considered "approximations". See the source Fandom article for details.

To disable this behaviour, set the `makeApproximation` boolean property of the `ImperialGregorianConverter` class or `makeApproximation` argument of the `gregorianToImperial` function to `false`.

```php
$gregorianDate = new \DateTime("1970-1-1");

// Gregorian date converted to Imperial Date and appended with approximation check number.

$imperialDate = gregorianToImperial($gregorianDate);
var_dump("Imperial Date from Gregorian date with approximation check number:", $imperialDate->code);

// Check number not appended.

$imperialDate = gregorianToImperial($gregorianDate, false);
var_dump("And without check number:", $imperialDate->code);
```

Expected output:

```bash
string(66) "Imperial Date from Gregorian date with approximation check number:"
string(12) "9.001.970.M2"
string(25) "And without check number:"
string(10) "001.970.M2"
```

### HTML conversion

This module provides a method to convert an Imperial Date to a HTML element with sub-elements corresponding to the elements of the date. This uses PHP's `DOM` interface.

```php
// Convert an imperial date to a `DOM` HTML element and insert it in the specified `DOM` HTML document.

$htmlDocument = \DOM\HTMLDocument::createEmpty();
$date = ImperialDate::fromCode("2.345.678.M37");
$htmlDate = $date->toHTMLElement($htmlDocument);
$htmlDocument->append($htmlDate);
var_dump("Date in HTML:", $htmlDocument->saveHTML());
```

Expected output:

```bash
string(13) "Date in HTML:"
string(241) "<span class="date wh40k wh40k-date wh40k-imperial-date imperial-date warhammer warhammer-date"><span class="check-number">2</span>.<span class="year-fraction">345</span>.<span class="year">678</span>.<span class="millenium">M37</span></span>"
```

### Testing script

There is a general test script "console.php" in the "test" directory in this GitHub repository. Run this script using PHP on the command line to demo the module.

```bash
php console.php
```

## Module dependencies

- "code-0.0.0.php" for representing the elements of a date as code strings.
- "to-dom-0.0.0.php" for conversion of a date to HTML.
- "disabled-magic-constructor-0.0.0.php" to enforce the use of custom constructors, of which there will be a variety.

## Changelog
Since version 0.2.0.

The previous version has not been released, but the current version is mostly revision and cleaning-up.

## License

The code itself is released to the public domain, but the Imperial Calendar concept is copyrighted to Games Workshop.
