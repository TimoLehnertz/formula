<?php

namespace test;

use PHPUnit\Framework\TestCase;
use TimoLehnertz\formula\Formula;
use TimoLehnertz\formula\FormulaRuntimeException;
use TimoLehnertz\formula\FormulaValidationException;
use TimoLehnertz\formula\PrettyPrintOptions;
use TimoLehnertz\formula\procedure\DefaultScope;
use TimoLehnertz\formula\procedure\Scope;
use TimoLehnertz\formula\tokens\TokenisationException;
use TimoLehnertz\formula\type\FloatType;
use TimoLehnertz\formula\type\IntegerType;

class FormulaTest extends TestCase {

  public function testEmptyFormula(): void {
    $this->expectException(TokenisationException::class);
    $this->expectExceptionMessage('Invalid formula');
    new Formula('');
  }

  public function testGetNodeTree(): void {
    $formula = new Formula('1+1');
    $nodeTree = $formula->getNodeTree();
    // echo json_encode($nodeTree);
    $this->assertEquals('OperatorExpression', $nodeTree['rootNode']['nodeType']);
    $this->assertCount(2, $nodeTree['rootNode']['connected']);
  }

  public function testWhile(): void {
    $formula = new Formula('int a = 0; while(a<3){a++;} return a;');
    $this->assertInstanceOf(IntegerType::class, $formula->getReturnType());
    $this->assertEquals(3, $formula->calculate()->toPHPValue());
  }


  public function testEqualsNull(): void {
    $formula = new Formula('int a = 0; return a == null;');
    $this->assertTrue($formula->calculate()->toPHPValue() === false);
    $formula = new Formula('int|null a = null; return a == null;');
    $this->assertTrue($formula->calculate()->toPHPValue() === true);
  }

  public function testIndentation(): void {
    $formula = new Formula('if(true){return false;}');
    $this->assertEquals("if (true) {\r\n  return false;\r\n}", $formula->prettyprintFormula());
    $prettyPrintOptions = new PrettyPrintOptions();
    $prettyPrintOptions->indentationMethodSpaces = false;
    $prettyPrintOptions->charsPerIndent = 1;
    $this->assertEquals("if (true) {\r\n\treturn false;\r\n}", $formula->prettyprintFormula($prettyPrintOptions));
  }

  public function testVariables(): void {
    $scope = new Scope();
    $scope->definePHP(false, 'a', 1);
    $scope->definePHP(false, 'b', 1);
    $scope->definePHP(false, 'c', 1);
    $scope->definePHP(false, 'd', 1);
    $scope->definePHP(false, 'e', 1.0);
    $str = 'a+b+c+d+e';
    $formula = new Formula($str, $scope);
    $this->assertInstanceOf(FloatType::class, $formula->getReturnType());
    for ($i = 0; $i < 10; $i++) {
      $a = rand(-1000, 1000);
      $b = rand(-1000, 1000);
      $c = rand(-1000, 1000);
      $d = rand(-1000, 1000);
      $e = rand(-1000, 1000) + 1.5;
      $scope->assignPHP('a', $a);
      $scope->assignPHP('b', $b);
      $scope->assignPHP('c', $c);
      $scope->assignPHP('d', $d);
      $scope->assignPHP('e', $e);
      $this->assertEquals($a + $b + $c + $d + $e, $formula->calculate()->toPHPValue());
    }
  }

  public function testpow(): void {
    $str = 'pow(a,b)';
    $scope = new DefaultScope();
    $scope->definePHP(false, 'a', 1);
    $scope->definePHP(false, 'b', 1);
    $formula = new Formula($str, $scope);
    for ($i = 0; $i < 1; $i++) {
      $a = rand(0, 10);
      $b = rand(0, 10);
      $scope->assignPHP('a', $a);
      $scope->assignPHP('b', $b);
      $result = $formula->calculate();
      $this->assertEquals(pow($a, $b), $result->toPHPValue());
    }
  }

  public function testNesting(): void {
    $str = '((min(a,2)*b+5)/(((2+5)-5)+99*0))-7.5';
    $scope = new DefaultScope();
    $scope->definePHP(false, 'a', 2);
    $scope->definePHP(false, 'b', 5);
    $formula = new Formula($str, $scope);
    $result = $formula->calculate();
    $this->assertEquals($result->toPHPValue(), 0);
  }

  // from original repo at https://github.com/socialist/formula
  public function testAllResults() {
    $parser = new Formula('2 * 2.65');
    $this->assertEquals('5.3', $parser->calculate()->toPHPValue());

    $parser = new Formula('2 * 2.65 + 25');
    $this->assertEquals('30.3', $parser->calculate()->toPHPValue());

    $parser = new Formula('2 * 2.65 + 25 / 3');
    $this->assertEquals(round('13.63'), round($parser->calculate()->toPHPValue()));

    $parser = new Formula('2 + 3 * 2.65 + 25');
    $this->assertEquals('34.95', $parser->calculate()->toPHPValue());

    $parser = new Formula('2 + 3 * 2.65 + 25 - 26');
    $this->assertEqualsWithDelta('8.95', $parser->calculate()->toPHPValue(), 0.0001);

    $parser = new Formula('2 + 3 - 4 * 2.65 + 25 - 26');
    $this->assertEqualsWithDelta('-6.6', $parser->calculate()->toPHPValue(), 0.0001);

    $parser = new Formula('( 15 + 235 ) * 2.65');
    $this->assertEquals('662.5', $parser->calculate()->toPHPValue());

    $parser = new Formula('( 2 + ( 3 - 4 ) ) * 2.65 + 25 - 26');
    $this->assertEqualsWithDelta('1.65', $parser->calculate()->toPHPValue(), 0.0001);

    $parser = new Formula('( 2 + ( 3 - 4 ) ) * ( 2.65 + ( 25 - 26 ) )');
    $this->assertEquals('1.65', $parser->calculate()->toPHPValue());

    $parser = new Formula('( 2 + ( 3 * 235 - 4 ) ) + 25');
    $this->assertEquals('728', $parser->calculate()->toPHPValue());
  }

  public function testVectoroutOfBounds1(): void {
    $this->expectException(FormulaRuntimeException::class);
    $this->expectExceptionMessage('Array key 3 does not exist');
    (new Formula('{1,2,3}[3]+1'))->calculate();
  }

  public function testRecalculate(): void {
    $scope = new Scope();
    $scope->definePHP(false, 'i', 1);
    $formula = new Formula('i+1', $scope);
    $this->assertEquals(2, $formula->calculate()->toPHPValue());
    $scope->assignPHP('i', 2);
    $this->assertEquals(3, $formula->calculate()->toPHPValue());
  }

  public function testIsUsed(): void {
    $scope = new Scope();
    $scope->definePHP(false, 'i', 1);
    new Formula('1+1', $scope);
    $this->assertFalse($scope->isUsed('i'));
    $scope = new Scope();
    $scope->definePHP(false, 'i', 1);
    new Formula('1+i', $scope);
    $this->assertTrue($scope->isUsed('i'));
  }

  public function testGetSource(): void {
    $formula = new Formula('1+2+3');
    $this->assertEquals('1+2+3', $formula->getSource());
  }

  public function testConstraintReturnType(): void {
    $formula = new Formula('"2024-01-01"', null, new IntegerType());
    $this->assertInstanceOf(IntegerType::class, $formula->getReturnType());
    $this->assertEquals((new \DateTimeImmutable("2024-01-01"))->getTimestamp(), $formula->calculate()->toPHPValue());
  }

  public function testConstraintInvalidReturnType(): void {
    $this->expectException(FormulaValidationException::class);
    $this->expectExceptionMessage('1:0 Validation error: Unable to convert DateTimeImmutable to float');
    new Formula('"2024-01-01"', null, new FloatType());

  }

  public function testDoubleTernary(): void {
    $formula = new Formula('false ? 1 : false ? true : 123');
    $this->assertEquals(123, $formula->calculate()->toPHPValue());
  }

  public function getMeasurements(int $sensorID, \DateInterval|int $fromBackInterval, \DateInterval|int $toFrontInterval = new \DateInterval('P0D')): int {
    return 1;
  }

  public function testComplexExpression(): void {
    $scope = new DefaultScope();
    $scope->definePHP(true, 'getMeasurements', [$this, 'getMeasurements']);
    $scope->definePHP(true, 'S4799ID', 4799);
    $scope->definePHP(true, 'S1820ID', 1820);
    $scope->definePHP(true, 'S1555ID', 1555);
    $scope->definePHP(true, 'S4582ID', 4582);
    $source = file_get_contents('test/advancedExpression.formula');
    $formula = new Formula($source, $scope);
    $this->assertEqualsWithDelta($formula->calculate()->toPHPValue(), 29, 1);
  }

  public function testImplicitCastToString(): void {
    $scope = new Scope();
    $scope->definePHP(false, 'a', false);
    $formula = new Formula('a = 1', $scope);
    $this->assertEquals("a=1", $formula->prettyprintFormula());
  }

  public function testImplicitCastToNodeTree(): void {
    $scope = new Scope();
    $scope->definePHP(false, 'a', false);
    $formula = new Formula('a = 1', $scope);
    $this->assertEquals('{"nodeType":"OperatorExpression","connected":[{"nodeType":"IdentifierExpression","connected":[],"properties":{"identifier":"a"}},{"nodeType":"ConstantExpression","connected":[],"properties":{"type":{"typeName":"IntegerType"},"value":"1"}}],"properties":{"operator":12}}', json_encode($formula->getNodeTree()['rootNode']));
  }

  public function testChainedOperatorsNodeTree(): void {
    $scope = new Scope();
    $scope->definePHP(false, 'a', 1);
    $formula = new Formula('a+=1', $scope);
    $this->assertEquals(2, $formula->calculate()->toPHPValue());
    $rootNode = $formula->getNodeTree()['rootNode'];
    $this->assertEquals('+=', $rootNode['nodeType']);
  }

  public function testCallOperatorNodeTree(): void {
    $scope = new DefaultScope();
    $formula = new Formula('sum(1)', $scope);
    $this->assertEquals(1, $formula->calculate()->toPHPValue());
  }

  public function testArrayAccessOperatorNodeTree(): void {
    $scope = new Scope();
    $formula = new Formula('{1}[0]', $scope);
    $this->assertEquals(1, $formula->calculate()->toPHPValue());
    $this->assertCount(2, $formula->getNodeTree()['rootNode']['connected']);
  }

  public function testFunctionWithNoParams(): void {
    $formula = new Formula('int r() {return 1;} return r();');
    $this->assertEquals(1, $formula->calculate()->toPHPValue());
  }

  public function testVoidFunction(): void {
    new Formula('void r() {}');
    $this->assertTrue(true); // assert no exception
  }

  public function testRecursiveFunction(): void {
    $formula = new Formula('int r(int a) {return a < 10 ? r(a + 1) : a;} return r(0);');
    $this->assertEquals(10, $formula->calculate()->toPHPValue());
  }

  public function testAnonymousFunctionInstantCall(): void {
    $formula = new Formula('((int a) -> a)(1)');
    $this->assertEquals(1, $formula->calculate()->toPHPValue());
  }

  public function testAnonymousVoidFunction(): void {
    $formula = new Formula('int b = 0;function(int) -> void a = void (int a) {b = a;}; a(1); return b;');
    $this->assertEquals(1, $formula->calculate()->toPHPValue());
  }
}
