<?php namespace util\unittest;

use util\Authority;
use util\Secret;
use util\URI;
use util\uri\Creation;
use util\uri\Parameters;

class URICreationTest extends \unittest\TestCase {

  #[@test]
  public function can_create() {
    new Creation();
  }

  #[@test]
  public function can_create_with_uri() {
    new Creation(new URI('http://example.com'));
  }

  #[@test]
  public function opaque_uri() {
    $this->assertEquals(
      new URI('mailto:test@example.com'),
      (new Creation())->scheme('mailto')->path('test@example.com')->create()
    );
  }

  #[@test, @values([['test@example.com:22'], [new Authority('example.com', 22, 'test')]])]
  public function hierarchical_uri($authority) {
    $this->assertEquals(
      new URI('ssh://test@example.com:22'),
      (new Creation())->scheme('ssh')->authority($authority)->create()
    );
  }

  #[@test]
  public function file_uri_requires_empty_authority() {
    $this->assertEquals(
      new URI('file:///usr/local/etc/php.ini'),
      (new Creation())->scheme('file')->authority(Authority::$EMPTY)->path('/usr/local/etc/php.ini')->create()
    );
  }

  #[@test]
  public function host() {
    $this->assertEquals(
      new URI('http://example.com'),
      (new Creation())->scheme('http')->host('example.com')->create()
    );
  }

  #[@test]
  public function port() {
    $this->assertEquals(
      new URI('http://example.com:80'),
      (new Creation())->scheme('http')->host('example.com')->port(80)->create()
    );
  }

  #[@test]
  public function without_port() {
    $this->assertEquals(
      new URI('http://example.com'),
      (new Creation())->scheme('http')->host('example.com')->port(null)->create()
    );
  }

  #[@test]
  public function user() {
    $this->assertEquals(
      new URI('http://test@example.com'),
      (new Creation())->scheme('http')->host('example.com')->user('test')->create()
    );
  }

  #[@test, @values([['secret'], [new Secret('secret')]])]
  public function password($password) {
    $this->assertEquals(
      new URI('http://test:secret@example.com'),
      (new Creation())->scheme('http')->host('example.com')->user('test')->password($password)->create()
    );
  }

  #[@test]
  public function query() {
    $this->assertEquals(
      new URI('mailto:test@example.com?Subject=Hello'),
      (new Creation())->scheme('mailto')->path('test@example.com')->query('Subject=Hello')->create()
    );
  }

  #[@test]
  public function query_encoding() {
    $this->assertEquals(
      new URI('mailto:test@example.com?Subject=Hello+World'),
      (new Creation())->scheme('mailto')->path('test@example.com')->query('Subject=Hello World')->create()
    );
  }

  #[@test]
  public function fragment() {
    $this->assertEquals(
      new URI('http://example.com#home'),
      (new Creation())->scheme('http')->host('example.com')->fragment('home')->create()
    );
  }

  #[@test]
  public function no_fragment() {
    $this->assertEquals(
      new URI('http://example.com'),
      (new Creation())->scheme('http')->host('example.com')->fragment(null)->create()
    );
  }

  #[@test]
  public function fragment_encoding() {
    $this->assertEquals(
      new URI('http://example.com#to+top'),
      (new Creation())->scheme('http')->host('example.com')->fragment('to top')->create()
    );
  }

  #[@test]
  public function modify_uri_given_to_constructor() {
    $this->assertEquals(
      new URI('https://example.com:443'),
      (new Creation(new URI('http://example.com')))->scheme('https')->port(443)->create()
    );
  }

  #[@test]
  public function param() {
    $this->assertEquals(
      new URI('mailto:test@example.com?Subject=%C3%9Cber'),
      (new Creation())->scheme('mailto')->path('test@example.com')->param('Subject', 'Über')->create()
    );
  }

  #[@test, @values([
  #  [['Subject' => 'Über']],
  #  [new Parameters('Subject=%C3%9Cber')]
  #])]
  public function params($argument) {
    $this->assertEquals(
      new URI('mailto:test@example.com?Subject=%C3%9Cber'),
      (new Creation())->scheme('mailto')->path('test@example.com')->params($argument)->create()
    );
  }

  #[@test, @values([
  #  ['http://localhost'],
  #  [new URI('http://localhost')]
  #])]
  public function param_is_encoded($uri) {
    $this->assertEquals(
      new URI('https://example.com/login?service=http%3A%2F%2Flocalhost'),
      (new URI('https://example.com/login'))->using()->param('service', $uri)->create()
    );
  }

  #[@test]
  public function remove_port() {
    $this->assertEquals(null, (new URI('http://example.com:8080'))->using()->port(null)->create()->port());
  }

  #[@test]
  public function remove_user() {
    $this->assertEquals(null, (new URI('http://test@example.com'))->using()->user(null)->create()->user());
  }

  #[@test]
  public function remove_password() {
    $this->assertEquals(null, (new URI('http://test:secret@example.com'))->using()->password(null)->create()->password());
  }

  #[@test]
  public function removing_last_param_also_removes_question_mark() {
    $this->assertEquals(
      'http://example.com/',
      (string)(new URI('http://example.com/?ticket=ABC'))->using()->param('ticket', null)->create()
    );
  }

  #[@test]
  public function removing_all_params_also_removes_question_mark() {
    $this->assertEquals(
      'http://example.com/',
      (string)(new URI('http://example.com/?ticket=ABC'))->using()->params([])->create()
    );
  }

  #[@test]
  public function removing_fragment_also_removes_hash() {
    $this->assertEquals(
      'http://example.com/',
      (string)(new URI('http://example.com/#top'))->using()->fragment(null)->create()
    );
  }

  #[@test]
  public function removing_path() {
    $this->assertEquals(
      'http://example.com',
      (string)(new URI('http://example.com/test'))->using()->path(null)->create()
    );
  }

  #[@test, @values(['/', '/test', '/test/child'])]
  public function path($path) {
    $this->assertEquals(
      'http://example.com'.$path,
      (string)(new URI('http://example.com/'))->using()->path($path)->create()
    );
  }

  #[@test, @values([
  #  'https://example.com/login?service=http%3A%2F%2Flocalhost',
  #  'https://example.com/login?service=http%3A%2F%2Flocalhost%2F%3Fservice%3D%2521ncoded',
  #])]
  public function encoded_params_are_passed_through($uri) {
    $this->assertEquals(new URI($uri), (new Creation(new URI($uri)))->create());
  }

  #[@test, @values([
  #  'https://example.com/%21ncoded',
  #  'https://example.com/%2521ncoded',
  #])]
  public function encoded_paths_are_passed_through($uri) {
    $this->assertEquals(new URI($uri), (new Creation(new URI($uri)))->create());
  }

  #[@test, @values([
  #  'https://example.com/#%21ncoded',
  #  'https://example.com/#%2521ncoded',
  #])]
  public function encoded_fragments_are_passed_through($uri) {
    $this->assertEquals(new URI($uri), (new Creation(new URI($uri)))->create());
  }
}