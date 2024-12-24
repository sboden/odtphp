<?php

declare(strict_types=1);

namespace Odtphp\Attributes;

/**
 * Attribute to allow dynamic properties in PHP 8.2+ classes.
 *
 * This attribute provides backward compatibility for classes that need
 * to use dynamic properties in PHP versions that have disabled them by default.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class AllowDynamicProperties {

  public function __construct() {}

}
