<?php namespace util;

use lang\FormatException;
use lang\Value;
use util\Objects;

/**
 * URI Parameters
 *
 * @test  xp://util.unittest.URIParametersTest
 */
class URIParameters implements Value, \IteratorAggregate {
  private $pairs= [];

  /**
   * Creates an instance from either a URI or a string
   *
   * @param  util.URI|string $arg
   * @param  int $nesting Maximum nesting depth for arrays
   * @see    http://php.net/manual/en/info.configuration.php#ini.max-input-nesting-level
   */
  public function __construct($arg, $nesting= 0) {
    $query= $arg instanceof URI ? $arg->query() : $arg;
    if ('' === $query) return;

    $nesting || $nesting= ini_get('max_input_nesting_level') ?: 64;
    foreach (explode('&', $query) as $pair) {
      $key= $value= null;
      sscanf($pair, "%[^=]=%[^\r]", $key, $value);
      if (null === $key) continue;

      $key= rawurldecode($key);
      if (substr_count($key, '[') !== substr_count($key, ']')) {
        throw new FormatException('Unbalanced [] in query string');
      }

      if ($start= strpos($key, '[')) {    // Array notation
        $base= substr($key, 0, $start);
        if (!isset($this->pairs[$base]) || !is_array($this->pairs[$base])) {
          $this->pairs[$base]= [];
        }
        $ptr= &$this->pairs[$base];
        $offset= 0;
        $level= 0;
        do {
          $end= strpos($key, ']', $offset);
          if ($start === $end - 1) {
            $ptr= &$ptr[];
          } else {
            $end+= substr_count($key, '[', $start + 1, $end - $start - 1);
            $ptr= &$ptr[substr($key, $start + 1, $end - $start - 1)];
          }
          $offset= $end + 1;
          if (++$level > $nesting) {
            throw new FormatException('Maximum nesting level ('.$nesting.') exceeded');
          }
        } while ($start= strpos($key, '[', $offset));
        $ptr= rawurldecode($value);
      } else {
        $this->pairs[$key]= rawurldecode($value);
      }
    }
  }

  /** @return int */
  public function size() { return sizeof($this->pairs); }

  /** @return [:var] */
  public function pairs() { return $this->pairs; }

  /**
   * Returns a parameter by a given name
   *
   * @param  string $name
   * @param  var $default
   * @return var
   */
  public function named($name, $default= null) {
    return isset($this->pairs[$name]) ? $this->pairs[$name] : $default;
  }

  /** @return iterable */
  public function getIterator() {
    foreach ($this->pairs as $key => $value) {
      yield $key => $value;
    }
  }

  /**
   * Creates a string representation of a given pair
   *
   * @param  string $key
   * @param  string $value
   * @param  string $offset
   * @return string
   */
  private function pair($key, $value, $offset= '') {
    $query= '';
    if ('' === $value) {
      $query.= '&'.rawurlencode($key).$offset;
    } else if (is_array($value)) {
      if (0 === key($value)) {
        $offset.= '[]';
        foreach ($value as $v) {
          $query.= $this->pair($key, $v, $offset);
        }
      } else {
        foreach ($value as $k => $v) {
          $query.= $this->pair($key, $v, $offset.'['.rawurlencode($k).']');
        }
      }
    } else {
      $query.= '&'.rawurlencode($key).$offset.'='.rawurlencode($value);
    }
    return $query;
  }

  /** @return string */
  public function __toString() {
    if (empty($this->pairs)) return '';

    $query= '';
    foreach ($this->pairs as $key => $value) {
      $query.= $this->pair($key, $value);
    }
    return substr($query, 1);
  }

  /** @return string */
  public function toString() {
    return nameof($this).'<'.$this->__toString().'>';
  }

  /** @return string */
  public function hashCode() {
    return Objects::hashOf($this->pairs);
  }

  /**
   * Compare to another value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $value instanceof self ? Objects::compare($this->pairs, $value->pairs) : 1;
  }
}