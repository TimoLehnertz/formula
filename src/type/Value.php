<?php
declare(strict_types = 1);
namespace TimoLehnertz\formula\type;

use TimoLehnertz\formula\PrettyPrintOptions;
use TimoLehnertz\formula\operator\ImplementableOperator;
use TimoLehnertz\formula\operator\Operator;
use TimoLehnertz\formula\operator\TypeCastOperator;

/**
 * @author Timo Lehnertz
 */
abstract class Value implements OperatorHandler {

  public function getCompatibleOperands(ImplementableOperator $operator): array {
    $array = $this->getValueExpectedOperands($operator);
    switch($operator->id) {
      case Operator::IMPLEMENTABLE_DIRECT_ASSIGNMENT:
        $array[] = $this->getType();
        break;
      case Operator::IMPLEMENTABLE_EQUALS:
        $array[] = $this->getType();
        break;
      case Operator::IMPLEMENTABLE_TYPE_CAST:
        $array[] = new BooleanType();
        $array[] = $this->getType();
    }
    return $array;
  }

  public function getOperatorResultType(ImplementableOperator $operator, ?Type $otherType): ?Type {
    $type = $this->getValueOperatorResultType($operator, $otherType);
    if($type !== null) {
      return $type;
    }
    // default operators
    switch($operator->id) {
      case Operator::IMPLEMENTABLE_DIRECT_ASSIGNMENT:
        if($otherType === null || !$this->getType()->assignableBy($otherType)) {
          return null;
        }
        return $this->getType();
      case Operator::IMPLEMENTABLE_EQUALS:
        if($otherType === null || !$this->getType()->assignableBy($otherType)) {
          return null;
        }
        return new BooleanType();
      case Operator::IMPLEMENTABLE_TYPE_CAST:
        if($operator instanceof TypeCastOperator) {
          if($operator->getCastType() instanceof BooleanType) {
            return new BooleanType();
          }
          if($operator->getCastType()->equals($this->getType())) {
            return $operator->getCastType();
          }
        }
    }
    return null;
  }

  public function operate(ImplementableOperator $operator, ?Value $other): Value {
    // default operators
    switch($operator->id) {
      case Operator::IMPLEMENTABLE_DIRECT_ASSIGNMENT:
        if($this->getType()->assignableBy($other->getType())) {
          $this->assign($other);
          return $this->copy();
        }
        break;
      case Operator::IMPLEMENTABLE_EQUALS:
        if($this->getType()->assignableBy($other->getType())) {
          return new BooleanValue($this->valueEquals($other));
        }
        break;
      case Operator::IMPLEMENTABLE_TYPE_CAST:
        if($operator instanceof TypeCastOperator) {
          if($operator->getCastType() instanceof BooleanType) {
            return new BooleanValue($this->isTruthy());
          }
          if($operator->getCastType()->equals($this->getType())) {
            return $this;
          }
        }
    }
    return $this->valueOperate($operator, $other);
  }

  /**
   * @param Value $value guaranteed to be assignable
   */
  public abstract function assign(Value $value): void;

  public abstract function getType(): Type;

  /**
   * Everything should be truthy except for false and nullish values
   */
  public abstract function isTruthy(): bool;

  public abstract function copy(): Value;

  public abstract function toString(PrettyPrintOptions $prettyPrintOptions): string;

  protected abstract function getValueOperatorResultType(ImplementableOperator $operator, ?Type $otherType): ?Type;

  protected abstract function valueOperate(ImplementableOperator $operator, ?Value $other): Value;

  /**
   * Returns the expected types for the operator or an empty array if the operator doesnt exist
   * @param ImplementableOperator $operator
   * @return array<Type>
   */
  protected abstract function getValueExpectedOperands(ImplementableOperator $operator): array;

  /**
   * @param Value $other guaranteed to be assignable
   */
  protected abstract function valueEquals(Value $other): bool;
}
