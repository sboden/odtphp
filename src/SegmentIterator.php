<?php

declare(strict_types=1);

namespace Odtphp;

/**
 * Iterator for ODT document segments.
 *
 * Provides iteration functionality over document segments
 * implementing both Iterator and Countable interfaces.
 *
 * @implements \Iterator<int, Segment>
 */
class SegmentIterator implements \Iterator, \Countable {
  /**
   * Current position in the iterator.
   */
  private int $position = 0;

  /**
   * Initialize segment iterator.
   *
   * @param array<int, Segment> $segments
   *   Array of segments to iterate over.
   */
  public function __construct(
    private readonly array $segments,
  ) {}

  /**
   * Reset iterator position.
   */
  public function rewind(): void {
    $this->position = 0;
  }

  /**
   * Get current segment.
   */
  public function current(): Segment {
    return $this->segments[$this->position];
  }

  /**
   * Get current position.
   */
  public function key(): int {
    return $this->position;
  }

  /**
   * Move to next segment.
   */
  public function next(): void {
    ++$this->position;
  }

  /**
   * Check if current position is valid.
   */
  public function valid(): bool {
    return isset($this->segments[$this->position]);
  }

  /**
   * Get total number of segments.
   */
  public function count(): int {
    return count($this->segments);
  }

}
