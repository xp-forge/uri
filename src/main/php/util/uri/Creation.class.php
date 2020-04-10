<?php namespace util\uri;

use lang\IllegalStateException;
use util\{Authority, Secret, URI};

/**
 * Creates URI instances 
 *
 * @see   xp://util.URI#using
 * @see   xp://util.URI#with
 * @test  xp://net.xp_framework.unittest.util.URICreationTest
 */
class Creation {
  public $scheme     = null;
  public $authority  = null;
  public $path       = null;
  public $query      = null;
  public $fragment   = null;
  private $params    = null;
  private $merge     = [];

  /**
   * Initialize creation instance
   *
   * @param  util.URI $uri Optional value to modify
   */
  public function __construct($uri= null) {
    if (null === $uri) return;

    $this->scheme= $uri->scheme();
    $this->authority= $uri->authority();

    // Do not decode these URI parts
    $this->path= $uri->path(false);
    $this->query= $uri->query(false);
    $this->fragment= $uri->fragment(false);
  }

  /**
   * Sets scheme - mandatory!
   *
   * @param  string $value
   * @return self
   */
  public function scheme($value) { $this->scheme= $value; return $this; }

  /**
   * Sets authority (use NULL to remove)
   *
   * @param  string|util.Authority $value
   * @return self
   */
  public function authority($value) {
    if (null === $value) {
      $this->authority= null;
    } else if ($value instanceof Authority) {
      $this->authority= $value;
    } else {
      $this->authority= Authority::parse($value);
    }
    return $this;
  }

  /**
   * Sets host
   *
   * @param  string $value
   * @return self
   */
  public function host($value) { $this->merge['host']= $value; return $this; }

  /**
   * Sets port
   *
   * @param  string $value
   * @return self
   */
  public function port($value) { $this->merge['port']= $value; return $this; }

  /**
   * Sets user
   *
   * @param  string $value
   * @return self
   */
  public function user($value) { $this->merge['user']= $value; return $this; }

  /**
   * Sets password
   *
   * @param  string|util.Secret $value
   * @return self
   */
  public function password($value) {
    if (null === $value) {
      $this->merge['password']= null;
    } else if ($value instanceof Secret) {
      $this->merge['password']= $value;
    } else {
      $this->merge['password']= new Secret($value);
    }
    return $this;
  }

  /**
   * Encode URI component
   *
   * @param   string $input
   * @param   string $mask
   * @param   string $encode
   * @return  string
   */
  private function escape($input, $mask, $encode) {
    $mask= 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789.-_~'.$mask;
    $l= strlen($input);
    $s= '';
    $o= 0;
    do {
      $p= strspn($input, $mask, $o);
      $s.= substr($input, $o, $p);
      $o+= $p;
      if ($o >= $l) break;
      $s.= $encode($input[$o]);
    } while ($o++);
    return $s;
  }

  /**
   * Sets path (use NULL to remove), encoding by default. Multiple paths
   * are joined together
   *
   * @param  string|string[] $value
   * @param  bool $encode
   * @return self
   */
  public function path($value, $encode= true) {
    if (null === $value) {
      $this->path= null;
    } else if (is_array($value)) {
      $this->path= '';
      foreach ($value as $path) {
        $this->path= rtrim($this->path, '/').'/'.($encode
          ? $this->escape(ltrim($path, '/'), '@+/:', 'rawurlencode')
          : ltrim($path, '/')
        );
      }
    } else {
      $this->path= $encode ? $this->escape($value, '@+/:', 'rawurlencode') : $value;
    }
    return $this;
  }

  /**
   * Sets query (use NULL to remove), encoding by default
   *
   * @param  string $value
   * @param  bool $encode
   * @return self
   */
  public function query($value, $encode= true) {
    $this->query= null === $value || !$encode ? $value : $this->escape($value, '&=', 'urlencode');
    return $this;
  }

  /**
   * Sets params (use NULL to remove)
   *
   * @param  [:var]|util.uri.Parameters $value
   * @return self
   */
  public function params($value) { 
    $this->params= $value instanceof Parameters ? $value->pairs() : $value;
    return $this;
  }

  /**
   * Sets a given named parameter to a value (use NULL to remove)
   *
   * @param  string $name
   * @param  string $value
   * @return self
   */
  public function param($name, $value) {
    if (null === $this->params) {
      $this->params= Parameters::decode($this->query);
    }

    if (null === $value) {
      unset($this->params[$name]);
    } else {
      $this->params[$name]= $value;
    }
    return $this;
  }

  /**
   * Sets fragment (use NULL to remove), encoding by default
   *
   * @param  string $value
   * @param  bool $encode
   * @return self
   */
  public function fragment($value, $encode= true) {
    $this->fragment= null === $value || !$encode ? $value : $this->escape($value, '&=', 'urlencode');
    return $this;
  }

  /**
   * Creates the URI
   *
   * @return util.URI
   * @throws lang.IllegalStateException
   */
  public function create() {

    // If host, port, user or password was set directly, merge
    if (!empty($this->merge)) {
      $this->authority= new Authority(
        array_key_exists('host', $this->merge) ? $this->merge['host'] : ($this->authority ? $this->authority->host() : null),
        array_key_exists('port', $this->merge) ? $this->merge['port'] : ($this->authority ? $this->authority->port() : null),
        array_key_exists('user', $this->merge) ? $this->merge['user'] : ($this->authority ? $this->authority->user() : null),
        array_key_exists('password', $this->merge) ? $this->merge['password'] : ($this->authority ? $this->authority->password() : null)
      );
    }

    // If parameters were given, overwrite query with encoded parameters
    if (null !== $this->params) {
      $this->query= Parameters::encode($this->params, null);
    }

    // Sanity check
    if (null === $this->scheme) {
      throw new IllegalStateException('Cannot create URI without scheme');
    } else if (null === $this->authority && null === $this->path) {
      throw new IllegalStateException('Need either authority or path to create URI');
    }

    return new URI($this);
  }
}