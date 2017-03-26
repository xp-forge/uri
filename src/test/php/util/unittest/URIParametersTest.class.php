<?php namespace util\unittest;

use util\URIParameters;
use lang\FormatException;

class URIParametersTest extends \unittest\TestCase {

  /** @return iterable */
  private function fixtures() {
    yield ['', []];
    yield ['a=b', ['a' => 'b']];
    yield ['ue=%C3%BC', ['ue' => 'ü']];
    yield ['%C3%BC=ue', ['ü' => 'ue']];
    yield ['a=b&c=d', ['a' => 'b', 'c' => 'd']];
    yield ['a.b=c', ['a.b' => 'c']];
    yield ['a[]=1', ['a' => ['1']]];
    yield ['a[]=1+2', ['a' => ['1 2']]];
    yield ['a[]=1&a[]=2', ['a' => ['1', '2']]];
    yield ['a[b]=c', ['a' => ['b' => 'c']]];
    yield ['a[%C3%BC]=ue', ['a' => ['ü' => 'ue']]];
    yield ['a[b]=c&a[c]=d', ['a' => ['b' => 'c', 'c' => 'd']]];
    yield ['a[b][c]=d', ['a' => ['b' => ['c' => 'd']]]];
    yield ['a[b][c]=d&a[b][e]=f', ['a' => ['b' => ['c' => 'd', 'e' => 'f']]]];
  }

  #[@test, @values('fixtures')]
  public function size($input, $expected) {
    $this->assertEquals(sizeof($expected), (new URIParameters($input))->size());
  }

  #[@test, @values('fixtures')]
  public function pairs($input, $expected) {
    $this->assertEquals($expected, (new URIParameters($input))->pairs());
  }

  #[@test, @values(['a=', 'a'])]
  public function empty_scalar($input) {
    $this->assertEquals(['a' => ''], (new URIParameters($input))->pairs());
  }

  #[@test, @values(['a[]=', 'a[]'])]
  public function empty_array($input) {
    $this->assertEquals(['a' => ['']], (new URIParameters($input))->pairs());
  }

  #[@test]
  public function lowercase_escape_sequences() {
    $this->assertEquals(['ue' => 'ü'], (new URIParameters('ue=%c3%bc'))->pairs());
  }

  #[@test, @values(['a=b&&c=d', 'a=b&=&c=d'])]
  public function empty_pair_ignored($input) {
    $this->assertEquals(['a' => 'b', 'c' => 'd'], (new URIParameters($input))->pairs());
  }

  #[@test]
  public function named() {
    $this->assertEquals('b', (new URIParameters('a=b'))->named('a'));
  }

  #[@test]
  public function non_existant_named() {
    $this->assertEquals(null, (new URIParameters('a=b'))->named('c'));
  }

  #[@test]
  public function non_existant_named_with_default() {
    $this->assertEquals('default', (new URIParameters('a=b'))->named('c', 'default'));
  }

  #[@test]
  public function can_be_iterated() {
    $this->assertEquals(
      ['a' => 'b', 'c' => ['1', '2']],
      iterator_to_array(new URIParameters('a=b&c[]=1&c[]=2'))
    );
  }

  #[@test, @values('fixtures')]
  public function string_representation_equals_input($input) {
    $this->assertEquals($input, (string)new URIParameters($input));
  }

  #[@test, @values(['a', 'a=', 'a[]', 'a[]='])]
  public function empty_parameters_output_without_equals($input) {
    $this->assertEquals(rtrim($input, '='), (string)new URIParameters($input));
  }

  #[@test, @values([
  #  ['a=b', '', 1],
  #  ['a=b&c=d', 'a=b', 1],
  #  ['a=b', 'a=b', 0],
  #  ['a=b&c=d', 'a=b&c=d', 0],
  #  ['c=d&a=b', 'a=b&c=d', 0],
  #  ['', 'a=b', -1],
  #  ['a=b', 'a=b&c=d', -1]
  #])]
  public function compare($lhs, $rhs, $expected) {
    $this->assertEquals($expected, (new URIParameters($lhs))->compareTo(new URIParameters($rhs)));
  }

  #[@test, @expect(FormatException::class), @values([
  #  '[',
  #  ']',
  #  '[][',
  #  '[]]',
  #  'a[=b',
  #  'a[[]=b',
  #  'a]=b',
  #  'a[]]=b',
  #  'a[b][=c',
  #  'a[b][[=c'
  #])]
  public function unbalanced_brackets($input) {
    new URIParameters($input);
  }

  #[@test, @expect(FormatException::class)]
  public function deeply_nested_array() {
    new URIParameters('a'.str_repeat('[]', (ini_get('max_input_nesting_level') ?: 64) + 1));
  }
}