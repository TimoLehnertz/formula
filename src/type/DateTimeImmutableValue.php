<?php

declare(strict_types=1);

namespace TimoLehnertz\formula\type;

use TimoLehnertz\formula\operator\ImplementableOperator;
use TimoLehnertz\formula\FormulaBugException;

/**
 * @author Timo Lehnertz
 */
class DateTimeImmutableValue extends Value {

  private readonly \DateTimeImmutable $value;

  public function __construct(\DateTimeImmutable $value) {
    $this->value = $value;
  }

  protected function valueOperate(ImplementableOperator $operator, ?Value $other): Value {
    switch ($operator->getID()) {
      case ImplementableOperator::TYPE_ADDITION:
        if ($other instanceof DateIntervalValue) {
          return new DateTimeImmutableValue($this->value->add($other->toPHPValue()));
        }
        break;
      case ImplementableOperator::TYPE_SUBTRACTION:
        if ($other instanceof DateIntervalValue) {
          return new DateTimeImmutableValue($this->value->sub($other->toPHPValue()));
        }
        break;
      case ImplementableOperator::TYPE_TYPE_CAST:
        if ($other instanceof TypeValue && $other->getValue() instanceof IntegerType) {
          return new IntegerValue($this->value->getTimestamp());
        }
        break;
    }
    throw new FormulaBugException('Invalid Operator');
  }

  public function toString(): string {
    return $this->value->format('Y-m-d\TH:i:s');
  }

  public function toPHPValue(): \DateTimeImmutable {
    return $this->value;
  }

  public function copy(): Value {
    return new DateTimeImmutableValue($this->value);
  }

  public function isTruthy(): bool {
    return true;
  }

  public function valueEquals(Value $other): bool {
    if ($other instanceof DateTimeImmutableValue) {
      return $this->value == $other->value;
    }
    return false;
  }
}
