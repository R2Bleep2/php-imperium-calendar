<?php

/**

Version: 0.0.0 (2025-9-12).

PHP version: 8.4.11

An include file providing definitions for conversion of a class to DOM nodes, specifically those of PHP's "DOM" module. This will mainly be used to assist with conversion to HTML for presentation on a website.

*/

namespace ToDOM;

/** A base interface for something that can be converted to some kind of DOM node. */
interface ConvertibleToDOMNode {}

/** An interface for conversion to a HTML element, the expected most common case of DOM conversion, useful for presentation in a website. This contracts for a method `toHTMLElement` which returns the resultant conversion. There is a parameter for the HTML document in which the new element is to be inserted. */
interface ConvertibleToHTMLElement extends ConvertibleToDOMNode {
    function toHTMLElement(\DOM\HTMLDocument $htmlDocument): \DOM\HTMLElement;
}

