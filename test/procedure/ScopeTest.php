<?php

namespace test\procedure;

use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertInstanceOf;
use TimoLehnertz\formula\Formula;
use TimoLehnertz\formula\FormulaBugException;
use TimoLehnertz\formula\FormulaRuntimeException;
use TimoLehnertz\formula\FormulaValidationException;
use TimoLehnertz\formula\procedure\Scope;
use TimoLehnertz\formula\type\ArrayType;
use TimoLehnertz\formula\type\ArrayValue;
use TimoLehnertz\formula\type\BooleanType;
use TimoLehnertz\formula\type\BooleanValue;
use TimoLehnertz\formula\type\CompoundType;
use TimoLehnertz\formula\type\FloatType;
use TimoLehnertz\formula\type\FloatValue;
use TimoLehnertz\formula\type\IntegerType;
use TimoLehnertz\formula\type\IntegerValue;
use TimoLehnertz\formula\type\MixedType;
use TimoLehnertz\formula\type\NullType;
use TimoLehnertz\formula\type\NullValue;
use TimoLehnertz\formula\type\StringType;
use TimoLehnertz\formula\type\StringValue;
use TimoLehnertz\formula\type\Type;
use TimoLehnertz\formula\type\Value;
use TimoLehnertz\formula\type\classes\ClassType;
use TimoLehnertz\formula\type\classes\FieldType;
use TimoLehnertz\formula\type\classes\PHPClassInstanceValue;
use TimoLehnertz\formula\type\DateTimeImmutableType;
use TimoLehnertz\formula\type\functions\FunctionType;
use TimoLehnertz\formula\type\functions\OuterFunctionArgument;
use TimoLehnertz\formula\type\functions\OuterFunctionArgumentListType;
use TimoLehnertz\formula\ValueUnsetException;

use const false;

class ScopeTest extends TestCase {

  public function testDefine(): void {
    $scope = new Scope();
    $this->assertFalse($scope->isDefined('i'));
    $scope->definePHP(false, 'i', 0);
    $this->assertTrue($scope->isDefined('i'));
  }

  public function testRedefine(): void {
    $scope = new Scope();
    $scope->definePHP(false, 'i', 0);
    $this->expectException(FormulaRuntimeException::class);
    $this->expectExceptionMessage('Can\'t redefine i');
    $scope->definePHP(false, 'i', 0);
  }

  public function testGet(): void {
    $scope = new Scope();
    $scope->definePHP(false, 'i', 0);
    $value = $scope->get('i');
    $type = $scope->use('i');
    $this->assertInstanceOf(IntegerValue::class, $value);
    $this->assertInstanceOf(IntegerType::class, $type);
  }

  public function testParentGet(): void {
    $parentScope = new Scope();
    $parentScope->definePHP(false, 'i', 0);

    $childScope = $parentScope->buildChild();

    $value = $childScope->get('i');
    $type = $childScope->use('i');
    $this->assertInstanceOf(IntegerValue::class, $value);
    $this->assertInstanceOf(IntegerType::class, $type);
  }

  public function testGetNotDefined(): void {
    $scope = new Scope();
    $this->expectException(FormulaRuntimeException::class);
    $this->expectExceptionMessage('i is not defined');
    $scope->get('i');
  }

  public function testGetTypeNotDefined(): void {
    $scope = new Scope();
    $this->expectException(FormulaRuntimeException::class);
    $this->expectExceptionMessage('i is not defined');
    $scope->use('i');
  }

  public function testIsDefined(): void {
    $scope = new Scope();
    $scope->definePHP(true, 'i', 1);
    $this->assertFalse($scope->isUsed('i'));
    $scope->use('i');
    $this->assertTrue($scope->isUsed('i'));
  }

  public function phpVarProvider(): array {
    // @formatter:off
    return [
      [1, new IntegerType(), new IntegerValue(1)],
      [1.5, new FloatType(), new FloatValue(1.5)],
      [false, new BooleanType(), new BooleanValue(false)],
      [true, new BooleanType(), new BooleanValue(true)],
      ['abc', new StringType(), new StringValue('abc')],
      [null, new NullType(), new NullValue()],
      [
        ['string' => 1, 2 => false],
        new ArrayType(CompoundType::buildFromTypes([new IntegerType(), new StringType()]), CompoundType::buildFromTypes([new IntegerType(), new BooleanType()])),
        new ArrayValue(['string' => new IntegerValue(1), 2 => new BooleanValue(false)])
      ],
    ];
    // @formatter:on
  }

  /**
   * @dataProvider phpVarProvider
   */
  public function testConvertPHPVar(mixed $phpValue, Type $expectedType, Value $expectedValue): void {
    $res = Scope::convertPHPVar($phpValue);
    $this->assertTrue($expectedType->equals($res[0]));
    $this->assertInstanceOf($expectedValue::class, $res[1]);
    $this->assertEquals($expectedValue->toPHPValue(), $res[1]->toPHPValue());
  }

  public function testConvertPHPObject(): void {
    $object = new PHPTestClass(1, 2, [1, 2]);
    $res = Scope::convertPHPVar($object);
    // @formatter:off
    $expectedClassType = new ClassType(
      new ClassType(null, ParentClass::class, [
        'i' => new FieldType(false, new IntegerType()),
      ]),
      PHPTestClass::class,
      [
        'publicReadonlyInt' => new FieldType(true, new IntegerType()),
        'publicArray' => new FieldType(false, new ArrayType(new MixedType(), new MixedType())),
        'add' => new FieldType(true, new FunctionType(new OuterFunctionArgumentListType([new OuterFunctionArgument(new IntegerType(), false, false), new OuterFunctionArgument(new IntegerType(), false, false)], false), new IntegerType()))
      ]
    );
    // @formatter:on
    $this->assertTrue($expectedClassType->equals($res[0]));
    $value = $res[1];
    assertInstanceOf(PHPClassInstanceValue::class, $value);
    $this->assertTrue($value->toPHPValue() === $object);
  }

  public function testPHPObject(): void {
    $scope = new Scope();
    $scope->definePHP(true, 'phpObject', new PHPTestClass(1, 2, [1, 2]));
    $formula = new Formula('phpObject.publicReadonlyInt', $scope);
    $this->assertEquals(1, $formula->calculate()->toPHPValue());
    $formula = new Formula('phpObject.publicArray', $scope);
    $this->assertEquals([1, 2], $formula->calculate()->toPHPValue());
    $formula = new Formula('phpObject.add(1,2)', $scope);
    $this->assertEquals(3, $formula->calculate()->toPHPValue());
    $formula = new Formula('phpObject.i', $scope);
    $this->assertEquals(0, $formula->calculate()->toPHPValue());
  }

  public function testFormulInFormula(): void {
    $scope = new Scope();
    $scope->definePHP(true, 'Formula', Formula::class);
    $scope->definePHP(true, 'Scope', Scope::class);
    $formula = new Formula('
      var scope = new Scope();
      scope.definePHP(true, "i", 5);
      var formula = new Formula("i+1", scope);
      return formula.calculate();
    ', $scope);
    $this->assertEquals(6, $formula->calculate()->toPHPValue());
  }

  public function testAssignSelf(): void {
    $scope = new Scope();
    $scope->definePHP(false, 'i', 0);
    $value = $scope->get('i');
    $this->assertEquals(0, $value->toPHPValue());
    $scope->assign('i', new IntegerValue(1));
    $value = $scope->get('i');
    $this->assertEquals(1, $value->toPHPValue());
  }

  public function testAssignParent(): void {
    $parentScope = new Scope();
    $parentScope->definePHP(false, 'i', 0);
    $childScope = $parentScope->buildChild();
    $value = $childScope->get('i');
    $this->assertEquals(0, $value->toPHPValue());
    $childScope->assignPHP('i', 1);
    $value = $childScope->get('i');
    $this->assertEquals(1, $value->toPHPValue());
  }

  public function testAssignUndefined(): void {
    $scope = new Scope();
    $this->expectException(FormulaRuntimeException::class);
    $this->expectExceptionMessage('i is not defined');
    $scope->assignPHP('i', 0);
  }

  public function testUnsetUndefined(): void {
    $scope = new Scope();
    $this->expectException(FormulaRuntimeException::class);
    $this->expectExceptionMessage('i is not defined');
    $scope->unset('i');
  }

  public function testUnset(): void {
    $scope = new Scope();
    $scope->definePHP(true, 'i', 1);
    $this->assertEquals(1, $scope->get('i')->toPHPValue());
    $scope->unset('i');
    $this->expectException(FormulaRuntimeException::class);
    $this->expectExceptionMessage('1:0 i is not defined');
    $scope->get('i');
  }

  public function testUnsetInUse(): void {
    $scope = new Scope();
    $scope->definePHP(true, 'i', 1);
    $scope->unset('i'); // no exception
    $scope->definePHP(true, 'i', 1);
    new Formula('1+i', $scope);
    $this->expectException(FormulaBugException::class);
    $this->expectExceptionMessage('Cant unset used variable i');
    $scope->unset('i'); // exception
  }

  public function testDefinePHPNull(): void {
    $scope = new Scope();
    $scope->definePHP(true, 'i', null);
    $this->assertInstanceOf(NullType::class, $scope->use('i'));
  }

  public function testFinal(): void {
    $scope = new Scope();
    $scope->definePHP(true, 'i', 0);
    $this->expectException(FormulaRuntimeException::class);
    $this->expectExceptionMessage('Cant mutate immutable value');
    $scope->assignPHP('i', 2);
  }

  public function testIgnoreFinal(): void {
    $scope = new Scope();
    $scope->definePHP(true, 'i', 0);
    $scope->assignPHP('i', 2, true);
    $this->assertEquals(2, $scope->get('i')->toPHPValue());
  }

  public function testReadUninitilized(): void {
    $scope = new Scope();
    $scope->define(true, new IntegerType(), 'i');
    $this->expectException(ValueUnsetException::class);
    $this->expectExceptionMessage('Property i is unset');
    $scope->get('i');
  }

  public function testToNodeTreeScope(): void {
    $parentScope = new Scope();
    $parentScope->definePHP(false, 'i', 0);
    $childScope = $parentScope->buildChild();
    $scopeArray = $childScope->toNodeTreeScope();
    $expectedScopeArray = ['i' => ['typeName' => 'IntegerType']];
    $this->assertEquals($expectedScopeArray, $scopeArray);
  }

  public function testMergeArguments(): void {
    $function = function (mixed $a) {
      return $a;
    };
    $scope = new Scope();
    $scope->definePHP(false, 'func', $function, ['a' => new IntegerType]);
    $this->expectException(FormulaValidationException::class);
    $this->expectExceptionMessage('1:0 Validation error: Unable to convert boolean to int');
    new Formula("func(false)", $scope);
  }

  public function testNullablePHPFunctionReturnType(): void {
    $function = function (int $a): ?int {
      return $a;
    };
    $scope = new Scope();
    $scope->definePHP(false, 'func', $function);
    $formula = new Formula("func(1)", $scope);
    $this->assertEquals('int|null', $formula->getReturnType()->getIdentifier());
  }

  public function testDateTimeReturn(): void {
    $scope = new Scope();
    $scope->definePHP(true, "func", function (): \DateTimeImmutable {
      return new \DateTimeImmutable("2024-01-01");
    });
    $formula = new Formula('func()', $scope);
    $this->assertInstanceOf(DateTimeImmutableType::class, $formula->getReturnType());
    $this->assertEquals(new \DateTimeImmutable("2024-01-01"), $formula->calculate()->toPHPValue());
  }

  public function testDateIntervalReturn(): void {
    $scope = new Scope();
    $scope->definePHP(true, "func", function (): \DateInterval {
      return new \DateInterval("P1D");
    });
    $formula = new Formula('"2024-01-01" + func()', $scope);
    $this->assertInstanceOf(DateTimeImmutableType::class, $formula->getReturnType());
    $this->assertEquals(new \DateTimeImmutable("2024-01-02"), $formula->calculate()->toPHPValue());
  }

  public function testFunctionReturnsValue(): void {
    $scope = new Scope();
    $scope->definePHP(true, "intValueFunction", intValueFunction(...), null, new IntegerType());
    $formula = new Formula('intValueFunction()', $scope);
    $this->assertInstanceOf(IntegerType::class, $formula->getReturnType());
    $this->assertEquals(123, $formula->calculate()->toPHPValue());
  }
}

function intValueFunction(): IntegerValue {
  return new IntegerValue(123);
}

class ParentClass {

  public int $i = 0;
}

class PHPTestClass extends ParentClass {

  public readonly int $publicReadonlyInt;

  private int $privateInt;

  public array $publicArray;

  public function __construct(int $publicReadonlyInt, int $privateInt, array $publicArray) {
    $this->publicReadonlyInt = $publicReadonlyInt;
    $this->privateInt = $privateInt;
    $this->publicArray = $publicArray;
  }

  public function add(int $a, int $b): int {
    return $a + $b;
  }

  private function privateFunc(int $a, int $b): int {
    return $a + $b;
  }
}
