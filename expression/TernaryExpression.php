<?php
namespace socialistFork\formula\expression;

use socialistFork\formula\Nestable;
use socialistFork\formula\operator\Calculateable;

/**
 *
 * @author timo
 *        
 */
class TernaryExpression implements Expression, Nestable {
  
  /**
   * Condition
   * @var Expression|null
   */
  public ?MathExpression $condition = null;
  
  /**
   * Left expression
   * @var Expression|null
   */
  public ?MathExpression $leftExpression = null;
  
  /**
   * Right expression
   * @var Expression|null
   */
  public ?MathExpression $rightExpression = null;
  
  public function calculate(): Calculateable {
    return $this->condition->calculate()->isTruthy() ? $this->leftExpression->calculate() : $this->rightExpression->calculate();
  }
  
  public function setMethod(string $identifier, callable $method): void {
    $this->condition->setMethod($identifier, $method);
    $this->leftExpression->setMethod($identifier, $method);
    $this->rightExpression->setMethod($identifier, $method);
  }
  
  public function setVariable(string $identifier, $value): void {
    $this->condition->setVariable($identifier, $value);
    $this->leftExpression->setVariable($identifier, $value);
    $this->rightExpression->setVariable($identifier, $value);
  }
  
  public function validate(bool $throwOnError): bool {
    if(!$this->condition->validate($throwOnError)) return false;
    if(!$this->leftExpression->validate($throwOnError)) return false;
    if(!$this->rightExpression->validate($throwOnError)) return false;
    return true;
  }
}

