<?php

namespace App\Utilities;

/**
 * Trait SingletonTrait
 */
trait SingletonTrait {
    /**
     * The single instance of class
     *
     * @var object
     */
    private static $instance = null;

    protected function __construct() {}

    /**
     * Get instance of class
     *
     * @return object
     */
    final public static function instance() {
        if ( is_null( static::$instance ) ) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Prevent cloning.
     */
    private function __clone() {}

    /**
     * Prevent unserializing.
     */
    private function __wakeup() {}
}
