<?php namespace util\unittest;

use io\Path;
use lang\{FormatException, IllegalStateException, Primitive};
use unittest\{Expect, Test, Values};
use util\{Authority, URI};

class URITest extends \unittest\TestCase {

  /** @return iterable */
  private function opaqueUris() {
    yield [new URI('mailto:fred@example.com')];
    yield [new URI('news:comp.infosystems.www.servers.unix')];
    yield [new URI('tel:+1-816-555-1212')];
    yield [new URI('urn:isbn:096139210x')];
  }

  /** @return iterable */
  private function hierarchicalUris() {
    yield [new URI('http://example.com')];
    yield [new URI('http://127.0.0.1:8080')];
    yield [new URI('http://user:pass@[::1]')];
    yield [new URI('ldap://example.com/c=GB?objectClass?one')];
  }

  /** @return iterable */
  private function relativeUris() {
    yield [new URI('/index.html')];
    yield [new URI('../../demo/index.html')];
    yield [new URI('//example.com/?a=b')];
    yield [new URI('/:p:/s/relative')];
    yield [new URI('./:p:/s/self')];
    yield [new URI('../:p:/s/parent')];
  }

  #[Test, Values('opaqueUris')]
  public function opaque_uris($uri) {
    $this->assertEquals([true, false], [$uri->isOpaque(), $uri->isRelative()]);
  }

  #[Test, Values('hierarchicalUris')]
  public function hierarchical_uris($uri) {
    $this->assertEquals([false, false], [$uri->isOpaque(), $uri->isRelative()]);
  }

  #[Test, Values('relativeUris')]
  public function relative_uris($uri) {
    $this->assertTrue($uri->isRelative());
  }

  #[Test, Values('opaqueUris')]
  public function opaque_uris_have_no_authority($uri) {
    $this->assertNull($uri->authority());
  }

  #[Test, Values('hierarchicalUris')]
  public function hierarchical_uris_have_authority($uri) {
    $this->assertInstanceOf(Authority::class, $uri->authority());
  }

  #[Test]
  public function scheme() {
    $this->assertEquals('http', (new URI('http://example.com'))->scheme());
  }

  #[Test]
  public function authority() {
    $this->assertEquals(new Authority('example.com', 8080), (new URI('http://example.com:8080'))->authority());
  }

  #[Test, Values(['http://example.com', 'http://example.com:8080', 'http://user:pass@example.com', 'ldap://example.com/c=GB?objectClass?one'])]
  public function domain_as_host($uri) {
    $this->assertEquals('example.com', (new URI($uri))->host());
  }

  #[Test, Values(['http://127.0.0.1', 'http://127.0.0.1:8080', 'http://user:pass@127.0.0.1', 'ldap://127.0.0.1/c=GB?objectClass?one'])]
  public function ipv4_address_as_host($uri) {
    $this->assertEquals('127.0.0.1', (new URI($uri))->host());
  }

  #[Test, Values(['http://[::1]', 'http://[::1]:8080', 'http://user:pass@[::1]', 'ldap://[::1]/c=GB?objectClass?one'])]
  public function ipv6_address_as_host($uri) {
    $this->assertEquals('[::1]', (new URI($uri))->host());
  }

  #[Test]
  public function without_port() {
    $this->assertEquals(null, (new URI('http://example.com'))->port());
  }

  #[Test]
  public function with_port() {
    $this->assertEquals(8080, (new URI('http://example.com:8080'))->port());
  }

  #[Test]
  public function without_path() {
    $this->assertEquals(null, (new URI('http://example.com'))->path());
  }

  #[Test]
  public function with_empty_path() {
    $this->assertEquals('/', (new URI('http://example.com/'))->path());
  }

  #[Test]
  public function with_path() {
    $this->assertEquals('/a/b/c', (new URI('http://example.com/a/b/c'))->path());
  }

  #[Test]
  public function raw_path() {
    $this->assertEquals('/a/b%2Fc/home+de.html', (new URI('http://example.com/a/b%2Fc/home+de.html'))->path(false));
  }

  #[Test]
  public function urlencoded_path() {
    $this->assertEquals('/a/b/c/home+de.html', (new URI('http://example.com/a/b%2Fc/home+de.html'))->path());
  }

  #[Test]
  public function file_url_path() {
    $this->assertEquals('/usr/local/etc/php.ini', (new URI('file:///usr/local/etc/php.ini'))->path());
  }

  #[Test, Values(['http://api@example.com', 'http://api:secret@example.com', 'http://api:secret@example.com:8080',])]
  public function with_user($uri) {
    $this->assertEquals('api', (new URI($uri))->user());
  }

  #[Test]
  public function without_user() {
    $this->assertEquals(null, (new URI('http://example.com'))->user());
  }

  #[Test]
  public function urlencoded_user() {
    $this->assertEquals('u:root', (new URI('http://u%3Aroot@example.com'))->user());
  }

  #[Test]
  public function plus_in_user() {
    $this->assertEquals('api+de', (new URI('http://api+de@example.com'))->user());
  }

  #[Test, Values(['http://api:secret@example.com', 'http://api:secret@example.com:8080'])]
  public function with_password($uri) {
    $this->assertEquals('secret', (new URI($uri))->password()->reveal());
  }

  #[Test]
  public function without_password() {
    $this->assertEquals(null, (new URI('http://example.com'))->password());
  }

  #[Test]
  public function urlencoded_password() {
    $this->assertEquals('p:secret', (new URI('http://u%3Aroot:p%3Asecret@example.com'))->password()->reveal());
  }

  #[Test]
  public function without_query() {
    $this->assertEquals(null, (new URI('http://example.com'))->query());
  }

  #[Test, Values(['http://example.com?a=b&c=d', 'http://example.com/?a=b&c=d', 'http://example.com/path?a=b&c=d', 'http://example.com/?a=b&c=d#', 'http://example.com/?a=b&c=d#fragment'])]
  public function with_query($uri) {
    $this->assertEquals('a=b&c=d', (new URI($uri))->query());
  }

  #[Test]
  public function urlencoded_query() {
    $this->assertEquals('a=/&c=d e', (new URI('http://example.com?a=%2F&c=d+e'))->query());
  }

  #[Test]
  public function raw_query() {
    $this->assertEquals('a=%2F&c=d+e', (new URI('http://example.com?a=%2F&c=d+e'))->query(false));
  }

  #[Test]
  public function query_for_opaque_uri() {
    $this->assertEquals('Subject=Hello World', (new URI('mailto:fred@example.com?Subject=Hello%20World'))->query());
  }

  #[Test]
  public function query_with_question_mark() {
    $this->assertEquals('objectClass?one', (new URI('ldap://[2001:db8::7]/c=GB?objectClass?one'))->query());
  }

  #[Test]
  public function without_fragment() {
    $this->assertEquals(null, (new URI('http://example.com'))->fragment());
  }

  #[Test, Values(['http://example.com#top', 'http://example.com/#top', 'http://example.com/path#top', 'http://example.com/a=b&c=d#top'])]
  public function with_fragment($uri) {
    $this->assertEquals('top', (new URI($uri))->fragment());
  }

  #[Test]
  public function urlencoded_fragment() {
    $this->assertEquals('a=/&c=d e', (new URI('http://example.com#a=%2F&c=d+e'))->fragment());
  }

  #[Test]
  public function raw_fragment() {
    $this->assertEquals('a=%2F&c=d+e', (new URI('http://example.com#a=%2F&c=d+e'))->fragment(false));
  }

  #[Test, Values(['http://example.com', 'http://example.com:80', 'http://user@example.com', 'http://user:pass@example.com', 'http://u%3Aroot:p%3Asecret@example.com', 'http://example.com?param=value', 'http://example.com/#fragment', 'http://example.com//path', 'http://example.com/path/with%2Fslashes', 'http://example.com/path?param=value&ie=utf8#fragment', 'https://example.com', 'https://msdn.microsoft.com/en-us/library/system.uri(v=vs.110).aspx', 'svn+ssh://example.com/repo/trunk', 'ms-help://section/path/file.htm', 'h323:seconix.com:1740', 'soap.beep://stockquoteserver.example.com/StockQuote', 'https://[::1]:443', 'ftp://example.com', 'file:///usr/local/etc/php.ini', 'file:///c:/php/php.ini', 'file:///c|/php/php.ini', 'tel:+1-816-555-1212', 'urn:oasis:names:specification:docbook:dtd:xml:4.1.2', 'ldap://[2001:db8::7]/c=GB?objectClass?one', 'index.html'])]
  public function string_cast_yields_input($input) {
    $this->assertEquals($input, (string)new URI($input));
  }

  #[Test]
  public function string_representation_does_not_include_password() {
    $this->assertEquals(
      'util.URI<http://user:********@example.com>',
      (new URI('http://user:pass@example.com'))->toString()
    );
  }

  #[Test]
  public function can_be_created_via_with() {
    $this->assertEquals(
      'https://example.com:443/',
      (string)URI::with()->scheme('https')->authority(new Authority('example.com', 443))->path('/')->create()
    );
  }

  #[Test]
  public function can_be_modified_via_using() {
    $this->assertEquals(
      'http://example.com?a=b',
      (string)(new URI('http://example.com'))->using()->query('a=b')->create()
    );
  }

  #[Test]
  public function compared_to_itself() {
    $uri= new URI('https://example.com/');
    $this->assertEquals(0, $uri->compareTo($uri));
  }

  #[Test, Values([['https://example.com', 1], ['https://example.com/', 0], ['https://example.com/path', -1]])]
  public function compared_to_another($uri, $expected) {
    $this->assertEquals($expected, (new URI('https://example.com/'))->compareTo(new URI($uri)));
  }

  #[Test, Values([[null], [false], [true], [0], [6100], [-1], [0.0], [1.5], [''], ['Hello'], ['https://example.com/'], [[]], [[1, 2, 3]], [['key' => 'value']]])]
  public function compared_to($value) {
    $this->assertEquals(1, (new URI('https://example.com/'))->compareTo($value));
  }

  #[Test]
  public function canonicalize() {
    $this->assertEquals(new URI('http://localhost/'), (new URI('http://localhost:80'))->canonicalize());
  }

  #[Test, Expect(FormatException::class)]
  public function empty_input() {
    new URI('');
  }

  #[Test, Expect(['class' => FormatException::class, 'withMessage' => '/Scheme .+ malformed/']), Values(['://example.com', '0://example.com', '-://example.com', '+://example.com', '.://example.com', '0http://example.com', '-http://example.com', '+http://example.com', '.http://example.com', 'http\\://example.com', 'http$://example.com', '1234://example.com', 'mailto!:test@example.com'])]
  public function malformed_scheme($arg) {
    new URI($arg);
  }

  #[Test, Expect(['class' => FormatException::class, 'withMessage' => '/Authority .+ malformed/']), Values(['http://user:', 'http://user@', 'http://user:password@', 'http://user@:8080', 'http://:8080', 'http://:foo', 'http://:123foo', 'http://:foo123', 'http://example.com:foo', 'http://example.com:123foo', 'http://example.com:foo123', 'http://example$'])]
  public function malformed_authority($arg) {
    new URI($arg);
  }

  #[Test]
  public function semantic_attack() {

    // See https://tools.ietf.org/html/rfc3986#section-7.6
    $this->assertEquals(
      'cnn.example.com&story=breaking_news',
      (new URI('ftp://cnn.example.com&story=breaking_news@10.0.0.1/top_story.htm'))->authority()->user()
    );
  }

  #[Test, Values(['http://localhost', 'http://localhost/', 'http://localhost/.'])]
  public function with_relative_part($uri) {
    $this->assertEquals(new URI('http://localhost/index.html'), (new URI($uri, 'index.html'))->canonicalize());
  }

  #[Test, Values(['http://localhost/home', 'http://localhost/home', 'http://localhost/home/.'])]
  public function with_relative_parent($uri) {
    $this->assertEquals(new URI('http://localhost/index.html'), (new URI($uri, '../index.html'))->canonicalize());
  }

  #[Test, Values(['http://localhost', 'http://localhost/', 'http://localhost/.'])]
  public function with_relative_part_including_query($uri) {
    $this->assertEquals(new URI('http://localhost/?a=b'), (new URI($uri, '?a=b'))->canonicalize());
  }

  #[Test, Values(['http://localhost', 'http://localhost/', 'http://localhost/.'])]
  public function with_relative_part_including_fragment($uri) {
    $this->assertEquals(new URI('http://localhost/#top'), (new URI($uri, '#top'))->canonicalize());
  }

  /** @return iterable */
  private function resolvables() {
    yield [new URI('/'), 'index.html', new URI('/index.html')];
    yield [new URI('/home/'), 'index.html', new URI('/home/index.html')];
    yield [new URI('file:///var/www-data/'), 'index.html', new URI('file:///var/www-data/index.html')];
    yield [new URI('http://localhost'), 'index.html', new URI('http://localhost/index.html')];
    yield [new URI('http://localhost'), '?a=b', new URI('http://localhost/?a=b')];
    yield [new URI('http://localhost'), '#top', new URI('http://localhost/#top')];
    yield [new URI('http://localhost?a=b'), '#top', new URI('http://localhost/?a=b#top')];
    yield [new URI('http://localhost'), 'index.html?a=b', new URI('http://localhost/index.html?a=b')];
    yield [new URI('http://localhost'), 'index.html#top', new URI('http://localhost/index.html#top')];
    yield [new URI('http://localhost/home.html'), 'index.html', new URI('http://localhost/index.html')];
    yield [new URI('http://localhost?c=d'), 'index.html?a=b', new URI('http://localhost/index.html?a=b')];
    yield [new URI('http://localhost?c=d'), 'index.html#top', new URI('http://localhost/index.html#top')];
    yield [new URI('http://localhost#top'), 'index.html', new URI('http://localhost/index.html')];
    yield [new URI('http://localhost/home/'), 'index.html', new URI('http://localhost/home/index.html')];
    yield [new URI('http://localhost/home'), '/index.html', new URI('http://localhost/index.html')];
    yield [new URI('http://localhost'), '//example.com', new URI('http://example.com/')];
    yield [new URI('http://localhost/home'), '//example.com', new URI('http://example.com/')];
    yield [new URI('http://localhost'), 'https://example.com', new URI('https://example.com')];
    yield [new URI('http://localhost/home'), 'https://example.com', new URI('https://example.com')];
    yield [new URI('http://localhost/ui@2.8.7/style.css'), '/index.html', new URI('http://localhost/index.html')];
    yield [new URI('http://localhost/ui@2.8.7/style.css'), 'icons.woff', new URI('http://localhost/ui@2.8.7/icons.woff')];
    yield [new URI('http://localhost/ui@2.8.7/style.css'), 'a/icons.woff', new URI('http://localhost/ui@2.8.7/a/icons.woff')];
    yield [new URI('http://localhost'), '../index.html', new URI('http://localhost/index.html')];
    yield [new URI('http://localhost/ui@2.8.7'), '../index.html', new URI('http://localhost/index.html')];
    yield [new URI('http://localhost/ui@2.8.7/style.css'), '../index.html', new URI('http://localhost/index.html')];
    yield [new URI('http://localhost'), './index.html', new URI('http://localhost/index.html')];
    yield [new URI('http://localhost/home.html'), './index.html', new URI('http://localhost/index.html')];
    yield [new URI('http://localhost/ui@2.8.7/style.css'), './icons.woff', new URI('http://localhost/ui@2.8.7/icons.woff')];
  }

  #[Test, Values('resolvables')]
  public function resolve($uri, $resolve, $result) {
    $this->assertEquals($result, $uri->resolve($resolve));
  }

  #[Test, Values([['http://localhost', []], ['http://localhost?a=b', ['a' => 'b']], ['http://localhost?a=b&c=d', ['a' => 'b', 'c' => 'd']], ['http://localhost?a[]=b&a[]=c', ['a' => ['b', 'c']]]])]
  public function params($input, $expected) {
    $this->assertEquals($expected, (new URI($input))->params()->pairs());
  }

  #[Test]
  public function param() {
    $this->assertEquals('b', (new URI('http://localhost?a=b'))->param('a'));
  }

  #[Test]
  public function non_existant_param() {
    $this->assertEquals(null, (new URI('http://localhost'))->param('a'));
  }

  #[Test]
  public function non_existant_param_with_default() {
    $this->assertEquals('default', (new URI('http://localhost'))->param('a', 'default'));
  }

  #[Test]
  public function unix_path() {
    $this->assertEquals('file:///usr/local/etc/php.ini', (string)URI::file('/usr/local/etc/php.ini'));
  }

  #[Test]
  public function windows_path() {
    $this->assertEquals('file://c:/php/php.ini', (string)URI::file('c:\\php\\php.ini'));
  }

  #[Test]
  public function windows_unc_name() {
    $this->assertEquals('file://remote', (string)URI::file('//remote'));
  }

  #[Test]
  public function windows_unc_path() {
    $this->assertEquals('file://remote/php/php.ini', (string)URI::file('\\\\remote\\php\\php.ini'));
  }

  #[Test]
  public function path_instance() {
    $this->assertEquals('file://.', (string)URI::file(new Path('.')));
  }

  #[Test, Values(['/usr/local/etc/php.ini', 'c:/php/php.ini', '//remote', '//remote/php/php.ini', '../dir/file.txt', '.'])]
  public function as_path($path) {
    $this->assertEquals(new Path($path), URI::file($path)->asPath());
  }

  #[Test, Expect(IllegalStateException::class), Values(['http://example.com', 'tel:+1-816-555-1212'])]
  public function not_representable_as_path($uri) {
    (new URI($uri))->asPath();
  }
}