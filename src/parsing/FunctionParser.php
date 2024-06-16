<?php
declare(strict_types = 1);
namespace TimoLehnertz\formula\parsing;

use TimoLehnertz\formula\statement\FunctionStatement;
use TimoLehnertz\formula\tokens\Token;
use TimoLehnertz\formula\type\functions\FunctionType;
use TimoLehnertz\formula\type\functions\InnerFunctionArgument;
use TimoLehnertz\formula\type\functions\InnerFunctionArgumentList;
use TimoLehnertz\formula\type\functions\InnerVargFunctionArgument;
use TimoLehnertz\formula\type\functions\FunctionValue;
use TimoLehnertz\formula\expression\ConstantExpression;
use TimoLehnertz\formula\expression\FunctionExpression;
use TimoLehnertz\formula\type\functions\FormulaFunctionBody;

/**
 * @author Timo Lehnertz
 */
class FunctionParser extends Parser {

  private readonly bool $parseStatement;

  public function __construct(bool $parseStatement) {
    if($parseStatement) {
      parent::__construct('function statement');
    } else {
      parent::__construct('function value');
    }
    $this->parseStatement = $parseStatement;
  }

  protected function parsePart(Token $firstToken): ParserReturn {
    $parsedReturnType = (new TypeParser(false))->parse($firstToken);
    $token = $parsedReturnType->nextToken;
    if($token === null) {
      throw new ParsingSkippedException();
    }
    if($this->parseStatement) {
      if($token->id !== Token::IDENTIFIER) {
        throw new ParsingSkippedException();
      }
      $identifier = $token->value;
      $token = $token->next();
      if($token === null) {
        throw new ParsingSkippedException();
      }
    }
    $functionArgumentParser = new EnumeratedParser('Function arguments', new FunctionArgumentParser(), Token::BRACKETS_OPEN, Token::COMMA, Token::BRACKETS_CLOSED, false, true);
    $parsedArguments = $functionArgumentParser->parse($token);
    $token = $parsedArguments->nextToken;
    $normalArgs = [];
    $vArg = null;
    for($i = 0;$i < count($parsedArguments->parsed);$i++) {
      $arg = $parsedArguments->parsed[$i];
      if($arg instanceof InnerFunctionArgument) {
        $normalArgs[] = $arg;
      } else if($arg instanceof InnerVargFunctionArgument) {
        $vArg = $arg;
        if($i !== count($parsedArguments->parsed) - 1) {
          throw new ParsingException(ParsingException::PARSING_ERROR_VARG_NOT_LAST);
        }
      }
    }
    $parsedCodeBlock = (new CodeBlockParser(false, false))->parse($token);
    $innerArgs = new InnerFunctionArgumentList($normalArgs, $vArg);
    if($this->parseStatement) {
      $parsed = new FunctionStatement($parsedReturnType->parsed, $identifier, $innerArgs, $parsedCodeBlock->parsed);
    } else {
      $parsed = new FunctionExpression($parsedReturnType->parsed, $innerArgs, $parsedCodeBlock->parsed);
    }
    return new ParserReturn($parsed, $parsedCodeBlock->nextToken);
  }
}