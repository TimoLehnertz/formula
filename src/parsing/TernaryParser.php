<?php
declare(strict_types = 1);
namespace TimoLehnertz\formula\parsing;

use TimoLehnertz\formula\expression\Expression;
use TimoLehnertz\formula\expression\TernaryExpression;
use TimoLehnertz\formula\tokens\Token;

/**
 * @author Timo Lehnertz
 */
class TernaryParser extends Parser {

  private readonly Expression $condition;

  public function __construct(Expression $condition) {
    parent::__construct('ternary');
    $this->condition = $condition;
  }

  protected function parsePart(Token $firstToken): ParserReturn {
    if($firstToken->id !== Token::QUESTIONMARK) {
      throw new ParsingException(ParsingException::ERROR_UNEXPECTED_TOKEN, $firstToken, 'Expected ?');
    }
    $token = $firstToken->requireNext();
    $parsedLeftExpression = (new ExpressionParser(false))->parse($token, true);
    $token = $parsedLeftExpression->nextToken;
    if($token === null) {
      throw new ParsingException(ParsingException::ERROR_UNEXPECTED_END_OF_INPUT);
    }
    if($token->id !== Token::COlON) {
      throw new ParsingException(ParsingException::ERROR_UNEXPECTED_TOKEN, $token, 'Expected :');
    }
    $token = $token->requireNext();
    $parsedRightExpression = (new ExpressionParser(false))->parse($token, true);
    $ternaryExpression = new TernaryExpression($this->condition, $parsedLeftExpression->parsed, $parsedRightExpression->parsed);
    return new ParserReturn($ternaryExpression, $parsedRightExpression->nextToken);
  }
}
