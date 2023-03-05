<?php namespace util\unittest;

use test\{Assert, Test, Values};
use util\URI;
use util\uri\Canonicalization;

class URICanonicalizationTest {

  /**
   * Assertion helper
   *
   * @param  string $expected
   * @param  string $input
   * @throws unittest.AssertionFailedError
   */
  private function assertCanonical($expected, $input) {
    Assert::equals(new URI($expected), (new Canonicalization())->canonicalize(new URI($input)));
  }

  #[Test]
  public function scheme_is_lowercased() {
    $this->assertCanonical('http://localhost/', 'HTTP://localhost/');
  }

  #[Test]
  public function works_without_authority() {
    $this->assertCanonical('mailto:test@example.com', 'mailto:test@example.com');
  }

  #[Test]
  public function scheme_argument_is_removed() {
    $this->assertCanonical('https://localhost/', 'https+v3://localhost/');
  }

  #[Test]
  public function host_is_lowercased() {
    $this->assertCanonical('http://localhost/', 'http://LOCALHOST/');
  }

  #[Test]
  public function path_defaults_to_root() {
    $this->assertCanonical('http://localhost/', 'http://localhost');
  }

  #[Test, Values([['http://example.com:80/', 'http://example.com/'], ['https://example.com:443/', 'https://example.com/'], ['ftp://example.com:22/', 'ftp://example.com:22/'], ['http://example.com:8080/', 'http://example.com:8080/'], ['https://example.com:8443/', 'https://example.com:8443/']])]
  public function http_and_https_default_ports_are_removed($input, $expected) {
    $this->assertCanonical($expected, $input);
  }

  #[Test]
  public function escape_sequence_handling_in_path() {
    $this->assertCanonical('http://localhost/a%C2%B1b-._~%FC', 'http://localhost/a%c2%b1b%2D%2E%5F%7E%FC');
  }

  #[Test]
  public function escape_sequence_handling_in_query() {
    $this->assertCanonical('http://localhost/?param=a%C2%B1b-._~%FC', 'http://localhost/?param=a%c2%b1b%2D%2E%5F%7E%FC');
  }

  #[Test]
  public function escape_sequence_handling_in_fragment() {
    $this->assertCanonical('http://localhost/#a%C2%B1b-._~%FC', 'http://localhost/#a%c2%b1b%2D%2E%5F%7E%FC');
  }

  #[Test, Values([['http://localhost/.', 'http://localhost/'], ['http://localhost/..', 'http://localhost/'], ['http://localhost/a/./b', 'http://localhost/a/b'], ['http://localhost/a/././b', 'http://localhost/a/b'], ['http://localhost/a/../b', 'http://localhost/b'], ['http://localhost/a/./.././b', 'http://localhost/b'], ['http://localhost/a/b/c/./../../d', 'http://localhost/a/d'], ['http://localhost/.test', 'http://localhost/.test'], ['http://localhost/..test', 'http://localhost/..test'], ['http://localhost/./.test', 'http://localhost/.test'],])]
  public function removing_dot_sequences_from_path($input, $expected) {
    $this->assertCanonical($expected, $input);
  }

  #[Test]
  public function dot_sequences_not_removed_from_query() {
    $this->assertCanonical('http://localhost/?file=./../etc/passwd', 'http://localhost/?file=./../etc/passwd');
  }

  #[Test]
  public function dot_sequences_not_removed_from_fragment() {
    $this->assertCanonical('http://localhost/#file=./../etc/passwd', 'http://localhost/#file=./../etc/passwd');
  }

  #[Test, Values([['http://localhost//', 'http://localhost/'], ['http://localhost///', 'http://localhost/'], ['http://localhost/a//b', 'http://localhost/a/b'], ['http://localhost/a///b', 'http://localhost/a/b'],])]
  public function replacing_multiple_slashes_with_single_slashes_in_path($input, $expected) {
    $this->assertCanonical($expected, $input);
  }

  #[Test]
  public function file_triple_slash_will_be_left_intact() {
    $this->assertCanonical('file:///usr/bin', 'file:///usr/bin');
  }

  /** @see https://superuser.com/questions/267844/full-uri-to-a-file-on-another-machine-in-our-local-network */
  #[Test]
  public function unc_path_in_file_form() {
    $this->assertCanonical('file://192.168.10.20/f$/MyDir/SubDir/text.doc', 'file://192.168.10.20/f$/MyDir/SubDir/text.doc');
  }
}