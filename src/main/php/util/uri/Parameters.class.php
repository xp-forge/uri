<?php namespace util\uri;

use IteratorAggregate, Traversable;
use lang\{FormatException, Value};
use util\{Objects, URI};

/**
 * URI Parameters
 *
 * @see   http://php.net/urldecode
 * @see   http://php.net/urlencode
 * @see   https://en.wikipedia.org/wiki/Query_string#URL_encoding
 * @test  util.unittest.URIParametersTest
 */
class Parameters implements Value, IteratorAggregate {
  private $pairs= [];

  /**
   * Creates an instance from either a URI or a string
   *
   * @param  util.URI|string $arg
   */
  public function __construct($arg) {
    $this->pairs= self::decode($arg instanceof URI ? $arg->query() : $arg);
  }

  /**
   * Creates a string representation of a given pair
   *
   * @param  string $key
   * @param  string $value
   * @param  string $offset
   * @return string
   */
  private static function pair($key, $value, $offset= '') {
    $query= '';
    if ('' === $value) {
      $query.= '&'.urlencode($key).$offset;
    } else if (is_array($value)) {
      if (0 === key($value)) {
        $offset.= '[]';
        foreach ($value as $v) {
          $query.= self::pair($key, $v, $offset);
        }
      } else {
        foreach ($value as $k => $v) {
          $query.= self::pair($key, $v, $offset.'['.urlencode($k).']');
        }
      }
    } else {
      $query.= '&'.urlencode($key).$offset.'='.urlencode($value);
    }
    return $query;
  }

  /**
   * Encodes parameters to a string
   *
   * @param  [:var] $pairs
   * @param  string $default
   * @return string
   */
  public static function encode($pairs, $default= '') {
    if (empty($pairs)) return $default;

    $query= '';
    foreach ($pairs as $key => $value) {
      $query.= self::pair($key, $value);
    }
    return substr($query, 1);
  }

  /**
   * Decodes a string into pairs
   *
   * @param  string $query
   * @param  int $nesting Maximum nesting depth for arrays
   * @see    http://php.net/manual/en/info.configuration.php#ini.max-input-nesting-level
   * @return [:var]
   */
  public static function decode($query, $nesting= 0) {
    if ('' === $query) return [];

    $nesting || $nesting= ini_get('max_input_nesting_level') ?: 64;
    $pairs= [];
    foreach (explode('&', (string)$query) as $pair) {
      $key= $value= null;
      sscanf($pair, "%[^=]=%[^\r]", $key, $value);
      if (null === $key) continue;

      $key= urldecode($key);
      if (substr_count($key, '[') !== substr_count($key, ']')) {
        throw new FormatException('Unbalanced [] in query string');
      }

      if ($start= strpos($key, '[')) {    // Array notation
        $base= substr($key, 0, $start);
        if (!isset($pairs[$base]) || !is_array($pairs[$base])) {
          $pairs[$base]= [];
        }
        $ptr= &$pairs[$base];
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
        $ptr= urldecode($value ?? '');
      } else {
        $pairs[$key]= urldecode($value ?? '');
      }
    }
    return $pairs;
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
    return $this->pairs[$name] ?? $default;
  }

  /** Iterates over all pairs */
  public function getIterator(): Traversable {
    yield from $this->pairs;
  }

  /** @return string */
  public function __toString() {
    return self::encode($this->pairs);
  }

  /** @return string */
  public function toString() {
    return nameof($this).'<'.self::encode($this->pairs).'>';
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