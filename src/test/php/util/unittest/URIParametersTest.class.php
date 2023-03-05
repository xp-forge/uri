<?php namespace util\unittest;

use lang\FormatException;
use test\{Assert, Expect, Test, Values};
use util\uri\Parameters;

class URIParametersTest {

  /** @return iterable */
  private function fixtures() {
    yield ['', []];
    yield ['a=b', ['a' => 'b']];
    yield ['ue=%C3%BC', ['ue' => 'ü']];
    yield ['%C3%BC=ue', ['ü' => 'ue']];
    yield ['a=b&c=d', ['a' => 'b', 'c' => 'd']];
    yield ['a.b=c', ['a.b' => 'c']];
    yield ['a_b=c', ['a_b' => 'c']];
    yield ['a-b=c', ['a-b' => 'c']];
    yield ['a[]=1', ['a' => ['1']]];
    yield ['a[]=1+2', ['a' => ['1 2']]];
    yield ['a[]=1&a[]=2', ['a' => ['1', '2']]];
    yield ['a[b]=c', ['a' => ['b' => 'c']]];
    yield ['a[%C3%BC]=ue', ['a' => ['ü' => 'ue']]];
    yield ['a[b]=c&a[c]=d', ['a' => ['b' => 'c', 'c' => 'd']]];
    yield ['a[b][c]=d', ['a' => ['b' => ['c' => 'd']]]];
    yield ['a[b][c]=d&a[b][e]=f', ['a' => ['b' => ['c' => 'd', 'e' => 'f']]]];
  }

  #[Test, Values(from: 'fixtures')]
  public function size($input, $expected) {
    Assert::equals(sizeof($expected), (new Parameters($input))->size());
  }

  #[Test, Values(from: 'fixtures')]
  public function pairs($input, $expected) {
    Assert::equals($expected, (new Parameters($input))->pairs());
  }

  #[Test, Values(from: 'fixtures')]
  public function iterable($input, $expected) {
    Assert::equals($expected, iterator_to_array(new Parameters($input)));
  }

  #[Test, Values(['a=', 'a'])]
  public function empty_scalar($input) {
    Assert::equals(['a' => ''], (new Parameters($input))->pairs());
  }

  #[Test, Values(['a[]=', 'a[]'])]
  public function empty_array($input) {
    Assert::equals(['a' => ['']], (new Parameters($input))->pairs());
  }

  #[Test]
  public function lowercase_escape_sequences() {
    Assert::equals(['ue' => 'ü'], (new Parameters('ue=%c3%bc'))->pairs());
  }

  #[Test, Values(['a=b&&c=d', 'a=b&=&c=d'])]
  public function empty_pair_ignored($input) {
    Assert::equals(['a' => 'b', 'c' => 'd'], (new Parameters($input))->pairs());
  }

  #[Test]
  public function named() {
    Assert::equals('b', (new Parameters('a=b'))->named('a'));
  }

  #[Test]
  public function non_existant_named() {
    Assert::equals(null, (new Parameters('a=b'))->named('c'));
  }

  #[Test]
  public function non_existant_named_with_default() {
    Assert::equals('default', (new Parameters('a=b'))->named('c', 'default'));
  }

  #[Test]
  public function can_be_iterated() {
    Assert::equals(
      ['a' => 'b', 'c' => ['1', '2']],
      iterator_to_array(new Parameters('a=b&c[]=1&c[]=2'))
    );
  }

  #[Test, Values(from: 'fixtures')]
  public function string_representation_equals_input($input) {
    Assert::equals($input, (string)new Parameters($input));
  }

  #[Test, Values(['a', 'a=', 'a[]', 'a[]='])]
  public function empty_parameters_output_without_equals($input) {
    Assert::equals(rtrim($input, '='), (string)new Parameters($input));
  }

  #[Test, Values([['a=b', '', 1], ['a=b&c=d', 'a=b', 1], ['a=b', 'a=b', 0], ['a=b&c=d', 'a=b&c=d', 0], ['c=d&a=b', 'a=b&c=d', 0], ['', 'a=b', -1], ['a=b', 'a=b&c=d', -1]])]
  public function compare($lhs, $rhs, $expected) {
    Assert::equals($expected, (new Parameters($lhs))->compareTo(new Parameters($rhs)));
  }

  #[Test, Expect(FormatException::class), Values(['[', ']', '[][', '[]]', 'a[=b', 'a[[]=b', 'a]=b', 'a[]]=b', 'a[b][=c', 'a[b][[=c'])]
  public function unbalanced_brackets($input) {
    new Parameters($input);
  }

  #[Test, Expect(FormatException::class)]
  public function deeply_nested_array() {
    new Parameters('a'.str_repeat('[]', (ini_get('max_input_nesting_level') ?: 64) + 1));
  }
}