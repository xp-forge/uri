<?php namespace util\uri;

use util\URI;
use util\Authority;

/**
 * Canonicalizes URIs
 *
 * @test  xp://net.xp_framework.unittest.util.URICanonicalizationTest
 * @see   https://en.wikipedia.org/wiki/URL_normalization
 */
class Canonicalization {
  private static $defaults= [
    'http'  => 80,
    'https' => 443,
  ];

  /**
   * Normalize escape sequences
   *
   * @see    https://tools.ietf.org/html/rfc3986#section-2.3
   * @param  string $segment
   * @return string
   */
  public static function ofSegment($segment) {
    if (null === $segment) return $segment;

    return preg_replace_callback(
      '/%([0-9a-zA-Z]{2})/',
      function($match) {
        $code= hexdec($match[1]);
        if (
          $code >= 65 && $code <= 90 ||                                 // A-Z
          $code >= 97 && $code <= 122 ||                                // a-z
          $code >= 48 && $code <= 57 ||                                 // 0-9
          95 === $code || 45 === $code || 46 === $code || 126 === $code // -._~
        ) {
          return chr($code);
        } else {
          return strtoupper($match[0]);
        }
      },
      $segment
    );
  }

  /**
   * Path canonicalization normalizes escape sequences, replaces multiple
   * consecutive forward slashes by a single one and removes dot segments.
   *
   * @see    xp://util.uri.Canonicalization::normalize
   * @see    https://tools.ietf.org/html/rfc3986#section-5.2.4
   * @param  string $path
   * @return string
   */
  public static function ofPath($path) {
    if (null === $path) return '/';

    $path= preg_replace('![/]+!', '/', self::ofSegment($path));
    $output= '';
    while ('' !== $path) {

      // A. If the input begins with a prefix of "../" or "./", then remove
      // that prefix from the input buffer; otherwise,
      if (preg_match('!^(\.\./|\./)!', $path)) {
        $path= preg_replace('!^(\.\./|\./)!', '', $path);
      }

      // B. if the input buffer begins with a prefix of "/./" or "/.",
      // where "." is a complete path segment, then replace that
      // prefix with "/" in the input buffer; otherwise,
      else if (preg_match('!^(/\./|/\.$)!', $path, $matches)) {
        $path= preg_replace('!^'.$matches[1].'!', '/', $path);
      }

      // C. if the input buffer begins with a prefix of "/../" or "/..",
      // where ".." is a complete path segment, then replace that
      // prefix with "/" in the input buffer and remove the last
      // segment and its preceding "/" (if any) from the output
      // buffer; otherwise,
      else if (preg_match('!^(/\.\./|/\.\.$)!', $path, $matches)) {
        $path= preg_replace('!^'.preg_quote($matches[1], '!').'!', '/', $path);
        $output= preg_replace('!/([^/]+)$!', '', $output);
      }

      // D. if the input buffer consists only of "." or "..", then remove
      // that from the input buffer; otherwise,
      else if (preg_match('!^(\.|\.\.)$!', $path)) {
        $path= preg_replace('!^(\.|\.\.)$!', '', $path);
      }

      // E. move the first path segment in the input buffer to the end of
      // the output buffer, including the initial "/" character (if
      // any) and any subsequent characters up to, but not including,
      // the next "/" character or the end of the input buffer.
      else if (preg_match('!(/*[^/]*)!', $path, $matches)) {
        $path= preg_replace('/^'.preg_quote($matches[1], '/').'/', '', $path, 1);
        $output.= $matches[1];
      }
    }
    return $output;
  }

  /**
   * Canonicalize a given URI, returning a new one
   *
   * @param  util.URI $uri
   * @return util.URI
   */
  public function canonicalize(URI $uri) {
    sscanf($uri->scheme(), '%[^+]', $scheme);

    $creation= (new Creation($uri))
      ->scheme(strtolower($scheme))
      ->path(self::ofPath($uri->path(false)), false)
      ->query(self::ofSegment($uri->query(false)), false)
      ->fragment(self::ofSegment($uri->fragment(false)), false)
    ;

    if ($authority= $uri->authority()) {
      $port= isset(self::$defaults[$scheme]) ? self::$defaults[$scheme] : null;
      $creation->authority(new Authority(
        strtolower($authority->host()),
        $authority->port() === $port ? null : $authority->port(),
        $authority->user(),
        $authority->password()
      ));
    }
    return $creation->create();
  }
}