<?php

/**

A module for conversion to and from code forms of classes.

Version
-------

0.0.0 (2025-9-10)

PHP Version
-----------

8.4.11

*/

namespace Code;

/** An interface providing a contract for a code form of a class, which is the computer code form used as a shorthand. This provides a contract for a virtual code property that translates to other properties. */
interface HasCode {
    public string $code {set; get;}
}

/** An interface for a custom constructor from code. */
interface ConstructableFromCode {
    static function fromCode(string $code): static;
}

/** A trait for a custom constructor directly from code which refers to a `code` property, thus fulfilling the `ConstructableFromCode` interface. */
trait CustomCodeConstructor {
    static function fromCode(string $code): static {
        $new = new static;
        $new->code = $code;
        return $new;
    }
}

/** A trait for a special string conversion method that refers to the code form. This conversion will likely be expected for elements of custom languages. */
trait CodeStringConverter {
    function __toString() {
        return $this->code;
    }
}
