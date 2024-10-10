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
class ArrayAccessOperator implements ParsedOperator {

  private Expression $indexExpression;

  public function __construct(Expression $indexExpression) {
    $this->indexExpression = $indexExpression;
  }

  public function getPrecedence(): int {
    return 2;
  }

  public function getOperatorType(): OperatorType {
    return OperatorType::PostfixOperator;
  }

  public function transform(?Expression $leftExpression, ?Expression $rightExpression): Expression {
    $arrayAccessOperator = new ImplementableOperator(ImplementableOperator::TYPE_ARRAY_ACCESS);
    return new ComplexOperatorExpression($leftExpression, $arrayAccessOperator, $this->indexExpression, $leftExpression, $this, $rightExpression);
  }

  public function toString(PrettyPrintOptions $prettyPrintOptions): string {
    return '['.$this->indexExpression->toString($prettyPrintOptions).']';
  }

  public function buildNode(Scope $scope, ?Expression $leftExpression, ?Expression $rightExpression): Node {
    return (new OperatorExpression($leftExpression, new ImplementableOperator(ImplementableOperator::TYPE_ARRAY_ACCESS), $this->indexExpression))->buildNode($scope);
  }
}
