<?php
namespace TimoLehnertz\formula\parsing;

use TimoLehnertz\formula\ParsingException;
use TimoLehnertz\formula\operator\ArrayAccessOperator;
use TimoLehnertz\formula\tokens\Token;

/**
 * Array operator Syntax
 * '['<Expression>']'
 *
 * @author Timo Lehnertz
 */
class ArrayOperatorParser extends Parser {

  protected function parsePart(Token $firstToken): ParserReturn|int {
    if($firstToken->id != Token::SQUARE_BRACKETS_OPEN) {
      return ParsingException::PARSING_ERROR_GENERIC;
    }
    if(!$firstToken->hasNext()) {
      return ParsingException::PARSING_ERROR_UNEXPECTED_END_OF_INPUT;
    }
    $token = $firstToken->next();
    $parsedIndexExpression = (new ExpressionParser())->parse($token);
    if(is_int($parsedIndexExpression)) {
      return $parsedIndexExpression;
    }
    $token = $parsedIndexExpression->nextToken;
    if($token === null) {
      return ParsingException::PARSING_ERROR_GENERIC;
    }
    if($token->id !== Token::SQUARE_BRACKETS_CLOSED) {
      return ParsingException::PARSING_ERROR_GENERIC;
    }
    return new ParserReturn(new ArrayAccessOperator($parsedIndexExpression->parsed, $token->next()));
  }
}
