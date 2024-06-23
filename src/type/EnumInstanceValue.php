<?php
declare(strict_types = 1);
namespace TimoLehnertz\formula\type;

use TimoLehnertz\formula\FormulaBugException;
use TimoLehnertz\formula\operator\ImplementableOperator;
use UnitEnum;

/**
 * @author Timo Lehnertz
 */
class EnumInstanceValue extends Value {

  private readonly UnitEnum $unitEnum;

  public function __construct(UnitEnum $unitEnum) {
    $this->unitEnum = $unitEnum;
  }

  protected function valueOperate(ImplementableOperator $operator, ?Value $other): Value {
    throw new FormulaBugException('Invalid operation');
  }

  public function isTruthy(): bool {
    return true;
  }

  public function valueEquals(Value $other): bool {
    return $other instanceof EnumInstanceValue && $this->unitEnum === $other->unitEnum;
  }

  public function toString(): string {
    return $this->unitEnum->name;
  }

  public function copy(): Value {
    return new EnumInstanceValue($this->unitEnum);
  }

  public function toPHPValue(): mixed {
    return $this->unitEnum;
  }
}
