<?php namespace util;

use io\Path;
use lang\{FormatException, IllegalStateException, Value};
use util\uri\{Canonicalization, Creation, Parameters};

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
 * @see   http://stackoverflow.com/questions/1546419/convert-file-path-to-a-file-uri
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
    } else if (preg_match('/^([^:\/]*):(.+)/', $base, $matches)) {
      if (!preg_match('!^([a-zA-Z][a-zA-Z0-9+.-]*)$!', $matches[1])) {
        throw new FormatException('Scheme "'.$matches[1].'" malformed');
      }
      $this->scheme= $matches[1];
      list($this->authority, $this->path, $this->query, $this->fragment)= $this->parse($matches[2]);
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
    static $enc= null;

    if (0 === strlen($relative)) {
      throw new FormatException('Cannot parse empty input');
    }

    preg_match('!^((//)([^/?#]*)(/[^?#]*)?|([^?#]*))(\?[^#]*)?(#.*)?!', $relative, $matches);
    $enc ?? $enc= function($m) { return urlencode($m[0]); };
    if ('//' === $matches[2]) {
      $authority= '' === $matches[3] ? Authority::$EMPTY : Authority::parse($matches[3]);
      $path= isset($matches[4]) && '' !== $matches[4] ? preg_replace_callback('/[^\x00-\x7F]+/', $enc, $matches[4]) : null;
    } else {
      $authority= null;
      $path= '' === $matches[5] ? null : $matches[5];
    }

    $query= isset($matches[6]) && '' !== $matches[6] ? preg_replace_callback('/[^\x00-\x7F]+/', $enc, substr($matches[6], 1)) : null;
    $fragment= isset($matches[7]) && '' !== $matches[7] ? preg_replace_callback('/[^\x00-\x7F]+/', $enc, substr($matches[7], 1)) : null;

    return [$authority, $path, $query, $fragment];
  }

  /**
   * Resolve authority, path, query and fragment against this URI. Canonicalizes
   * path while doing so, removing `./` and `../`.
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
    } else if (null === $path) {
      if (null === $query) $query= $this->query;
    } else if ('/' === $path[0]) {
      // NOOP
    } else if (null === $this->path) {
      $path= '/'.$path;
    } else if ('/' === $this->path[strlen($this->path) - 1]) {
      $path= $this->path.$path;
    } else {
      $path= substr($this->path, 0, strrpos($this->path, '/')).'/'.$path;
    }

    $this->path= Canonicalization::ofPath($path);
    $this->query= $query;
    $this->fragment= $fragment;
  }

  /**
   * Creates a file URI. Handles Windows UNC paths.
   *
   * @param  string|io.Path $path
   * @return self
   */
  public static function file($path) {
    $path= str_replace('\\', '/', $path);
    if (0 === strncmp($path, '//', 2)) {
      $p= strcspn($path, '/', 3);
      $authority= substr($path, 2, $p + 1);
      $path= substr($path, $p + 3);
    } else {
      $authority= Authority::$EMPTY;
    }

    return (new Creation())->scheme('file')->authority($authority)->path($path)->create();
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

  /** @return ?string */
  public function host() { return $this->authority ? $this->authority->host() : null; }

  /** @return ?int */
  public function port() { return $this->authority ? $this->authority->port() : null; }

  /** @return ?string */
  public function user() { return $this->authority ? $this->authority->user() : null; }

  /** @return ?util.Secret */
  public function password() { return $this->authority ? $this->authority->password() : null; }

  /**
   * Returns the base URI. For opaque URIs, this is the scheme and the path;
   * for hierarchical URIs, this is the scheme and authority:
   *
   * - `mailto:test@example.com?subject=Test` -> `mailto:test@example.com`
   * - `http://localhost:443/test?of=example` -> `http://localhost:443`
   *
   * @return self
   * @throws lang.IllegalStateException for relative URIs
   */
  public function base() {
    if (null === $this->scheme) {
      throw new IllegalStateException('Relative URIs do not have a base');
    }

    $base= clone $this;
    $base->authority && $base->path= null;
    $base->query= null;
    $base->fragment= null;
    return $base;
  }

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
    return null === $this->query || !$decode ? $this->query : urldecode($this->query);
  }

  /**
   * Gets fragment, decoding it by default
   *
   * @param  bool $decode
   * @return string
   */
  public function fragment($decode= true) {
    return null === $this->fragment || !$decode ? $this->fragment : urldecode($this->fragment);
  }

  /** @return self */
  public function canonicalize() { return (new Canonicalization())->canonicalize($this); }

  /**
   * Returns a copy of this URI without authentication information
   *
   * @return self
   */
  public function anonymous() {
    $clone= clone $this;
    if ($this->authority) {
      $clone->authority= new Authority($this->authority->host(), $this->authority->port());
    }
    return $clone;
  }

  /**
   * Returns a copy of this URI with the given authentication information
   *
   * @param  string $user
   * @param  ?string|util.Secret $password
   * @return self
   */
  public function authenticated($user, $password= null) {
    $clone= clone $this;
    if ($this->authority) {
      $clone->authority= new Authority($this->authority->host(), $this->authority->port(), $user, $password);
    }
    return $clone;
  }

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
   * Converts this URI to a local path
   *
   * @return io.Path
   * @throws lang.IllegalStateException
   */
  public function asPath() {
    if ('file' !== $this->scheme && null !== $this->scheme) {
      throw new IllegalStateException('Cannot represent '.$this->scheme.' URIs as paths');
    }

    if ($remote= $this->authority->host()) {
      return new Path('//'.$remote, $this->path);
    } else {
      return new Path($this->path);
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