<?php

namespace test\other;

use PHPUnit\Framework\TestCase;
use TimoLehnertz\formula\Formula;
use TimoLehnertz\formula\FormulaBugException;
use TimoLehnertz\formula\operator\ImplementableOperator;
use TimoLehnertz\formula\procedure\Scope;
use TimoLehnertz\formula\type\ArrayType;
use TimoLehnertz\formula\type\ArrayValue;
use TimoLehnertz\formula\type\FloatType;
use TimoLehnertz\formula\type\FloatValue;
use TimoLehnertz\formula\type\IntegerType;
use TimoLehnertz\formula\type\Type;
use TimoLehnertz\formula\type\TypeType;
use TimoLehnertz\formula\type\TypeValue;
use TimoLehnertz\formula\type\Value;

class CustomTypeTest extends TestCase {

  public function bar(): CustomArrayValue {
    $array = [];
    for ($i=-1000; $i <= 1000; $i++) { 
      $array[] = $i;
    }
    return new CustomArrayValue($array);
  }

  public function foo(CustomArrayValue $value): CustomArrayValue {
    return new CustomArrayValue(array_reverse($value->getValue()));
  }

  public function testFunctionTakesArgument() {
    $scope = new Scope();
    $scope->definePHP(true, 'bar', $this->bar(...), null, new CustomArrayType());
    $scope->definePHP(true, 'foo', $this->foo(...), ['value' => new CustomArrayType()], new CustomArrayType());
    $formula = new Formula('foo(bar())', $scope);
    $resultArray = $formula->calculate()->toPHPValue()->getValue();
    $this->assertCount(2001, $resultArray);
  }

  public function testTypeCast() {
    $scope = new Scope();
    $scope->definePHP(true, 'bar', $this->bar(...), null, new CustomArrayType());
    $scope->definePHP(true, 'foo', $this->foo(...), ['value' => new CustomArrayType()], new CustomArrayType());
    $formula = new Formula('avg(bar())', $scope);
    $this->assertEquals(0, $formula->calculate()->toPHPValue());
  }
}


class CustomArrayType extends Type {

  protected function typeAssignableBy(Type $type): bool {
    return $this->equals($type);
  }

  public function equals(Type $type): bool {
    return $type instanceof CustomArrayType;
  }

  public function getIdentifier(bool $nested = false): string {
    return 'CustomArray';
  }

  public function getTypeCompatibleOperands(ImplementableOperator $operator): array {
    switch ($operator->getID()) {
      case ImplementableOperator::TYPE_TYPE_CAST:
        return [new TypeType(new ArrayType(new IntegerType(), new FloatType()))];
    }
    return [];
  }

  public function getTypeOperatorResultType(ImplementableOperator $operator, ?Type $otherType): ?Type {
    switch ($operator->getID()) {
      case ImplementableOperator::TYPE_TYPE_CAST:
        if ($otherType === null || !($otherType instanceof TypeType)) {
          return null;
        }
        return new ArrayType(new IntegerType(), new FloatType());
    }
    return null;
  }
}

class CustomArrayValue extends Value {

  private readonly array $value;

  public function __construct(array $value) {
    $this->value = $value;
  }

  public function valueOperate(ImplementableOperator $operator, ?Value $other): Value {
    switch($operator->getID()) {
      case ImplementableOperator::TYPE_TYPE_CAST:
        if($other === null || !($other instanceof TypeValue) || !$other->getValue() instanceof ArrayType) {
          break;
        }
        $values = [];
        foreach ($this->value as $key => $value) {
          $values[$key] = new FloatValue($value);
        }
        return new ArrayValue($values);
    }
  }

  public function isTruthy(): bool {
    return true;
  }

  public function valueEquals(Value $other): bool {
    return $other instanceof CustomArrayValue && $other->value === $this->value;
  }

  public function toString(): string {
    return $this->value . '';
  }

  public function copy(): Value {
    return new CustomArrayValue($this->value);
  }

  public function toPHPValue(): mixed {
    return $this;
  }

  public function getValue(): array {
    return $this->value;
  }
}
