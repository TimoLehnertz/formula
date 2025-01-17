<?php
declare(strict_types = 1);
namespace TimoLehnertz\formula\operator;

use TimoLehnertz\formula\PrettyPrintOptions;
use TimoLehnertz\formula\expression\ComplexOperatorExpression;
use TimoLehnertz\formula\expression\Expression;
use TimoLehnertz\formula\expression\OperatorExpression;
use TimoLehnertz\formula\nodes\Node;
use TimoLehnertz\formula\procedure\Scope;

/**
 * @author Timo Lehnertz
 */
class GreaterEqualsOperator implements ParsedOperator {

  public function __construct() {}

  public function transform(?Expression $leftExpression, ?Expression $rightExpression): Expression {
    $comparisonOperator = new ImplementableOperator(ImplementableOperator::TYPE_EQUALS);
    $comparisonExpression = new OperatorExpression($leftExpression, $comparisonOperator, $rightExpression);
    $greaterOperator = new ImplementableOperator(ImplementableOperator::TYPE_GREATER);
    $lessExpression = new OperatorExpression($leftExpression, $greaterOperator, $rightExpression);
    $orOperator = new ImplementableOperator(ImplementableOperator::TYPE_LOGICAL_OR);
    return new ComplexOperatorExpression($comparisonExpression, $orOperator, $lessExpression, $leftExpression, $this, $rightExpression);
  }

  public function getOperatorType(): OperatorType {
    return OperatorType::InfixOperator;
  }

  public function getPrecedence(): int {
    return 9;
  }

  public function toString(PrettyPrintOptions $prettyPrintOptions): string {
    return '>=';
  }

  public function buildNode(Scope $scope, ?Expression $leftExpression, ?Expression $rightExpression): Node {
    return new Node('greaterEquals', [$leftExpression->buildNode($scope), $rightExpression->buildNode($scope)]);
  }
}