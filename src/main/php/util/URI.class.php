<?php namespace util;

use lang\Value;
use lang\FormatException;
use util\uri\Creation;
use util\uri\Canonicalization;
use util\uri\Parameters;

/**
 * A Uniform Resource Identifier (URI) is a compact sequence of
 * characters that identifies an abstract or physical resource.
 *
 * ```
 *   foo://example.com:8042/over/there?name=ferret#nose
 *   \_/   \______________/\_________/ \_________/ \__/
 *    |           |            |            |        |
 * scheme     authority       path        query   fragment
 *    |   _____________________|__
 *   / \ /                        \
 *   urn:example:animal:ferret:nose
 * ```
 *
 * This class implements a URI reference, which is either a
 * URI or a relative reference.
 *
 * @see   https://tools.ietf.org/html/rfc3986
 * @test  xp://net.xp_framework.unittest.util.URITest
 */
class URI implements Value {
  private $scheme, $authority, $path, $query, $fragment;
  private $params= null;

  /**
   * Creates a URI instance, either by parsing a string or by using a
   * given `util.uri.Creation` instance, which offers a fluent interface.
   *
   * @see    https://tools.ietf.org/html/rfc3986#section-1.1.2
   * @param  string|util.uri.Creation $base
   * @param  string $relative Optional relative URI
   * @throws lang.FormatException if string argument cannot be parsed
   */
  public function __construct($base, $relative= null) {
    if ($base instanceof Creation) {
      $this->scheme= $base->scheme;
      $this->authority= $base->authority;
      $this->path= $base->path;
      $this->query= $base->query;
      $this->fragment= $base->fragment;
    } else if (false !== ($p= strpos($base, ':'))) {
      $this->scheme= substr($base, 0, $p);
      if (!preg_match('!^([a-zA-Z][a-zA-Z0-9+.-]*)$!', $this->scheme)) {
        throw new FormatException('Scheme "'.$this->scheme.'" malformed');
      }
      list($this->authority, $this->path, $this->query, $this->fragment)= $this->parse(substr($base, $p + 1));
    } else {
      $this->scheme= null;
      list($this->authority, $this->path, $this->query, $this->fragment)= $this->parse($base);
    }

    if (null !== $relative) {
      $this->resolve0(...$this->parse($relative));
    }
  }

  /**
   * Parse relative URI into authority, path, query and fragment
   *
   * @param  string $relative
   * @return var[]
   */
  private function parse($relative) {
    if (0 === strlen($relative)) {
      throw new FormatException('Cannot parse empty input');
    }

    preg_match('!^((//)([^/?#]*)(/[^?#]*)?|([^?#]*))(\?[^#]*)?(#.*)?!', $relative, $matches);
    if ('//' === $matches[2]) {
      $authority= '' === $matches[3] ? Authority::$EMPTY : Authority::parse($matches[3]);
      $path= isset($matches[4]) && '' !== $matches[4] ? $matches[4] : null;
    } else {
      $authority= null;
      $path= '' === $matches[5] ? null : $matches[5];
    }

    $query= isset($matches[6]) && '' !== $matches[6] ? substr($matches[6], 1) : null;
    $fragment= isset($matches[7]) && '' !== $matches[7] ? substr($matches[7], 1) : null;

    return [$authority, $path, $query, $fragment];
  }

  /**
   * Resolve authority, path, query and fragment against this URI
   *
   * @see    https://tools.ietf.org/html/rfc3986#section-5.2.2
   * @param  util.Authority $authority
   * @param  string $path
   * @param  string $query
   * @param  string $fragment
   * @return void
   */
  private function resolve0($authority, $path, $query, $fragment) {
    if ($authority) {
      $this->authority= $authority;
      $this->path= $path;
    } else if (null === $path) {
      if (null === $query) $query= $this->query;
    } else if ('/' === $path{0}) {
      $this->path= $path;
    } else if (null === $this->path) {
      $this->path= '/'.$path;
    } else if ('/' === $this->path{strlen($this->path)- 1}) {
      $this->path= $this->path.$path;
    } else {
      $this->path= substr($this->path, 0, strpos($this->path, '/')).'/'.$path;
    }

    $this->query= $query;
    $this->fragment= $fragment;
  }

  /**
   * Use a fluent interface to create a URI
   *
   * ```php
   * $uri= URI::with()
   *   ->scheme('http')
   *   ->authority('example.com')
   *   ->path('/index')
   *   ->create();
   * ;
   * ```
   *
   * @return util.uri.Creation
   */
  public static function with() { return new Creation(); }

  /**
   * Use a fluent interface to create a new URI based on this URI
   *
   * ```php
   * $uri= (new URI('http://example.com'))->using()->scheme('https')->create();
   * ```
   *
   * @return util.uri.Creation
   */
  public function using() { return new Creation($this); }

  /** @return bool */
  public function isRelative() { return null === $this->scheme; }

  /** @return bool */
  public function isOpaque() { return null === $this->authority; }

  /** @return string */
  public function scheme() { return $this->scheme; }

  /** @return util.Authority */
  public function authority() { return $this->authority; }

  /** @return string */
  public function host() { return $this->authority ? $this->authority->host() : null; }

  /** @return int */
  public function port() { return $this->authority ? $this->authority->port() : null; }

  /** @return string */
  public function user() { return $this->authority ? $this->authority->user() : null; }

  /** @return util.Secret */
  public function password() { return $this->authority ? $this->authority->password() : null; }

  /**
   * Gets path, decoding it by default
   *
   * @param  bool $decode
   * @return string
   */
  public function path($decode= true) {
    return null === $this->path || !$decode ? $this->path : rawurldecode($this->path);
  }

  /**
   * Gets query, decoding it by default
   *
   * @param  bool $decode
   * @return string
   */
  public function query($decode= true) {
    return null === $this->query || !$decode ? $this->query : rawurldecode($this->query);
  }

  /**
   * Gets fragment, decoding it by default
   *
   * @param  bool $decode
   * @return string
   */
  public function fragment($decode= true) {
    return null === $this->fragment || !$decode ? $this->fragment : rawurldecode($this->fragment);
  }

  /** @return self */
  public function canonicalize() { return (new Canonicalization())->canonicalize($this); }

  /** @return util.uri.Parameters */
  public function params() {
    if (null === $this->params) {
      $this->params= new Parameters($this->query);
    }
    return $this->params;
  }

  /**
   * Returns a given named parameter or a default value
   *
   * @param  string $name
   * @param  var $default
   * @param  var
   */
  public function param($name, $default= null) {
    return $this->params()->named($name, $default);
  }

  /**
   * Resolves another URI against this URI. If the given URI is absolute,
   * it's returned directly. Otherwise, a new URI is returned; with the
   * given URI's authority (if any), its path relativized against this URI's
   * path, and its query and fragment.
   *
   * @see    https://tools.ietf.org/html/rfc3986#section-5
   * @param  string|self $arg
   * @return self
   */
  public function resolve($arg) {
    $uri= $arg instanceof self ? $arg : new self($arg);

    if ($uri->scheme) {
      return $uri;
    } else {
      $result= clone $this;
      $result->resolve0($uri->authority, $uri->path, $uri->query, $uri->fragment);
      return $result;
    }
  }

  /**
   * Helper to create a string representations.
   *
   * @see    https://tools.ietf.org/html/rfc3986#section-5.3
   * @param  bool $reveal Whether to reveal password, if any
   * @return string
   */
  public function asString($reveal) {
    $s= '';
    isset($this->scheme) && $s.= $this->scheme.':';
    isset($this->authority) && $s.= '//'.$this->authority->asString($reveal);
    isset($this->path) && $s.= $this->path;
    isset($this->query) && $s.= '?'.$this->query;
    isset($this->fragment) && $s.= '#'.$this->fragment;
    return $s;
  }

  /** @return string */
  public function __toString() { return $this->asString(true); }

  /** @return string */
  public function toString() { return nameof($this).'<'.$this->asString(false).'>'; }

  /** @return string */
  public function hashCode() { return md5($this->asString(true)); }

  /**
   * Compare to another value
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    if ($value instanceof self) {
      $a= $this->asString(true);
      $b= $value->asString(true);
      return $a === $b ? 0 : ($a < $b ? -1 : 1);
    } else {
      return 1;
    }
  }
}