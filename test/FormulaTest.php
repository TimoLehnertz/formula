<?php

namespace test;

use PHPUnit\Framework\TestCase;
use TimoLehnertz\formula\Formula;
use TimoLehnertz\formula\FormulaRuntimeException;
use TimoLehnertz\formula\FormulaValidationException;
use TimoLehnertz\formula\PrettyPrintOptions;
use TimoLehnertz\formula\procedure\Scope;
use TimoLehnertz\formula\tokens\TokenisationException;
use TimoLehnertz\formula\type\BooleanType;
use TimoLehnertz\formula\type\CompoundType;
use TimoLehnertz\formula\type\FloatType;
use TimoLehnertz\formula\type\FloatValue;
use TimoLehnertz\formula\type\IntegerType;
use TimoLehnertz\formula\type\StringType;
use TimoLehnertz\formula\type\StringValue;

use function PHPUnit\Framework\assertEquals;

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
    $scope = new Scope();
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
    $scope = new Scope();
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
    $scope = new Scope();
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
    $scope = new Scope();
    $formula = new Formula('sum(1)', $scope);
    $this->assertEquals(1, $formula->calculate()->toPHPValue());
    // print_r($formula->getNodeTree()['rootNode']);
    // $rootNode = $formula->getNodeTree()['rootNode'];
    // $this->assertEquals('ComplexOperatorExpression', $rootNode['nodeType']);
    // $this->assertEquals('+=', $rootNode['properties']['operator']);
  }

  public function testArrayAccessOperatorNodeTree(): void {
    $scope = new Scope();
    $formula = new Formula('{1}[0]', $scope);
    $this->assertEquals(1, $formula->calculate()->toPHPValue());
    $this->assertCount(2, $formula->getNodeTree()['rootNode']['connected']);
    // $rootNode = $formula->getNodeTree()['rootNode'];
    // $this->assertEquals('ComplexOperatorExpression', $rootNode['nodeType']);
    // $this->assertEquals('+=', $rootNode['properties']['operator']);
  }

  // public function testAdvanced(): void {
  //   $src = 'getModuleComponentIndex()==1 ? S1875-S362 : earlyReturnIfNull(getModuleComponentIndex())>1 ? earlyReturnIfNull(getMeasurementAtComponentIndex(earlyReturnIfNull(getModuleComponentIndex())-1,{S362ID,S363ID,S364ID,S365ID,S366ID},MeasurementInterpolation.OFF)) -S362 : null';
  //   $scope = new Scope();
  //   $scope->definePHP(true, 'S1875', 1);
  //   $scope->definePHP(true, 'S362', 1);
  //   $scope->definePHP(true, 'S362ID', 1);
  //   $scope->definePHP(true, 'S363ID', 1);
  //   $scope->definePHP(true, 'S364ID', 1);
  //   $scope->definePHP(true, 'S365ID', 1);
  //   $scope->definePHP(true, 'S366ID', 1);
  //   $scope->definePHP(true, 'getModuleComponentIndex', function (): ?int {
  //     return 0;
  //   });
  //   $scope->definePHP(true, 'getMeasurementAtComponentIndex', function (int $componentIndex, array $sensorIDs, ?MeasurementInterpolation $interpolation = null): ?int {
  //     return 1;
  //   });
  //   $scope->definePHP(true, 'MeasurementInterpolation', MeasurementInterpolation::class);
  //   $formula = new Formula($src, $scope);
  // }

  //   //   public function testUnexpectedEndOfInputException(): void {
  //   //     $this->expectException(ExpressionNotFoundException::class);
  //   //     $this->expectExceptionMessage('Unexpected end of input. Formula: "(1+2+3"  At position: 6');
  //   //     $formula = new Formula('(1+2+3');
  //   //     $formula->calculate();
  //   //   }

  //   //   public function strFunc() {
  //   //     return "Hallo welt";
  //   //   }

  //   //   public function testGetStringLiterals(): void {
  //   //     $formula = new Formula('strFunc("hallo", "welt", "hallo", "welt")');
  //   //     $formula->setMethod('strFunc', [$this, "strFunc"]);
  //   //     $this->assertEquals("Hallo welt", $formula->calculate());
  //   //     $this->assertEquals(['hallo', 'welt', 'hallo', 'welt'], $formula->getStringLiterals());
  //   //   }

  //   //   public function testRenameVariables(): void {
  //   //     $formula = new Formula('a+b+maxFunc(a,minFunc(a,b))');
  //   //     $formula->renameVariables('a', 'c');
  //   //     $formula->renameVariables('b', 'd');
  //   //     $formula->renameVariables('D', 'f'); // to check that it is case sensitive
  //   //     $formula->renameMethods('minFunc', 'min', false);
  //   //     $formula->renameMethods('maxFunc', 'max', false);
  //   //     $formula->setVariable('c', 10);
  //   //     $formula->setVariable('d', 20);
  //   //     $this->assertEquals(10+20+max(10,min(10, 20)), $formula->calculate());
  //   //   }

  //   //   public function testRenameVariablesCaseInsensitive(): void {
  //   //     $formula = new Formula('A+B+MAXFUNC(A,MINFUNC(a,b))');
  //   //     $formula->renameVariables('a', 'c', false);
  //   //     $formula->renameVariables('b', 'd', false);
  //   //     $formula->renameMethods('minFunc', 'min', false);
  //   //     $formula->renameMethods('maxFunc', 'max', false);
  //   //     $formula->setVariable('c', 10);
  //   //     $formula->setVariable('d', 20);
  //   //     $this->assertEquals(10+20+max(10,min(10, 20)), $formula->calculate());
  //   //   }

  //   //   public function testRenameStrings(): void {
  //   //     $formula = new Formula('"Hallo"');
  //   //     $formula->renameStrings('Hallo', 'Welt');
  //   //     $this->assertEquals('Welt', $formula->calculate());
  //   //   }

  //   //   public function testRenameMethods(): void {
  //   //     $formula = new Formula('abc(1,2)');
  //   //     $formula->renameMethods('abc', 'min');
  //   //     $this->assertEquals(1, $formula->calculate());
  //   //   }

  //   //   public function provideFormulaStrings(): array {
  //   //     return [
  //   //       ['1+1'],
  //   //       ['a + b'],
  //   //       ['max(50, 3, 100) + (a ? b : min(b,a))'],
  //   //       ['"P1D" + "P2D"'],
  //   //       ['{a,b,c}[2]'],
  //   //       ['(1((1)1)2)'],
  //   //       ['"Hallo welt"'],
  //   //       ['a+b+max(a,min(a,b))'],
  //   //       ['{1,2,a+max(a,b,c)} + {1,2,3}'],
  //   //       ['(a+(b-c))*(a/d)*e+pow(a,b)*(b/d)-pow(a,e)'],
  //   //       ['a&&b||c^d!=e>=f'],
  //   //     ];
  //   //   }

  //   //   /**
  //   //    * @dataProvider provideFormulaStrings
  //   //    */
  //   //   public function testPrettyprintFormula(string $formulaString): void {
  //   //     $formula1 = new Formula($formulaString);
  //   //     $formula1->setVariable('a', 0);
  //   //     $formula1->setVariable('b', 1);
  //   //     $formula1->setVariable('c', 2);
  //   //     $formula1->setVariable('d', 3);
  //   //     $formula1->setVariable('e', 4);
  //   //     $formula1->setVariable('f', 5);
  //   //     $parsedString = $formula1->prettyprintFormula();
  //   //     $formula2 = new Formula($parsedString);
  //   //     $formula2->setVariable('a', 0);
  //   //     $formula2->setVariable('b', 1);
  //   //     $formula2->setVariable('c', 2);
  //   //     $formula2->setVariable('d', 3);
  //   //     $formula2->setVariable('e', 4);
  //   //     $formula2->setVariable('f', 5);
  //   //     $formula2->calculate();
  //   //     $this->assertEquals($formula1->calculate(), $formula2->calculate());
  //   //   }

  //   //   public function methodTest(): int {
  //   //     return 123;
  //   //   }

  //   //   public function testMethofNoArgs(): void {
  //   //     $formula1 = new Formula('methodTest()');
  //   //     $formula1->setMethod('methodTest', [$this, 'methodTest']);

  //   //     $this->assertEquals(123, $formula1->calculate());
  //   //   }

  //   //   public function testStringConcatination(): void {
  //   //     $formula = new Formula('1.0 + "Hello" + " " + "world" + 1.0');
  //   //     $result = $formula->calculate();
  //   //     $this->assertEquals('1Hello world1', $result);

  //   //     $formula = new Formula('"S"');
  //   //     $result = $formula->calculate();
  //   //     $this->assertEquals('S', $result);
  //   //   }

  //   //   public function testDotVariables(): void {
  //   //     $formula = new Formula('self.id');
  //   //     $formula->setVariable('self.id', 123);
  //   //     $result = $formula->calculate();
  //   //     $this->assertEquals(123, $result);
  //   //   }

  //   //   public function dummyArrayFunc(int $index, array $arr) {
  //   //     return $arr[$index];
  //   //   }

  //   //   public function testdummyArrayFunc(): void {
  //   //     $formula = new Formula('dummyArrayFunc(2, {"S3", "S4", "S10"})');
  //   //     $formula->setMethod('dummyArrayFunc', [$this, 'dummyArrayFunc']);
  //   //     $result = $formula->calculate();
  //   //     $this->assertEquals('S10', $result);
  //   //   }

  //   //   public function testStringifyBrackets(): void {
  //   //     $testFormula = "((((getModuleComponentIndex()==1)))?(1):((getModuleComponentIndex()>1)?(s362/getMeasurementAtComponentIndex((getModuleComponentIndex()-1),{'s362','s363','s364','s365','s366'})):0))*100";
  //   //     $formula = new Formula($testFormula);
  //   //     $stringified = $formula->prettyprintFormula();
  //   //     $this->assertEquals("(getModuleComponentIndex()==1?1:getModuleComponentIndex()>1?s362/getMeasurementAtComponentIndex(getModuleComponentIndex()-1,{'s362','s363','s364','s365','s366'}):0)*100", $stringified);
  //   //   }

  //   //   public function testEmptyExpression(): void {
  //   //     $testFormula = "testFunc(())";
  //   //     $this->expectException(ExpressionNotFoundException::class);
  //   //     $this->expectExceptionMessage('Expression can\'t be empty. Formula: "testFunc(())"  At position: 3');
  //   //     new Formula($testFormula);
  //   //   }

  //   //   function getModuleComponentIndexFunc(): int {
  //   //     return -1;
  //   //   }

  //   //   public function testComplexTernary(): void {
  //   //     $formula = new Formula("((getModuleComponentIndex()>=1&&getModuleComponentIndex()<=2)?1:(getModuleComponentIndex()>2?2:-1))");
  //   //     $formula->setMethod('getModuleComponentIndex', [$this, 'getModuleComponentIndexFunc']);
  //   //     $res = $formula->calculate();
  //   //     $this->assertEquals(-1, $res);
  //   //   }
  //   public function testSumFunc(): void {
  //     $res = (new Formula("sum({1,{{{2}},2},4}, 5, {6,7+8+9})"))->calculate();
  //     $this->assertEquals(1 + 2 + 2 + 4 + 5 + 6 + 7 + 8 + 9, $res->toPHPValue());
  //     $this->expectException(FormulaValidationException::class);
  //     $this->expectExceptionMessage('No conversion from string to array<mixed,mixed>|float exists');
  //     $formula = new Formula("sum('Error')");
  //     echo (json_encode($formula->getNodeTree()));
  //     $res = $formula->calculate();
  //   }

  //   //   public function testUnusedVariable(): void {
  //   //     $formula = new Formula("a ? b : c");
  //   //     $formula->setVariable('a', true);
  //   //     $formula->setVariable('b', 123);
  //   //     $this->assertEquals(123, $formula->calculate());
  //   //     $formula->setVariable('a', false);
  //   //     $this->expectException(NoVariableValueException::class);
  //   //     $this->expectExceptionMessage("Can't calculate. Variable c has no value");
  //   //     $formula->calculate();
  //   //   }

  //   //   public function testResetVariable(): void {
  //   //     $formula = new Formula("a + b");
  //   //     $formula->setVariable('a', 1);
  //   //     $formula->setVariable('b', 2);
  //   //     $this->assertEquals(3, $formula->calculate());
  //   //     $formula->resetVariable('b');
  //   //     $this->expectException(NoVariableValueException::class);
  //   //     $this->expectExceptionMessage("Can't calculate. Variable b has no value");
  //   //     $formula->calculate();
  //   //   }

  //   //   public function testResetAllVariables(): void {
  //   //     $formula = new Formula("a + b");
  //   //     $formula->setVariable('a', 1);
  //   //     $formula->setVariable('b', 2);
  //   //     $this->assertEquals(3, $formula->calculate());
  //   //     $formula->resetAllVariables();
  //   //     $this->expectException(NoVariableValueException::class);
  //   //     $this->expectExceptionMessage("Can't calculate. Variable a has no value");
  //   //     $formula->calculate();
  //   //   }

  //   //   public function mockFunction(): int {
  //   //     return 1;
  //   //   }

  //   //   public function testResetMethod(): void {
  //   //     $formula = new Formula("min(1,2,3)");
  //   //     $formula->resetMethod('min');
  //   //     $this->assertEquals(1, $formula->calculate());
  //   //     $formula = new Formula("testFunc()");
  //   //     $formula->setMethod('testFunc', [$this, 'mockFunction']);
  //   //     $this->assertEquals(1, $formula->calculate());
  //   //     $formula->resetMethod('testFunc');
  //   //     $this->expectException(ExpressionNotFoundException::class);
  //   //     $this->expectExceptionMessage("No method provided for testFunc!");
  //   //     $formula->calculate();
  //   //   }

  //   //   public function testResetAllMethods(): void {
  //   //     $formula = new Formula("testFunc1() + testFunc2()");
  //   //     $formula->setMethod('testFunc1', [$this, 'mockFunction']);
  //   //     $formula->setMethod('testFunc2', [$this, 'mockFunction']);
  //   //     $this->assertEquals(2, $formula->calculate());
  //   //     $formula->resetAllMethods();
  //   //     $this->expectException(ExpressionNotFoundException::class);
  //   //     $this->expectExceptionMessage("No method provided for testFunc1!");
  //   //     $formula->calculate();
  //   //   }

  //   //   public function testNoVariableValueException(): void {
  //   //     $formula = new Formula("a");
  //   //     try {
  //   //       $formula->calculate();
  //   //     } catch (NoVariableValueException $e) {
  //   //       $this->once();
  //   //       $this->assertEquals('a', $e->getMissingVariable());
  //   //     }
  //   //   }

  //   //   /**
  //   //    * @dataProvider booleanDataProvider
  //   //    */
  //   //   public function testOperatorOrder($a, $b): void {
  //   //     $formula = new Formula('a&&b==a||!b^a!=!b');
  //   //     $formula->setVariable('a', $a);
  //   //     $formula->setVariable('b', $b);
  //   //     $this->assertEquals($a&&$b==$a||!$b^$a!=!$b, $formula->calculate() == 1);
  //   //   }

  //   //   public function testNegativeNumber(): void {
  //   //     $formula = new Formula("-5");
  //   //     $this->assertEquals(-5, $formula->calculate());
  //   //     $formula = new Formula("-5 + 3");
  //   //     $this->assertEquals(-2, $formula->calculate());
  //   //     $formula = new Formula("-(5) + 3");
  //   //     $this->assertEquals(-2, $formula->calculate());
  //   //     $formula = new Formula("(-5) + 3");
  //   //     $this->assertEquals(-2, $formula->calculate());
  //   //     $formula = new Formula("-(5 + 3)");
  //   //     $this->assertEquals(-8, $formula->calculate());
  //   //     $formula = new Formula("-(5 + -3)");
  //   //     $this->assertEquals(-2, $formula->calculate());
  //   //     $formula = new Formula("-(5 * -3)");
  //   //     $this->assertEquals(15, $formula->calculate());
  //   //   }

  //   //   public function testNull(): void {
  //   //     $formula = new Formula("null");
  //   //     $this->assertEquals(null, $formula->calculate());
  //   //     $formula = new Formula("a");
  //   //     $formula->setVariable('a', null);
  //   //     $this->assertEquals(null, $formula->calculate());

  //   //     $formula = new Formula("getModuleComponentIndex()>=1&&getModuleComponentIndex()<=2?s1858*s1883/1000*24:getModuleComponentIndex()>2?s1858*s564/1000*24:null");
  //   //     $formula->setMethod('getModuleComponentIndex', function() {return -1;});
  //   //     $formula->setVariable('s1858', 1);
  //   //     $formula->setVariable('s1883', 1);
  //   //     $formula->setVariable('s1858', 1);
  //   //     $formula->setVariable('s564', 1);
  //   //     $this->assertEquals(null, $formula->calculate());
  //   //   }
}


enum MeasurementInterpolation: string {

  case OFF = 'O';

  case TO_ZERO = 'Z';

  case FILL = 'F';

  case LINEAR = 'L';
}
