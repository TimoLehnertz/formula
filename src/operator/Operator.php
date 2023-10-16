<?php
namespace TimoLehnertz\formula\operator;

use TimoLehnertz\formula\SubFormula;
use TimoLehnertz\formula\expression\Expression;
use TimoLehnertz\formula\expression\NoExpression;
use InvalidArgumentException;

/**
 *
 * @author Timo Lehnertz
 *
 */
abstract class Operator implements SubFormula {

  /**
   * precedence of this operator over other operators, lower is higher priority
   * source https://en.cppreference.com/w/cpp/language/operator_precedence
   * @readonly
   */
  private int $precedence;

  /**
   * Can left and right be interchanged
   * @readonly
   */
  private bool $commutative;

  /**
   * Is lefthand expression required
   * @readonly
   */
  private bool $needsLeft;

  /**
   * Is righthand expression required
   * @readonly
   */
  private bool $needsRight;

  /**
   * Will use lefthand expression if available
   * @readonly
   */
  private bool $usesLeft;

  /**
   * Will use righthand expression if available
   * @readonly
   */
  private bool $usesRight;

  private string $stringRepresentation;

  public function __construct(string $stringRepresentation, int $precedence, bool $commutative, bool $needsLeft = true, bool $needsRight = true, bool $usesLeft = true, bool $usesRight = true) {
    $this->precedence = $precedence;
    $this->commutative = $commutative;
    $this->needsLeft = $needsLeft;
    $this->needsRight = $needsRight;
    $this->usesLeft = $usesLeft;
    $this->usesRight = $usesRight;
    $this->stringRepresentation = $stringRepresentation;
  }

  public function getPrecedence(): int {
    return $this->precedence;
  }

  public function needsLeft(): bool {
    return $this->needsLeft;
  }

  public function needsRight(): bool {
    return $this->needsRight;
  }

  public function usesLeft(): bool {
    return $this->usesLeft;
  }

  public function usesRight(): bool {
    return $this->usesRight;
  }

  /**
   * @throws InvalidArgumentException
   */
  public function calculate(Expression $left, Expression $right): Calculateable {
    try {
      return $this->doCalculate($left->calculate(), $right->calculate());
    } catch(InvalidArgumentException $e) {
      if($this->commutative) { // try other direction
        return $this->doCalculate($right->calculate(), $left->calculate());
      } else {
        throw $e;
      }
    }
  }

  public static function fromString(string $name): Operator {
    switch($name) {
      case "+":   return new Increment();
      case "-":   return new Subtraction();
      case "*":   return new Multiplication();
      case "/":   return new Division();
      case "^":   return new XorOperator();
      case "&&":  return new AndOperator();
      case "||":  return new OrOperator();
      case "!=":  return new NotEqualsOperator();
      case "!":   return new NotOperator();
      case "==":  return new EqualsOperator();
      case "<":  return new SmallerOperator();
      case ">":  return new GreaterOperator();
      case "<=":  return new SmallerEqualsOperator();
      case "<":   return new SmallerOperator();
      case ">=":  return new GreaterEqualsOperator();
      case "<":   return new GreaterOperator();
      default: throw new \Exception("Invalid operator: $name"); // shouldnt happen as this gets sorted out in tokenizer stage
    }
  }

  /**
   * @param Expression|array|null $left
   * @param Expression|array|null $right
   * @return array node
   */
  public function getNode($left, $right): array {
    $leftNode = $left;
    $rightNode = $right;
    if($left !== null && ($left instanceof Expression) && !($left instanceof NoExpression)) {
      $leftNode = $left->getNode();
    }
    if($right !== null && ($right instanceof Expression) && !($right instanceof NoExpression)) {
      $rightNode = $right->getNode();
    }
    return [
      'type' => 'operator',
      'operator' => $this->stringRepresentation,
      'left' => $leftNode,
      'right' => $rightNode,
      'commutative' => $this->commutative,
      'needsLeft' => $this->needsLeft,
      'needsRight' => $this->needsRight,
      'usesLeft' => $this->usesLeft,
      'usesRight' => $this->usesRight
    ];
  }

  public abstract function doCalculate(Calculateable $left, Calculateable $right): Calculateable;

  public function toString(): string {
    return $this->stringRepresentation;
  }
}