<?php
declare(strict_types = 1);
namespace TimoLehnertz\formula\operator;

use TimoLehnertz\formula\PrettyPrintOptions;
use TimoLehnertz\formula\expression\ArgumentListExpression;
use TimoLehnertz\formula\expression\ComplexOperatorExpression;
use TimoLehnertz\formula\expression\Expression;
use TimoLehnertz\formula\expression\OperatorExpression;
use TimoLehnertz\formula\nodes\Node;
use TimoLehnertz\formula\procedure\Scope;

/**
 * @author Timo Lehnertz
 */
class CallOperator implements ParsedOperator {

  private ArgumentListExpression $arguments;

  public function __construct(ArgumentListExpression $arguments) {
    $this->arguments = $arguments;
  }

  public function getPrecedence(): int {
    return 2;
  }

  public function getOperatorType(): OperatorType {
    return OperatorType::PostfixOperator;
  }

  public function transform(?Expression $leftExpression, ?Expression $rightExpression): Expression {
    $callOperator = new ImplementableOperator(ImplementableOperator::TYPE_CALL);
    return new ComplexOperatorExpression($leftExpression, $callOperator, $this->arguments, $leftExpression, $this, null);
  }

  public function toString(PrettyPrintOptions $prettyPrintOptions): string {
    return $this->arguments->toString($prettyPrintOptions);
  }

  public function buildNode(Scope $scope, ?Expression $leftExpression, ?Expression $rightExpression): Node {
    return (new OperatorExpression($leftExpression, new ImplementableOperator(ImplementableOperator::TYPE_CALL), $this->arguments))->buildNode($scope);
  }
}
