<?php

/**

# Disabled Magic Constructor

Version: 0.0.0 (2025-9-25)

PHP version: 8.4.11

A simple module containing a disabled magic constructor method. If the magic constructor is disabled, the use of custom constructors is enforced.

*/

trait DisabledMagicConstructor {
    protected function __construct() {}
}
