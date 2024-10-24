<?php
namespace test\tokens;

use PHPUnit\Framework\TestCase;
use TimoLehnertz\formula\tokens\Tokenizer;
use TimoLehnertz\formula\tokens\Token;
use TimoLehnertz\formula\tokens\TokenisationException;

class TokenizerTest extends TestCase {

  // @formatter:off
  public static function provideTokens(): array {
    return [
      [Token::KEYWORD_INT, 'int ', 'int'],
      [Token::KEYWORD_FLOAT, 'float ', 'float'],
      [Token::KEYWORD_STRING, 'String ', 'String'],
      [Token::KEYWORD_BOOL, 'boolean ', 'boolean'],
      [Token::KEYWORD_CHAR, 'char ', 'char'],
      [Token::KEYWORD_NEW, 'new ', 'new'],
      [Token::KEYWORD_RETURN, 'return ', 'return'],
      [Token::KEYWORD_CONTINUE, 'continue ', 'continue'],
      [Token::KEYWORD_BREAK, 'break ', 'break'],
      [Token::KEYWORD_TRUE, 'true ', 'true'],
      [Token::KEYWORD_FALSE, 'false ', 'false'],
      [Token::KEYWORD_VOID, 'void ', 'void'],
      [Token::KEYWORD_NULL, 'null ', 'null'],
      [Token::KEYWORD_IF, 'if ', 'if'],
      [Token::KEYWORD_WHILE, 'while ', 'while'],
      [Token::KEYWORD_DO, 'do ', 'do'],
      [Token::KEYWORD_FOR, 'for ', 'for'],
      [Token::KEYWORD_INSTANCEOF, 'instanceof', 'instanceof'],
      [Token::KEYWORD_TYPE, 'Type', 'Type'],
      [Token::COlON, ':', ':'],
      [Token::QUESTIONMARK, '?', '?'],
      [Token::LOGICAL_AND, '&&', '&&'],
      [Token::LOGICAL_OR, '||', '||'],
      [Token::LOGICAL_XOR, '^', '^'],
      [Token::EXCLAMATION_MARK, '!', '!'],
      [Token::COMPARISON_EQUALS, '==', '=='],
      [Token::COMPARISON_NOT_EQUALS, '!=', '!='],
      [Token::COMPARISON_SMALLER, '<', '<'],
      [Token::COMPARISON_SMALLER_EQUALS, '<=', '<='],
      [Token::COMPARISON_GREATER, '>', '>'],
      [Token::COMPARISON_GREATER_EQUALS, '>=', '>='],
      [Token::ASSIGNMENT, '=', '='],
      [Token::ASSIGNMENT_PLUS, '+=', '+='],
      [Token::ASSIGNMENT_MINUS, '-=', '-='],
      [Token::ASSIGNMENT_MULTIPLY, '*=', '*='],
      [Token::ASSIGNMENT_DIVIDE, '/=', '/='],
      [Token::ASSIGNMENT_AND, '&=', '&='],
      [Token::ASSIGNMENT_OR, '|=', '|='],
      [Token::ASSIGNMENT_XOR, '^=', '^='],
      [Token::INCREMENT, '++', '++'],
      [Token::DECREMENT, '--', '--'],
      [Token::PLUS, '+', '+'],
      [Token::MINUS, '-', '-'],
      [Token::MULTIPLY, '*', '*'],
      [Token::DIVIDE, '/', '/'],
      [Token::INT_CONSTANT, '123', '123'],
      [Token::FLOAT_CONSTANT, '1.23', '1.23'],
      [Token::NULLISH, '??', '??'],
      [Token::LINE_COMMENT, "// abcd\n abc", "// abcd"],
      [Token::MULTI_LINE_COMMENT, "/* abcd\nefg */ abc", "/* abcd\nefg */"],
      [Token::CURLY_BRACKETS_OPEN, '{', '{'],
      [Token::CURLY_BRACKETS_CLOSED, '}', '}'],
      [Token::SQUARE_BRACKETS_OPEN, '[', '['],
      [Token::SQUARE_BRACKETS_CLOSED, ']', ']'],
      [Token::BRACKETS_OPEN, '(', '('],
      [Token::BRACKETS_CLOSED, ')', ')'],
      [Token::COMMA, ',', ','],
      [Token::SEMICOLON, ';', ';'],
      [Token::SCOPE_RESOLUTION, '::', '::'],
      [Token::INTL_BACKSLASH, '|', '|'],
      [Token::SPREAD, '...', '...'],
      [Token::DOT, '.', '.'],
      [Token::STRING_CONSTANT, '"ABC123!"', 'ABC123!'],
      [Token::IDENTIFIER, 'abc', 'abc'],
      [Token::MODULO, '% ', '%'],
      [Token::KEYWORD_ELSE, 'else ', 'else'],
      [Token::KEYWORD_FINAL, 'final ', 'final'],
      [Token::KEYWORD_VAR, 'var ', 'var'],
      [Token::KEYWORD_DATE_INTERVAL, 'DateInterval', 'DateInterval'],
      [Token::KEYWORD_DATE_TIME_IMMUTABLE, 'DateTimeImmutable ', 'DateTimeImmutable'],
      [Token::FUNCTION_ARROW, '-> ', '->'],
      [Token::KEYWORD_FUNCTION, 'function', 'function'],
      [Token::DATE_TIME, '"2024-07-31"', '2024-07-31'],
      [Token::DATE_TIME, '"2024-07-31T14:20"', '2024-07-31T14:20'],
      [Token::DATE_TIME, '"2024-07-31T14:20:30"', '2024-07-31T14:20:30'],
      [Token::DATE_TIME, '"2024-07-31T14:20:30+02:00"', '2024-07-31T14:20:30+02:00'],
      [Token::DATE_TIME, '"2024-07-31T14:20:30-04:00"', '2024-07-31T14:20:30-04:00'],
      [Token::DATE_TIME, '"2024-07-31T14:20:30Z"', '2024-07-31T14:20:30Z'],
      [Token::DATE_TIME, '"2024-07-31T14:20:30.123Z"', '2024-07-31T14:20:30.123Z'],
      [Token::DATE_TIME, '"2024-07-31 14:20:30"', '2024-07-31 14:20:30'],
      [Token::DATE_INTERVAL, '"P1Y2M3DT4H5M6S"', 'P1Y2M3DT4H5M6S'],
      [Token::DATE_INTERVAL, '"P5D"', 'P5D'],
      [Token::DATE_INTERVAL, '"PT3H15M"', 'PT3H15M'],
      [Token::DATE_INTERVAL, '"P2Y6M"', 'P2Y6M'],
      [Token::DATE_INTERVAL, '"PT45S"', 'PT45S'],
      [Token::DATE_INTERVAL, '"P0D"', 'P0D'],
      [Token::KEYWORD_MIXED, 'mixed', 'mixed'],
    ];
  }
  // @formatter:on

  /**
   * @dataProvider provideTokens
   */
  public function testTokens(int $id, string $src, string $expected): void {
    $tokenized = Tokenizer::tokenize($src);
    $this->assertEquals($id, $tokenized->id);
    $this->assertEquals(0, $tokenized->line);
    $this->assertEquals(0, $tokenized->position);
    $this->assertEquals($expected, $tokenized->value);
  }

  public function testLineComments(): void {
    $tokenized = Tokenizer::tokenize("// abc\n// abc");
    $this->assertNull($tokenized->skipComment());
    $this->assertEquals('// abc', $tokenized->value);
    $this->assertEquals(Token::LINE_COMMENT, $tokenized->id);
    $this->assertEquals(Token::LINE_COMMENT, $tokenized->next(true)->id);
  }

  public function testUnexpectedEndOfInput(): void {
    $this->expectException(TokenisationException::class);
    $this->expectExceptionMessage('Unexpected end of input');
    Tokenizer::tokenize("'I am an incomplete string");
  }

  public function testNumberWithTwoDots(): void {
    $this->expectException(TokenisationException::class);
    $this->expectExceptionMessage('Number cant have two dots');
    Tokenizer::tokenize("1.2.3");
  }

  public function testNumberEndsWithDot(): void {
    $this->expectException(TokenisationException::class);
    $this->expectExceptionMessage('Incomplete number');
    Tokenizer::tokenize("1.");
  }
}

