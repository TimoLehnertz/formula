<?php
declare(strict_types = 1);
namespace TimoLehnertz\formula\operator;

use TimoLehnertz\formula\FormulaPart;
use TimoLehnertz\formula\expression\Expression;
use TimoLehnertz\formula\nodes\Node;
use TimoLehnertz\formula\procedure\Scope;

/**
 * @author Timo Lehnertz
 */
interface ParsedOperator extends FormulaPart {

  public function getPrecedence(): int;

  public function getOperatorType(): OperatorType;

  /**
   * Transform this operator into an expression
   */
  public function transform(?Expression $leftExpression, ?Expression $rightExpression): Expression;

  public function buildNode(Scope $scope, ?Expression $leftExpression, ?Expression $rightExpression): Node;
}
