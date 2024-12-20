<?php
namespace test;

use PHPUnit\Framework\TestCase;
use TimoLehnertz\formula\expression\ArgumentListExpression;
use TimoLehnertz\formula\expression\OperatorExpression;
use TimoLehnertz\formula\Formula;
use TimoLehnertz\formula\procedure\DefaultScope;
use TimoLehnertz\formula\procedure\Scope;

class FormulaIntegrationTest extends TestCase {

  public function testPrim(): void {
    $source = file_get_contents('test/prim.formula');
    $formula = new Formula($source);
    $result = $formula->calculate();
    $this->assertEquals(25, $result->toPHPValue());
  }

  public function testTypeRestrictions(): void {
    $scope = new DefaultScope();
    $formula = new Formula('sum(1,2,3)', $scope);
    $result = $formula->calculate()->toPHPValue();
    $this->assertEquals(6, $result);

    /** @var OperatorExpression */
    $operatorExpression = $formula->getContent()->getContent();
    /** @var ArgumentListExpression */
    $argumentListExpression = $operatorExpression->getRightExpression();
    $argumentValues = [];
    foreach ($argumentListExpression->getExpressions() as $expression) {
      $argumentValues[] = $expression->validate($scope)->getRestrictedValues()[0]->toPHPValue();
    }
    $this->assertEquals([1, 2, 3], $argumentValues);
  }
}
