<?php

declare(strict_types=1);

namespace test\other;

use TimoLehnertz\formula\FormulaBugException;
use TimoLehnertz\formula\operator\ImplementableOperator;
use TimoLehnertz\formula\type\Value;

/**
 * @author Timo Lehnertz
 */
class CustomValue extends Value {

  private readonly int $value;

  public function __construct(int $value) {
    $this->value = $value;
  }

  public function valueOperate(ImplementableOperator $operator, ?Value $other): Value {
    throw new FormulaBugException('Bad operation');
  }

  public function isTruthy(): bool {
    return true;
  }

  public function valueEquals(Value $other): bool {
    return $other instanceof CustomValue && $other->value === $this->value;
  }

  public function toString(): string {
    return $this->value . '';
  }

  public function copy(): Value {
    return new CustomValue($this->value);
  }

  public function toPHPValue(): mixed {
    return $this->value;
  }
}
