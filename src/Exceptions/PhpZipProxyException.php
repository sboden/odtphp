<?php

namespace Odtphp\Exceptions;

/**
 * Exception thrown for errors specific to PHP ZIP proxy operations.
 *
 * This exception is raised when issues occur while using the native PHP ZIP
 * extension for file handling in the ODT processing workflow.
 */
class PhpZipProxyException extends \Exception {
}
