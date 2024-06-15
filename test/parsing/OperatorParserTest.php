<?php
namespace test\parsing;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Constraint\Operator;
use TimoLehnertz\formula\PrettyPrintOptions;
use TimoLehnertz\formula\operator\OperatorType;
use TimoLehnertz\formula\parsing\OperatorParser;
use TimoLehnertz\formula\parsing\ParsingException;
use TimoLehnertz\formula\tokens\Tokenizer;
use TimoLehnertz\formula\operator\ParsedOperator;

class OperatorParserTest extends TestCase {

  public function provideOperators(): array {
    // @formatter:off
    $dataset = [
      ["a::b", '::', 1, OperatorType::InfixOperator],
      ['(int[]|bool)', '(int[]|bool)', 0, OperatorType::PrefixOperator],
      ['a(a,b,c)', '(a,b,c)', 1, OperatorType::PostfixOperator],
      ['[c]', '[c]', 0, OperatorType::PostfixOperator],
      ['a.b', '.', 1, OperatorType::InfixOperator],
      ['a++', '++', 1, OperatorType::PostfixOperator],
      ['a--', '--', 1, OperatorType::PostfixOperator],
      ['++a', '++', 0, OperatorType::PrefixOperator],
      ['--a', '--', 0, OperatorType::PrefixOperator],
      ['a+b', '+', 1, OperatorType::InfixOperator],
      ['a-b', '-', 1, OperatorType::InfixOperator],
      ['+b', '+', 0, OperatorType::PrefixOperator],
      ['(+b)', '+', 1, OperatorType::PrefixOperator],
      ['-b', '-', 0, OperatorType::PrefixOperator],
      ['(-b)', '-', 1, OperatorType::PrefixOperator],
      ['!', '!', 0, OperatorType::PrefixOperator],
      ['a*b', '*', 1, OperatorType::InfixOperator],
      ['a/b', '/', 1, OperatorType::InfixOperator],
      ['a%b', '%', 1, OperatorType::InfixOperator],
      ['a<b', '<', 1, OperatorType::InfixOperator],
      ['a>b', '>', 1, OperatorType::InfixOperator],
      ['a<=b', '<=', 1, OperatorType::InfixOperator],
      ['a>=b', '>=', 1, OperatorType::InfixOperator],
      ['a==b', '==', 1, OperatorType::InfixOperator],
      ['a!=b', '!=', 1, OperatorType::InfixOperator],
      ['a&&b', '&&', 1, OperatorType::InfixOperator],
      ['a||b', '||', 1, OperatorType::InfixOperator],
      ['a^b', '^', 1, OperatorType::InfixOperator],
      ['a=b', '=', 1, OperatorType::InfixOperator],
      ['a&=b', '&=', 1, OperatorType::InfixOperator],
      ['a/=b', '/=', 1, OperatorType::InfixOperator],
      ['a-=b', '-=', 1, OperatorType::InfixOperator],
      ['a*=b', '*=', 1, OperatorType::InfixOperator],
      ['a|=b', '|=', 1, OperatorType::InfixOperator],
      ['a+=b', '+=', 1, OperatorType::InfixOperator],
      ['a^=b', '^=', 1, OperatorType::InfixOperator],
      ['a instanceof b', 'instanceof', 1, OperatorType::InfixOperator],
    ];
  // @formatter:on
    return $dataset;
  }

  /**
   * @dataProvider provideOperators
   */
  public function test(string $source, string $expectedOperator, int $startToken, OperatorType $operatorType): void {
    $token = Tokenizer::tokenize($source);
    while($startToken-- > 0) {
      $token = $token->next();
    }
    $parser = new OperatorParser();
    $parsed = $parser->parse($token);
    if(is_int($parsed)) {
      throw new ParsingException($this, $parsed, $token);
    }
    $this->assertInstanceOf(ParsedOperator::class, $parsed->parsed);
    $this->assertEquals($expectedOperator, $parsed->parsed->toString(PrettyPrintOptions::buildDefault()));
    $this->assertEquals($operatorType, $parsed->parsed->getOperatorType());
  }
}

