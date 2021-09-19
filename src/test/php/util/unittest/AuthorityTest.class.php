<?php namespace util\unittest;

use lang\FormatException;
use unittest\{Assert, Expect, Test, Values};
use util\{Authority, Secret};

class AuthorityTest {

  /** @return iterable */
  private function hosts() {
    yield 'example.com';
    yield '127.0.0.1';
    yield '[::1]';
  }

  #[Test]
  public function can_create() {
    new Authority('example.com');
  }

  #[Test]
  public function host() {
    Assert::equals('example.com', (new Authority('example.com'))->host());
  }

  #[Test]
  public function port() {
    Assert::equals(80, (new Authority('example.com', 80))->port());
  }

  #[Test]
  public function port_defaults_to_null() {
    Assert::null((new Authority('example.com'))->port());
  }

  #[Test]
  public function user() {
    Assert::equals('test', (new Authority('example.com', 80, 'test'))->user());
  }

  #[Test]
  public function user_defaults_to_null() {
    Assert::null((new Authority('example.com'))->user());
  }

  #[Test, Values(eval: '[["secret"], [new Secret("secret")]]')]
  public function password($password) {
    Assert::equals('secret', (new Authority('example.com', 80, 'test', $password))->password()->reveal());
  }

  #[Test]
  public function password_defaults_to_null() {
    Assert::null((new Authority('example.com'))->password());
  }

  #[Test, Values('hosts')]
  public function parse_host_only($host) {
    Assert::equals(new Authority($host), Authority::parse($host));
  }

  #[Test, Values('hosts')]
  public function parse_host_and_port($host) {
    Assert::equals(new Authority($host, 443), Authority::parse($host.':443'));
  }

  #[Test, Values('hosts')]
  public function parse_host_and_user($host) {
    Assert::equals(new Authority($host, null, 'test'), Authority::parse('test@'.$host));
  }

  #[Test, Values('hosts')]
  public function parse_host_and_credentials($host) {
    Assert::equals(new Authority($host, null, 'test', 'secret'), Authority::parse('test:secret@'.$host));
  }

  #[Test, Values('hosts')]
  public function parse_urlencoded_credentials($host) {
    Assert::equals(new Authority($host, null, '@test:', 'sec ret'), Authority::parse('%40test%3A:sec%20ret@'.$host));
  }

  #[Test, Expect(FormatException::class)]
  public function parse_empty() {
    Authority::parse('');
  }

  #[Test, Expect(FormatException::class), Values(['user:', 'user@', 'user:password@', 'user@:8080', 'user:password@:8080', ':8080', ':foo', ':123foo', ':foo123', 'example.com:foo', 'example.com:123foo', 'example.com:foo123', 'example$'])]
  public function parse_malformed($arg) {
    Authority::parse($arg);
  }

  #[Test]
  public function password_hidden_in_as_string() {
    Assert::equals(
      'user:********@example.com',
      Authority::parse('user:password@example.com')->asString(false)
    );
  }

  #[Test]
  public function reveal_password_in_as_string() {
    Assert::equals(
      'user:password@example.com',
      Authority::parse('user:password@example.com')->asString(true)
    );
  }

  #[Test]
  public function password_included_in_string_cast() {
    Assert::equals(
      'user:password@example.com',
      (string)Authority::parse('user:password@example.com')
    );
  }

  #[Test]
  public function password_hidden_in_to_string() {
    Assert::equals(
      'util.Authority<user:********@example.com>',
      Authority::parse('user:password@example.com')->toString()
    );
  }
}