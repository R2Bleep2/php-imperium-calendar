<?php

/** Test of the include file on the command line. */

namespace ImperiumCalendar;

include "imperium-calendar-0.3.0.php";

// Create a check number instance from an index that is too high, resulting in correction.

$index = 20;
$checkNumberInstance = CheckNumber::fromIndex($index);
var_dump("Check number instance:", (string)$checkNumberInstance);

// Convert the check number to HTML.

$htmlDocument = \DOM\HTMLDocument::createEmpty();
$htmlCheckNumberInstance = $checkNumberInstance->toHTMLElement($htmlDocument);
$htmlDocument->append($htmlCheckNumberInstance);
var_dump("Check number instance in HTML:", $htmlDocument->saveHTML());

// Get the description of the check number.

$checkNumberInstance->number = 5;
var_dump("Check number description:", $checkNumberInstance->description);

// Create a year fraction from a value that is too high, resulting in correction.

$yearFraction = YearFraction::fromValue(1001);
var_dump("Year fraction from value that is too high:", (string)$yearFraction);

// Create a year fraction from code. The code should be `000` here as a test of reading that code as 1,000.

$yearFraction = YearFraction::fromCode("000");
var_dump("Year fraction from code:", (string)$yearFraction);

$htmlYearFraction = $yearFraction->toHTMLElement($htmlDocument);
$htmlDocument->replaceChild($htmlYearFraction, $htmlCheckNumberInstance);
var_dump("Year fraction in HTML:", $htmlDocument->saveHTML());

// Convert the year fraction to seconds.
var_dump("Year fraction in seconds:", $yearFraction->duration);

// Create a year and convert it to seconds.

$year = Year::fromValue(2);
var_dump("Year in seconds:", $year->duration);

// Create a millenium.

$millenium = Millenium::fromValue(25);
var_dump("Millenium in seconds:", $millenium->duration);

// Create an Imperial Date just a millenium.

$imperialDate = ImperialDate::fromCode("M35");
var_dump("Code of Imperial Date with just a millenium:", $imperialDate->code);

// Create an Imperial Date from code.

$imperialDate = ImperialDate::fromCode("3.996.636.M41");
var_dump("Imperial Date from code:", $imperialDate);

// Set and get the code of an Imperial Date.

$imperialDate->code = "8.234.567.M12";
var_dump("Code of Imperial Date:", $imperialDate->code);

// Try creating dates from a range of code forms with differing numbers of parts.

$dateCodeForms = ["1.234.456.M41", "123.M41", "M41", "5.123.m31", "0.1.234.456.M35", "M42", ""];

var_dump("# Dates parsed from different code forms");

foreach ($dateCodeForms as $codeForm) {
    $date = ImperialDate::fromCode($codeForm);
    var_dump($codeForm . ": " . $date->code);
}

// Create a date from elements.

$millenium = Millenium::fromCode("M41");
$year = Year::fromCode("456");
$yearFraction = YearFraction::fromCode("123");
$checkNumber = CheckNumber::fromCode("1");

$date = ImperialDate::fromElements($millenium, $year, $yearFraction, $checkNumber);
var_dump("Date from properties:", (string)$date);

// Get the array of codifiable elements in a date that contains only a check number, year fraction and millenium. It is expected that the year fraction is excluded to avoid confusion with a year.

$date = ImperialDate::fromElements($millenium, yearFraction: $yearFraction, checkNumber: $checkNumber);
var_dump("Codifiable elements:", $date->codifiable);
var_dump("Code form of date with only year fraction and no year:", (string)$date);

// Convert a date to HTML.

$date = ImperialDate::fromCode("2.345.678.M37");
$htmlDate = $date->toHTMLElement($htmlDocument);
$htmlDocument->replaceChild($htmlDate, $htmlYearFraction);
var_dump("Date in HTML:", $htmlDocument->saveHTML());

/// Save the date to a HTML file.

$htmlDocument->saveHTMLFile("imperial-date.html");

// Get the total duration of the date.

var_dump("Total date duration:", $date->duration);

// Create a date from a duration in seconds.

$duration = 1_234_567_891_234;
$date = ImperialDate::fromDuration($duration);
var_dump("Date from duration:", (string)$date);

/// Section for tests of conversion to and PHP `DateTime` objects.

// Convert a date of hours within a year to a year fraction. This conversion is detailed in the source Fandom article in the section "Year Fraction". Take the same hour count given (18th July) to test equivalence.

$yearFractionConverter = YearFractionGregorianConverter::fromHours(4816);
$yearFraction = $yearFractionConverter->yearFraction;
var_dump("Year fraction converted from hour count according to Fandom method:", (string)$yearFraction);

// Convert a Gregorian date to an imperial date.

$gregorianDate = new \DateTime("1970-1-1");
$imperialDate = ImperialGregorianConverter::fromGregorianDate($gregorianDate)->imperialDate;
var_dump("Gregorian date converted to imperial date:", (string)$imperialDate);

// Convert an imperial date to a Gregorian date.

$imperialDate = ImperialDate::fromCode("3.996.636.M41");
$converter = ImperialGregorianConverter::fromImperialDate($imperialDate);
$gregorianDate = $converter->gregorianDate;
var_dump("Imperial Date converted to Gregorian:", $gregorianDate->format(DATE_ATOM));

// Convert between imperial and Gregorian dates using the procedural-style functions.

$imperialDate = ImperialDate::fromCode("9.001.001.M41");
$gregorianDate = imperialToGregorian($imperialDate);
var_dump("Imperial date converted to Gregorian date using `imperialToGregorian`:", $gregorianDate->format(DATE_ATOM));

$gregorianDate = new \DateTime("2025-9-20 12:09 PM");
$imperialDate = gregorianToImperial($gregorianDate, false);
var_dump("Gregorian date converted to imperial date using `gregorianToImperial`:", (string)$imperialDate);

// Convert a Gregorian date to an Imperial Date with and without the approximation check number.

$gregorianDate = new \DateTime("1970-1-1");

/// Gregorian date converted to Imperial Date and appended with approximation check number.

$imperialDate = gregorianToImperial($gregorianDate);
var_dump("Imperial Date from Gregorian date with approximation check number:", $imperialDate->code);

/// Check number not appended.

$imperialDate = gregorianToImperial($gregorianDate, false);
var_dump("And without check number:", $imperialDate->code);

// Convert an imperial date with only a millenium element.

$imperialDate = ImperialDate::fromCode("M33");
$gregorianDate = imperialToGregorian($imperialDate);
var_dump("Imperial date with only millenium element converted to Gregorian:", $gregorianDate->format(DATE_ATOM));

// Generate imperial dates for all the years of a millenium and convert them to gregorian dates. The output will be sent to the file "converted-dates.txt".

$convertedDatesFilename = "converted-dates.txt";
$convertedDates = "";
$millenium = 35;

for ($year = BaseYearElement::MIN; $year <= BaseYearElement::MAX; $year++) {
    
    $imperialDate = ImperialDate::fromNumbers($millenium, $year);
    $gregorianDate = imperialToGregorian($imperialDate);
    
    $convertedDates .= $imperialDate . ": " . $gregorianDate->format(DATE_ATOM) . "\n";
    
}

file_put_contents($convertedDatesFilename, $convertedDates);