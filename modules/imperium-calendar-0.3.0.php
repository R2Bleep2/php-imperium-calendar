<?php

/**

# imperium-calendar-0.3.0.php

An implementation of the Imperial Dating System from Warhammer 40,000. This is the dating system used by the Imperium of Man and which appears commonly in Warhammer 40,000 media.

An example of an imperial date would be "999.M41", meaning the 999th year of the 41st Millenium, a date often mentioned in the setting as being the "present" or thereabouts.

The source for this module is the "Imperial Dating System" article from the Warhammer 40,000 Fandom wiki, retrieved Thursday, October 5, 2023 (https://warhammer40k.fandom.com/wiki/Imperial Dating System).

There are features for conversion to HTML and there is a section for conversion of imperial dates to and from Gregorian dates.

Version: 0.3.0 (2025-10-12)

PHP version: 8.4.11

## Module dependencies

- "code-0.0.0.php" for representing the elements of a date as code strings.
- "to-dom-0.0.0.php" for conversion of a date to HTML.
- "disabled-magic-constructor-0.0.0.php" to enforce the use of custom constructors, of which there will be a variety.

## Changelog
Since version 0.2.0

- The previous version works fine but a bit of clean-up might be possible. This version revises the documentation and the naming of definitions. Custom constructors are no longer contracted for in interfaces, in keeping with the advice on the PHP manual to keep classes flexible this way.

*/

namespace ImperiumCalendar;

require_once "code-0.0.0.php";
require_once "to-dom-0.0.0.php";
require_once "disabled-magic-constructor-0.0.0.php";

/** An interface for a duration in seconds. This interface contracts for the conversion of an imperial date or date element into seconds from the start time. */
interface HasDuration {
    public int $duration { set; get; }
}

/** A custom constructor method from the duration. */
trait CustomDurationConstructor {
    static function fromDuration(int $duration): static {
        $new = new static;
        $new->duration = $duration;
        return $new;
    }
}

/** An interface for the count of an imperial date element. This is the number specified in the element, starting from 1. For example, a year element counts from 1 to 1,000 within a millenium; when printed, this will be 001 to 000. */
interface HasCount {
    public int $count { set; get; }
}

trait CustomCountConstructor {
    static function fromCount(int $count): static {
        $new = new static;
        $new->count = $count;
        return $new;
    }
}

/** An interface for the numerical value of an imperial date element. This is the floored form of the element, in contrast to the count which is "ceilinged", e.g. the 41st millenium runs from years 40,001 to 41,000. The floored value represents the actual numerical value of the duration. */
interface HasValue {
    public int $value { set; get; }
}

/** A custom constructor method from the floored value. */
trait CustomValueConstructor {
    static function fromValue(int $value): static {
        $new = new static;
        $new->value = $value;
        return $new;
    }
}

/** A value property which functions as specified in `HasValue`. The value is an associated `count` floored. */
trait ValueProperty {
    
    public int $value {
        
        set {
            $this->count = $value + 1;
        }
        
        get => $this->count -= 1;
        
    }
    
}

/** An interface for the number that appears in an imperial date element generally. In the millenium, year and year fraction elements, this is the count, and in the check number element, this is the index. */
interface HasNumber {
    public int $number { set; get; }
}

trait CustomNumberConstructor {
    static function fromNumber(int $number): static {
        $new = new static;
        $new->number = $number;
        return $new;
    }
}

/** An interface for a PHP `DateTime` considered equivalent to an imperial date. This contracts for some kind of conversion. */
interface HasGregorianDate {
    public \DateTimeInterface $gregorianDate { set; get; }
}

trait CustomGregorianConstructor {
    static function fromGregorianDate(\DateTime $gregorianDate): static {
        $new = new static;
        $new->gregorianDate = $gregorianDate;
        return $new;
    }
}

/** An enumeration containing an array of check numbers of calendar dates. These are measures of the certainty of a date due to vagaries associated with the Warp or with errors in timekeeping in the vast Imperium.

In the array, each key is the calendar class (not PHP class) of the check number and the associated value is a brief description. See the Fandom article for details on each number. */
enum DefinedCheckNumbers {
    
    const array VALUE = [
        "Earth Standard Date (Holy Terra)",
        "Earth Standard Date (Sol)",
        "Direct",
        "Indirect",
        "Corroborated",
        "Sub-Corroborated",
        "Non-Referenced, 1 year",
        "Non-Referenced, 10 years",
        "Non-Referenced, 11+ years",
        "Approximation",
    ];
    
    const int MIN_INDEX = 0;
    
    static function getMaxIndex(): int {
        return count(self::VALUE) - 1;
    }
    
}

/** A method to clamp a given integer to a certain range. If below the minimum, it is set to the minimum. If above the maximum, it is set to the maximum.
    
This method is useful for elements that have defined minimum and/or maximum counts, namely the millenium, year and year fraction elements.

Clamping can be omitted for either the minimum or maximum by setting them to null. */
trait ClampingMethod {
    
    protected function clamp(int $value, ?int $min = null, ?int $max = null): int {
        
        if (isset($min) and $value < $min) {
            $value = $min;
        } elseif (isset($max) and $value > $max) {
            $value = $max;
        }
        
        return $value;
        
    }
    
}

/** A base class for any element in this module, such as a millenium part of an Imperial Date. All elements have code forms (the printed form) and can be converted to HTML for presentation. */
abstract class Element implements \Code\HasCode, \Code\ConstructableFromCode, \ToDOM\ConvertibleToHTMLElement {
    
    /** The string conversion of an element is its code form. */
    use \Code\CodeStringConverter;
    
    /** By default, in HTML, a date element is a `span` containing the code form of the element. The HTML class name of that `span` is a static `domClass` property defined per subclass. */
    function toHTMLElement(\DOM\HTMLDocument $htmlDocument): \DOM\HTMLElement {
        $htmlConversion = $htmlDocument->createElement("span");
        $htmlConversion->className = $this::$domClass;
        $htmlConversion->textContent = $this->code;
        return $htmlConversion;
    }
    
    /** The default magic constructor is disabled to enforce the use of custom constructors. */
    use \DisabledMagicConstructor;
    
    use \Code\CustomCodeConstructor;
    
}

/** A base class for an element of an imperial calendar date, such as a check number instance or year fraction. All such elements have a `number` of some kind as explained in `HasNumber`. */
abstract class DateElement extends Element implements HasNumber {
    use CustomNumberConstructor;
}

/** A class for an instance of a check number in a date. This is a container for the index of a check number in the `CHECK_NUMBERS` constant in the `DefinedCheckNumbers` enumeration.
 
The given index must be extant in the array. This means that the possible range is zero up to nine.

If a given index is outside of the valid range, it is clamped to the range. */
class CheckNumber extends DateElement {
    
    static string $domClass = "check-number";
    
    use ClampingMethod;
    
    public int $index {
        set => $this->clamp($value, DefinedCheckNumbers::MIN_INDEX, DefinedCheckNumbers::getMaxIndex());
    }
    
    /** A shortcut virtual property for the description of the check number of the specified index. */
    public string $description {
        get => DefinedCheckNumbers::VALUE[$this->index];
    }
    
    /** The number of a check number is its index. */
    public int $number {
        
        set {
            $this->index = $value;
        }
        
        get => $this->index;
        
    }
    
    /** The code form of a check number instance is just the string conversion of its index. */
    public string $code {
        
        set {
            $this->index = (int)$value;
        }
        
        get => (string)$this->index;
        
    }
    
    static function fromIndex(int $index): static {
        $new = new static;
        $new->index = $index;
        return $new;
    }
    
}

/** A base class for an element of a date representing a duration, namely the year fraction, year and millenium elements.
 
Each subclass defines a static property `unit` which is the size of a single unit of the duration in seconds. */
abstract class DurationElement extends DateElement implements HasDuration, HasCount, HasValue {
    
    public int $count;
    
    use ValueProperty;
    
    /** The number of a duration element is its count. */
    public int $number {
        
        set {
            $this->count = $value;
        }
        
        get => $this->count;
        
    }
    
    /** The duration in seconds will be the unit defined in the static property `unit` multiplied by the value. */
    public int $duration {
        
        set {
            $this->value = $value / $this::$unit;
        }
        
        get => $this->value * $this::$unit;
        
    }
    
    // A duration element can be constructed either from its duration, its count, its value or its number (equivalent to its count).
    
    use CustomDurationConstructor;
    use CustomCountConstructor;
    use CustomValueConstructor;
    
}

// Classes for year fraction and year elements, inheriting from the `BaseYearElement` base class.

/** A base class for the year fraction and year parts of a date. In code form, these are both strings of three digits, e.g. `123`. */
abstract class BaseYearElement extends DurationElement {
    
    /** The length of a Gregorian year in days including leap years. */
    const float GREGORIAN_YEAR_DAYS = 365.2425;
    
    /** The length of a Gregorian year in seconds including leap years. This is relevant to calculating the duration of the element. */
    const float GREGORIAN_YEAR_SECONDS = self::GREGORIAN_YEAR_DAYS * 24 * 60 * 60;
    
    /** The integer value of the base year element. This ranges from 1 to 1,000 (in code form, 1 is `001`, and 1,000 is `000`). */
    
    const int MIN = 1;
    const int MAX = 1_000;
    
    use ClampingMethod;
    
    public int $count {
        set => $this->clamp($value, $this::MIN, $this::MAX);
    }
    
    /** The code form of the base year element. This is a string of three digits where 1 is represented as `001` and 1,000 as `000`.
    
    When set, if a code form has less than three digits, zeroes are prepended. If there are more than three digits, excess digits are removed from the start. */
    
    const int CODE_LEN = 3;
    const string CODE_PAD_CHAR = "0";
    const string THOUSAND_CODE = "000";
    
    public string $code {
        
        set(string $code) {
            
            // Remove excess digits from the start.
            
            $codeLen = $this::CODE_LEN;
            $code = substr($code, -$codeLen);
            
            // Alternatively, pad the start with zeroes.
            
            $code = str_pad($code, $codeLen, $this::CODE_PAD_CHAR, STR_PAD_LEFT);
            
            // Interpret "000" as 1,000, else convert the code to an integer as normal.
            
            if ($code == $this::THOUSAND_CODE) {
                $count = $this::MAX;
            } else {
                $count = (int)$code;
            }
            
            $this->count = $count;
            
        }
        
        /** When gotten, the code form will be the string conversion of the count padded with zeroes at the start to make three digits. A value of 1,000 is interpreted as `000`. */
        get {
            
            $count = $this->count;
            
            // Interpret 1,000 as `000`, else pad the value up to three digits.
            
            if ($count == $this::MAX) {
                $code = $this::THOUSAND_CODE;
            } else {
                $code = str_pad($count, $this::CODE_LEN, $this::CODE_PAD_CHAR, STR_PAD_LEFT);
            }
            
            return $code;
            
        }
        
    }
    
}

/** The fraction of a year within an imperial date. A year is divided up into a thousand even fractions, numbering from 001 to 000 (000 is code for one thousand). */
class YearFraction extends BaseYearElement {
    
    /** A year fraction is a year divided into 1,000 parts. */
    static float $unit = BaseYearElement::GREGORIAN_YEAR_SECONDS / BaseYearElement::MAX;
    
    static string $domClass = "year-fraction";
    
}

class Year extends BaseYearElement {
    
    static float $unit = BaseYearElement::GREGORIAN_YEAR_SECONDS;
    
    static string $domClass = "year";
    
}

/** A class for the millenium part of a date. The count is the current millenium, e.g. 41. The code form is the integer prefixed with "M", hence `M41`. */
class Millenium extends DurationElement {
    
    // Each millenium unit is 1,000 years.

    const int YEARS = 1000;
    static float $unit = self::YEARS * BaseYearElement::GREGORIAN_YEAR_SECONDS;
    
    static string $domClass = "millenium";
    
    const string PREFIX = "M";
    
    const int MIN = 1;
    
    // The default count is 41, the usual millenium of the Warhammer 40,000 setting.
    
    const int DEFAULT_COUNT = 41;
    
    use ClampingMethod;
    
    public int $count = self::DEFAULT_COUNT {
        set => $this->clamp($value, $this::MIN);
    }
    
    /** The code form of a millenium part is the prefix "M" followed by the count, e.g. `M41`.
    
    When set, all characters after the first (whether or not that character is "M") will be read as the count. If there is no such count in the given code, the value will be taken to be 41. If the code is entirely blank, it is also taken to be 41. */
    public string $code {
        
        set (string $code) {
            
            // Take 41 if the given code is blank.
            
            $defaultCount = $this::DEFAULT_COUNT;
            
            if ($code === "") {
                
                trigger_error("The given millenium code is blank so the count is being taken to be $defaultCount.", E_USER_WARNING);
                $count = $defaultCount;
                
            } else {
            
                // Notify the user if the code does not start with the defined prefix.
                
                $definedPrefix = $this::PREFIX;
                
                $givenPrefix = substr($code, 0, 1);
                
                if ($givenPrefix != $definedPrefix) {
                    trigger_error("The given millenium code \"$code\" starts with the prefix \"$givenPrefix\" but the defined prefix is \"$definedPrefix\"");
                }
                
                $codeCount = substr($code, 1);
                
                // Take 41 if the count in the code is missing.
                
                if ($codeCount === "") {
                    trigger_error("There is no count part in the given millenium code so the count is being taken to be $defaultCount.");
                    $count = $defaultCount;
                } else {
                    $count = (int)$codeCount;
                }
                
            }
            
            $this->count = $count;
            
        }
        
        get => $this::PREFIX . $this->count;
        
    }
    
}

/** A class for a complete date in the imperial calendar. This is a container for a check number, a year fraction, a year and a millenium. The check number, year fraction and year are all optional. */
class ImperialDate extends Element implements HasDuration {
    
    static string $domClass = "date wh40k wh40k-date wh40k-imperial-date imperial-date warhammer warhammer-date";
    
    public Millenium $millenium;
    public ?Year $year = null;
    public ?YearFraction $yearFraction = null;
    public ?CheckNumber $checkNumber = null;
    
    /** A boolean indicating whether or not to include the year fraction in a generated code form. The year fraction is only included if it is itself defined and the year (the separate element specifying the part of a millenium) is also defined, as the two code forms have similar forms and could be confused. */
    public bool $includeYearFractionInCode {
        get => isset($this->year) and isset($this->yearFraction);
    }
    
    /** A getter for the year fraction if it can be included in code as specified in `includeYearFractionInCode`, else null. */
    public ?YearFraction $yearFractionIfCodifiable {
        get => $this->includeYearFractionInCode ? $this->yearFraction : null;
    }
    
    /** A getter for an array of elements considered codifiable, for convenience when converting to code. An element is considered codifiable if its associated property is set. There is a special case for the year fraction, which is only codifiable if both the year fraction and year are defined (see the property `includeYearFractionInCode`).
     
    The array will be given in order of most significance:
    
    1. Millenium.
    2. Year.
    3. Year fraction.
    4. Check number. */
    public array $codifiable {
        
        get {
            
            $codifiable = [$this->millenium];
            
            $year = $this->year;
            
            if (isset($year)) {
                $codifiable[] = $year;
            }
            
            if ($this->includeYearFractionInCode) {
                $codifiable[] = $this->yearFraction;
            }
            
            $checkNumber = $this->checkNumber;
            
            if (isset($checkNumber)) {
                $codifiable[] = $checkNumber;
            }
            
            return $codifiable;
            
        }
        
    }
    
    /** The code form of a complete date is the concatenated code forms of the check number, year fraction, year and millenium, all delimited by a period, e.g. `0.123.456.M41`.
    
    If the year is missing, the year fraction is also omitted, to avoid confusion of a year fraction therein with a year (the two being indistinguishable), e.g. `0.M41`.
    
    If a code form is given with more than four parts (delimited by periods) then excess parts are removed from the start. */
    
    const string DELIMITER = ".";
    
    const int MAX_CODE_PART_COUNT = 4;
    
    public string $code {
        
        /** When set, if there is a single three-digit number therein, it is always interpreted as the year, as explained earlier. */
        set (string $code) {
            
            // Split the given code on the period to get the code forms of each part.
            
            $parts = explode($this::DELIMITER, $code);
            
            // Remove excess parts if there are more than four.
            
            $maxCodePartCount = $this::MAX_CODE_PART_COUNT;
            
            $partCount = count($parts);
            
            if ($partCount > $maxCodePartCount) {
                trigger_error("The number of parts (delimited by the period) in the given date code is $partCount, which is greater than the limit of $maxCodePartCount, so excess parts will be removed from the start.", E_USER_WARNING);
                $parts = array_slice($parts, -$maxCodePartCount);
                $partCount = count($parts);
            }
            
            /* If there is only one part, e.g. "M41", it is the millenium.
            
            If there is more than one part, the last will always be the millenium.
            
            If there are two parts, e.g. "123.M41", the first will be either the check number if one digit or else the year.
            
            If there are three parts, e.g. "5.123.M41", either the first will be the check number and the second the year, or the first the year fraction and the second the year.
            
            If there are four parts, e.g. "6.123.456.M41", the first will be the check number, the second the year fraction and the third the year. */
            
            // The last part is always the millenium.
            
            $millenium = Millenium::fromCode($parts[array_key_last($parts)]);
            $this->millenium = $millenium;
            
            // The other parts are assumed to be absent unless detected later.
            
            $checkNumberCode = null;
            $yearFractionCode = null;
            $yearCode = null;
            
            // If there is more than one part, then if the first part is one digit long, it is the check number.
            
            if ($partCount > 1) {
                
                $firstPart = $parts[0];
                
                if (strlen($firstPart) == 1) {
                    $checkNumberCode = $firstPart;
                }
                
            }
            
            $checkNumberSet = isset($checkNumberCode);
            
            // If there are two parts, then if the first part was not the check number, it is the year.
            
            if ($partCount == 2) {
                
                if (!$checkNumberSet) {
                    $yearCode = $parts[0];
                }
            
            // Else if there are three parts, then the second part will be the year. If the first part was not detected to be the check number, it will be the year fraction.
            } elseif ($partCount == 3) {
                
                $yearCode = $parts[1];
                
                if (!$checkNumberSet) {
                    $yearFractionCode = $firstPart;
                }
            
            // Else if there are four parts, the first part will be the check number, the second part the year fraction and the third part the year.
            
            } elseif ($partCount == 4) {
                
                if (!$checkNumberSet) {
                    $checkNumberCode = $parts[0];
                }
                
                $yearFractionCode = $parts[1];
                $yearCode = $parts[2];
                
            }
            
            // Store all the optional properties or null if not found.
            
            $this->checkNumber = isset($checkNumberCode) ? CheckNumber::fromCode($checkNumberCode) : null;
            $this->yearFraction = isset($yearFractionCode) ? YearFraction::fromCode($yearFractionCode) : null;
            $this->year = isset($yearCode) ? Year::fromCode($yearCode) : null;
            
        }
        
        /** When gotten, the code form will be the code forms of the elements concatenated with a period delimiter, with the millenium first and check number last. */
        get => join($this::DELIMITER, array_reverse($this->codifiable));
        
    }
    
    /** In HTML, a date can be converted to a `span` of class `date imperial-date wh40k-date wk40k-imperial-date`. There is also a method to convert its elements therein as a document fragment, which can be useful if constructing a `time` element with an equivalent Gregorian date.*/
    
    /** A method to generate the equivalent inner HTML elements. */
    function toHTMLFragment(\DOM\HTMLDocument $htmlDocument): \DOM\DocumentFragment {
        
        $domDocumentFragment = $htmlDocument->createDocumentFragment();
        
        // Start with the millenium.
        $domDocumentFragment->prepend($this->millenium->toHTMLElement($htmlDocument));
        
        // For each element thereafter, prepend it to the HTML date using a period delimiter.
        
        $codifiable = $this->codifiable;
        
        // The codifiable element array includes the millenium first, but that is already prepended to the HTML conversion, so omit it from the array.
        array_shift($codifiable);
        
        $delimiter = $this::DELIMITER;
        
        foreach ($codifiable as $element) {
            $domDocumentFragment->prepend($element->toHTMLElement($htmlDocument), $delimiter);
        }
        
        return $domDocumentFragment;
        
    }
    
    function toHTMLElement(\DOM\HTMLDocument $htmlDocument): \DOM\HTMLElement {
        $htmlDate = $htmlDocument->createElement("span");
        $htmlDate->className = $this::$domClass;
        $htmlDate->append($this->toHTMLFragment($htmlDocument));
        return $htmlDate;
    }
    
    /** The duration of a date is the sum of the durations of its elements that also have durations, according to their units.
    
    The duration of a date will be relative to the start of year one in the imperial calendar exactly.
     
    The duration can be set, in which case the elements are derived by sequentially dividing up the given duration into the units of those elements. */
    public int $duration {
        
        set(int $duration) {
            
            // Divide the duration up into millenia. The remainder will be divided up into years. The remainder of those years will be a year fraction.
            
            $milleniaCount = $duration / Millenium::$unit;
            $milleniaCountFloored = floor($milleniaCount);
            $milleniaCountRemainder = $milleniaCount - $milleniaCountFloored;
            
            // Years.
            
            $baseYearElementMax = BaseYearElement::MAX;
            
            $yearCount = $milleniaCountRemainder * $baseYearElementMax;
            
            // Round the year count slightly to avoid a precision error that can result in the year fraction, calculated later, to be erroneously offset by one fraction.
            $yearCount = round($yearCount, 5);
            
            $yearCountFloored = floor($yearCount);
            $yearCountRemainder = $yearCount - $yearCountFloored;
            
            // Year fractions.
            
            $yearFractionCount = $yearCountRemainder * $baseYearElementMax;
            
            $this->millenium = Millenium::fromValue($milleniaCount);
            $this->year = Year::fromValue($yearCount);
            $this->yearFraction = YearFraction::fromValue($yearFractionCount);
            
        }
        
        get {
            
            $duration = $this->millenium->duration;
            
            foreach ([$this->year, $this->yearFraction] as $element) {
                
                if (isset($element)) {
                    $duration += $element->duration;
                }
                
            }
            
            return $duration;
            
        }
        
    }
    
    /** The numbers of the elements can be set directly as a shorthand. If any of these numbers (except for the mandatory millenium) are set to null, their associated elements will be omitted. The check number is set by index. */
    function setNumbers(int $millenium = Millenium::DEFAULT_COUNT, ?int $year = null, ?int $yearFraction = null, ?int $checkNumber = null) {
        $this->millenium = Millenium::fromNumber($millenium);
        $this->year = isset($year) ? Year::fromNumber($year) : null;
        $this->yearFraction = isset($yearFraction) ? YearFraction::fromNumber($yearFraction) : null;
        $this->checkNumber = isset($checkNumber) ? CheckNumber::fromNumber($checkNumber) : null;
    }
    
    /*
    
    A date can be constructed from any of the following:
    
    - Elements.
    - The numbers of the elements.
    - Duration.
    - Code.
    
    */
    
    static function fromElements(Millenium $millenium, ?Year $year = null, ?YearFraction $yearFraction = null, ?CheckNumber $checkNumber = null): static {
        $new = new static;
        $new->millenium = $millenium;
        $new->yearFraction = $yearFraction;
        $new->year = $year;
        $new->checkNumber = $checkNumber;
        return $new;
    }
    
    use CustomDurationConstructor;
    
    static function fromNumbers(int $millenium, ?int $year = null, ?int $yearFraction = null, ?int $checkNumber = null): static {
        $new = new static;
        $new->setNumbers($millenium, $year, $yearFraction, $checkNumber);
        return $new;
    }
    
}

/// Definitions for conversion between imperial and Gregorian dates, where a Gregorian date is a PHP `DateTime`.

/** A base class for converters between imperial elements and Gregorian date elements (years, days, hours, etc.). */
abstract class GregorianConverter {
    use \DisabledMagicConstructor;
}

/** A class for a year fraction conversion calculator.

This uses the Fandom conversion method outlined in this module's source Fandom article under the section "Year Fraction". This involves converting the hour within a year to a year fraction using a constant that relates the hour in a Gregorian year to the year fraction in an imperial year. */
class YearFractionGregorianConverter extends GregorianConverter {
    
    const int HOURS_PER_DAY = 24;
    
    /** The "Makr Constant" described in the Fandom article. */
    const float MAKR = 0.11407955;
    
    public float $hours;
    
    public float $days {
        
        set {
            $this->hours = $value * $this::HOURS_PER_DAY;
        }
        
        get => $this->hours / $this::HOURS_PER_DAY;
        
    }
    
    public YearFraction $yearFraction {
        
        get => YearFraction::fromCount($this->hours * $this::MAKR);
        
        set(YearFraction $yearFraction) {
            $this->hours = $yearFraction->count / $this::MAKR;
        }
        
    }
    
    // Custom constructors from hours, days and year fractions.
    
    static function fromHours(float $hours): static {
        $new = new static;
        $new->hours = $hours;
        return $new;
    }
    
    static function fromDays(float $days): static {
        $new = new static;
        $new->days = $days;
        return $new;
    }
    
    static function fromYearFraction(YearFraction $yearFraction): static {
        $new = new static;
        $new->yearFraction = $yearFraction;
        return $new;
    }
    
}

/** An interface contracting for conversion between imperial and Gregorian dates, for use by a converter class.
 
In keeping with the check number system, dates converted to imperial dates from foreign calendars are "approximations", so are assigned the check number 9. This behaviour is controlled by the boolean argument `makeApproximation` in `gregorianToImperial`. */
interface CanConvertImperialGregorian {
    function imperialToGregorian(ImperialDate $imperialDate): \DateTime;
    function gregorianToImperial(\DateTimeInterface $gregorianDate, bool $makeApproximation = true): ImperialDate;
}

/** Fulfilment of the `CanConvertImperialGregorian` interface. */
trait ImperialGregorianConversionMethods {
    
    function imperialToGregorian(ImperialDate $imperialDate): \DateTime {
        
        // The year will be the millenium, minus one, plus the count of the year. If the year element is omitted, assume one.
        
        $millenia = ($imperialDate->millenium->value * Millenium::YEARS);
        
        $year = $imperialDate->year;
        
        if (isset($year)) {
            $year = $year->count;
        } else {
            $year = 1;
        }
        
        $year += $millenia;
        
        // The gregorian date will have a value of hours appended to it. This will be the determined hour conversion of the year fraction but floored to produce an hour value. If there is no year fraction, add nothing.
        
        $yearFraction = $imperialDate->yearFraction;
        
        if (isset($yearFraction)) {
            $yearFractionConverter = YearFractionGregorianConverter::fromYearFraction($imperialDate->yearFraction);
            $hours = ((int)$yearFractionConverter->hours) - 1;
        } else {
            $hours = 0;
        }
        
        $gregorianDateFormatted = "+" . $year . "-01-01 +" . $hours . " hours";
        return new \DateTime($gregorianDateFormatted);
        
    }
    
    function gregorianToImperial(\DateTimeInterface $gregorianDate, bool $makeApproximation = true): ImperialDate {
        
        /// Derive the millenium, year, day and hour values from the Gregorian date.
        
        $yearsPerMillenium = Millenium::YEARS;
        
        // The millenium will be the number of millenia in the year, rounded up.
        
        $years = $gregorianDate->format("Y");
        
        $millenium = ceil($years / $yearsPerMillenium);
        $milleniumElement = Millenium::fromCount($millenium);
        
        // The year element will be the year within the millenium, i.e. minus the elapsed millenia.
        
        $elapsedMillenia = $millenium - 1;
        $elapsedMilleniaInYears = $elapsedMillenia * $yearsPerMillenium;
        $year = $years - $elapsedMilleniaInYears;
        $yearElement = Year::fromCount($year);
        
        $days = $gregorianDate->format("z");
        $hours = $gregorianDate->format("H");
        
        $hoursInYear = ($days * YearFractionGregorianConverter::HOURS_PER_DAY) + $hours;
        
        $yearFraction = YearFractionGregorianConverter::fromHours($hoursInYear)->yearFraction;
        
        // Optionally prepend check number 9 if the approximation boolean is enabled.
        $checkNumber = $makeApproximation ? CheckNumber::fromIndex(9) : null;
        
        return ImperialDate::fromElements($milleniumElement, $yearElement, $yearFraction, $checkNumber);
        
    }
    
}

/** A converter between an imperial date and a Gregorian date. This assigns the millenium of the Gregorian date directly to the millenium element of the imperial date and likewise with the year within that millenium. The total hours within that year are then converted to the year fraction using `YearFractionGregorianConverter`.
 
In keeping with the check number system, dates converted to imperial dates from foreign calendars are "approximations", so are assigned the check number 9. This behaviour is controlled by the boolean property `makeApproximation`. */
class ImperialGregorianConverter extends GregorianConverter implements HasGregorianDate, CanConvertImperialGregorian {
    
    public \DateTimeInterface $gregorianDate;
    
    use ImperialGregorianConversionMethods;
    
    /** Whether to prepend the check number 9 (approximation) to an imperial date converted from a gregorian date, as according to the Fandom article, imperial dates converted from foreign calendars are "approximations". */
    public bool $makeApproximation = true;
    
    /** The equivalent imperial date.
    
    When set, this will be converted into an equivalent Gregorian date, specifically a `DateTime`. If the year element is omitted, the resulting Gregorian date will assume the first year of the specified millenium, lacking any more specific information. If the year fraction is omitted, the Gregorian date will have an unspecified day within the year. The `DateTime` class will handle omission as normal.
    
    If the `makeApproximation` boolean is `true`, check number 9 will be prepended (see property `makeApproximation` or class comment). */
    public ImperialDate $imperialDate {
        
        get => $this->gregorianToImperial($this->gregorianDate, $this->makeApproximation);
        
        set {
            $this->gregorianDate = $this->imperialToGregorian($value);
        }
        
    }
    
    static function fromGregorianDate(\DateTimeInterface $gregorianDate, bool $makeApproximation = true): static {
        $new = new static;
        $new->gregorianDate = $gregorianDate;
        $new->makeApproximation = $makeApproximation;
        return $new;
    }
    
    static function fromImperialDate(ImperialDate $imperialDate): static {
        $new = new static;
        $new->imperialDate = $imperialDate;
        return $new;
    }
    
}

// Procedural-style functions to convert between imperial and gregorian dates, if preferred.

function imperialToGregorian(ImperialDate $imperial): \DateTime {
    return ImperialGregorianConverter::fromImperialDate($imperial)->gregorianDate;
}

/** See the interface `CanConvertImperialGregorian` for an explanation of the `makeApproximation` argument. */
function gregorianToImperial(\DateTimeInterface $gregorian, bool $makeApproximation = true): ImperialDate {
    return ImperialGregorianConverter::fromGregorianDate($gregorian, $makeApproximation)->imperialDate;
}