<?php

declare(strict_types=1);

namespace TimoLehnertz\formula\expression;

use TimoLehnertz\formula\nodes\Node;
use TimoLehnertz\formula\PrettyPrintOptions;
use TimoLehnertz\formula\operator\ImplementableOperator;
use TimoLehnertz\formula\operator\ParsedOperator;
use TimoLehnertz\formula\procedure\Scope;

/**
 * @author Timo Lehnertz
 *        
 *         Represents an OperatorExpression whose string representation differs from the default implementation
 */
class ComplexOperatorExpression extends OperatorExpression {

  private readonly ?Expression $outerLeftExpression;

  private readonly ?ParsedOperator $outerOperator;

  private readonly ?Expression $outerRightExpression;

  public function __construct(?Expression $innerLeftExpression, ImplementableOperator $innerOperator, ?Expression $innerRightExpression, ?Expression $outerLeftExpression, ?ParsedOperator $outerOperator, ?Expression $outerRightExpression) {
    parent::__construct($innerLeftExpression, $innerOperator, $innerRightExpression);
    $this->outerLeftExpression = $outerLeftExpression;
    $this->outerOperator = $outerOperator;
    $this->outerRightExpression = $outerRightExpression;
  }

  public function toString(PrettyPrintOptions $prettyPrintOptions): string {
    $str = '';
    if ($this->outerLeftExpression !== null) {
      $str .= $this->outerLeftExpression->toString($prettyPrintOptions);
    }
    if ($this->outerOperator !== null) {
      $str .= $this->outerOperator->toString($prettyPrintOptions);
    }
    if ($this->outerRightExpression !== null) {
      $str .= $this->outerRightExpression->toString($prettyPrintOptions);
    }
    return $str;
  }

  public function buildNode(Scope $scope): Node {
    return $this->outerOperator->buildNode($scope, $this->outerLeftExpression, $this->outerRightExpression);
  }

  public function getOuterLeftExpression(): ?Expression {
    return $this->outerLeftExpression;
  }

  public function getOuterOperator(): ?ParsedOperator {
    return $this->outerOperator;
  }

  public function getOuterRightExpression(): ?Expression {
    return $this->outerRightExpression;
  }
}
