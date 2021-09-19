<?php namespace util\unittest;

use unittest\{Assert, Test, Values};
use util\uri\{Creation, Parameters};
use util\{Authority, Secret, URI};

class URICreationTest {

  #[Test]
  public function can_create() {
    new Creation();
  }

  #[Test]
  public function can_create_with_uri() {
    new Creation(new URI('http://example.com'));
  }

  #[Test]
  public function opaque_uri() {
    Assert::equals(
      new URI('mailto:test@example.com'),
      (new Creation())->scheme('mailto')->path('test@example.com')->create()
    );
  }

  #[Test, Values(eval: '[["test@example.com:22"], [new Authority("example.com", 22, "test")]]')]
  public function hierarchical_uri($authority) {
    Assert::equals(
      new URI('ssh://test@example.com:22'),
      (new Creation())->scheme('ssh')->authority($authority)->create()
    );
  }

  #[Test]
  public function file_uri_requires_empty_authority() {
    Assert::equals(
      new URI('file:///usr/local/etc/php.ini'),
      (new Creation())->scheme('file')->authority(Authority::$EMPTY)->path('/usr/local/etc/php.ini')->create()
    );
  }

  #[Test]
  public function host() {
    Assert::equals(
      new URI('http://example.com'),
      (new Creation())->scheme('http')->host('example.com')->create()
    );
  }

  #[Test]
  public function port() {
    Assert::equals(
      new URI('http://example.com:80'),
      (new Creation())->scheme('http')->host('example.com')->port(80)->create()
    );
  }

  #[Test]
  public function without_port() {
    Assert::equals(
      new URI('http://example.com'),
      (new Creation())->scheme('http')->host('example.com')->port(null)->create()
    );
  }

  #[Test]
  public function user() {
    Assert::equals(
      new URI('http://test@example.com'),
      (new Creation())->scheme('http')->host('example.com')->user('test')->create()
    );
  }

  #[Test, Values(eval: '[["secret"], [new Secret("secret")]]')]
  public function password($password) {
    Assert::equals(
      new URI('http://test:secret@example.com'),
      (new Creation())->scheme('http')->host('example.com')->user('test')->password($password)->create()
    );
  }

  #[Test]
  public function query() {
    Assert::equals(
      new URI('mailto:test@example.com?Subject=Hello'),
      (new Creation())->scheme('mailto')->path('test@example.com')->query('Subject=Hello')->create()
    );
  }

  #[Test]
  public function query_encoding() {
    Assert::equals(
      new URI('mailto:test@example.com?Subject=Hello+World'),
      (new Creation())->scheme('mailto')->path('test@example.com')->query('Subject=Hello World')->create()
    );
  }

  #[Test]
  public function fragment() {
    Assert::equals(
      new URI('http://example.com#home'),
      (new Creation())->scheme('http')->host('example.com')->fragment('home')->create()
    );
  }

  #[Test]
  public function no_fragment() {
    Assert::equals(
      new URI('http://example.com'),
      (new Creation())->scheme('http')->host('example.com')->fragment(null)->create()
    );
  }

  #[Test]
  public function fragment_encoding() {
    Assert::equals(
      new URI('http://example.com#to+top'),
      (new Creation())->scheme('http')->host('example.com')->fragment('to top')->create()
    );
  }

  #[Test]
  public function modify_uri_given_to_constructor() {
    Assert::equals(
      new URI('https://example.com:443'),
      (new Creation(new URI('http://example.com')))->scheme('https')->port(443)->create()
    );
  }

  #[Test]
  public function param() {
    Assert::equals(
      new URI('mailto:test@example.com?Subject=%C3%9Cber'),
      (new Creation())->scheme('mailto')->path('test@example.com')->param('Subject', 'Über')->create()
    );
  }

  #[Test, Values(eval: '[[["Subject" => "Über"]], [new Parameters("Subject=%C3%9Cber")]]')]
  public function params($argument) {
    Assert::equals(
      new URI('mailto:test@example.com?Subject=%C3%9Cber'),
      (new Creation())->scheme('mailto')->path('test@example.com')->params($argument)->create()
    );
  }

  #[Test, Values(eval: '[["http://localhost"], [new URI("http://localhost")]]')]
  public function param_is_encoded($uri) {
    Assert::equals(
      new URI('https://example.com/login?service=http%3A%2F%2Flocalhost'),
      (new URI('https://example.com/login'))->using()->param('service', $uri)->create()
    );
  }

  #[Test]
  public function remove_port() {
    Assert::equals(null, (new URI('http://example.com:8080'))->using()->port(null)->create()->port());
  }

  #[Test]
  public function remove_user() {
    Assert::equals(null, (new URI('http://test@example.com'))->using()->user(null)->create()->user());
  }

  #[Test]
  public function remove_password() {
    Assert::equals(null, (new URI('http://test:secret@example.com'))->using()->password(null)->create()->password());
  }

  #[Test]
  public function removing_last_param_also_removes_question_mark() {
    Assert::equals(
      'http://example.com/',
      (string)(new URI('http://example.com/?ticket=ABC'))->using()->param('ticket', null)->create()
    );
  }

  #[Test]
  public function removing_all_params_also_removes_question_mark() {
    Assert::equals(
      'http://example.com/',
      (string)(new URI('http://example.com/?ticket=ABC'))->using()->params([])->create()
    );
  }

  #[Test]
  public function removing_fragment_also_removes_hash() {
    Assert::equals(
      'http://example.com/',
      (string)(new URI('http://example.com/#top'))->using()->fragment(null)->create()
    );
  }

  #[Test]
  public function removing_path() {
    Assert::equals(
      'http://example.com',
      (string)(new URI('http://example.com/test'))->using()->path(null)->create()
    );
  }

  #[Test, Values(['/', '/test', '/test/child'])]
  public function path($path) {
    Assert::equals(
      'http://example.com'.$path,
      (string)(new URI('http://example.com/'))->using()->path($path)->create()
    );
  }

  #[Test, Values([[[], ''], [['/test'], '/test'], [['/test/'], '/test/'], [['/test', 'child/'], '/test/child/'], [['/test', 'child'], '/test/child'], [['/test/', 'child'], '/test/child'], [['/test//', 'child'], '/test/child'], [['/test', '/child'], '/test/child'], [['/test/', '/child'], '/test/child'], [['/test/', '//child'], '/test/child'], [['/test//', '//child'], '/test/child'], [['/test', 'child', 'sub'], '/test/child/sub'],])]
  public function combining_path($paths, $expected) {
    Assert::equals(
      'http://example.com'.$expected,
      (string)(new URI('http://example.com'))->using()->path($paths)->create()
    );
  }

  #[Test, Values(['https://example.com/login?service=http%3A%2F%2Flocalhost', 'https://example.com/login?service=http%3A%2F%2Flocalhost%2F%3Fservice%3D%2521ncoded',])]
  public function encoded_params_are_passed_through($uri) {
    Assert::equals(new URI($uri), (new Creation(new URI($uri)))->create());
  }

  #[Test, Values(['https://example.com/%21ncoded', 'https://example.com/%2521ncoded',])]
  public function encoded_paths_are_passed_through($uri) {
    Assert::equals(new URI($uri), (new Creation(new URI($uri)))->create());
  }

  #[Test, Values(['https://example.com/#%21ncoded', 'https://example.com/#%2521ncoded',])]
  public function encoded_fragments_are_passed_through($uri) {
    Assert::equals(new URI($uri), (new Creation(new URI($uri)))->create());
  }
}