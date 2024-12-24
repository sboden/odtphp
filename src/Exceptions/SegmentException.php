<?php

namespace Odtphp\Exceptions;

/**
 * Exception thrown for errors specific to ODT document segment processing.
 *
 * This exception is raised when issues occur during segment manipulation,
 * such as invalid segment creation, merging, or variable substitution.
 */
class SegmentException extends \Exception {
}
