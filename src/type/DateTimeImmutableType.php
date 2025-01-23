<?php

declare(strict_types=1);

namespace TimoLehnertz\formula\type;

use TimoLehnertz\formula\operator\ImplementableOperator;

/**
 * @author Timo Lehnertz
 */
class DateTimeImmutableType extends Type {

  protected function typeAssignableBy(Type $type): bool {
    return $this->equals($type);
  }

  public function equals(Type $type): bool {
    return $type instanceof DateTimeImmutableType;
  }

  protected function getTypeCompatibleOperands(ImplementableOperator $operator): array {
    switch ($operator->getID()) {
      case ImplementableOperator::TYPE_ADDITION:
      case ImplementableOperator::TYPE_SUBTRACTION:
        return [new DateIntervalType()];
        case ImplementableOperator::TYPE_GREATER:
        case ImplementableOperator::TYPE_LESS:
        return [new DateTimeImmutableType()];
      case ImplementableOperator::TYPE_TYPE_CAST:
        return [new TypeType(new IntegerType())];
      default:
        return [];
    }
  }

  protected function getTypeOperatorResultType(ImplementableOperator $operator, ?Type $otherType): ?Type {
    switch ($operator->getID()) {
      case ImplementableOperator::TYPE_ADDITION:
      case ImplementableOperator::TYPE_SUBTRACTION:
        if ($otherType instanceof DateIntervalType) {
          return new DateTimeImmutableType();
        }
        break;
      case ImplementableOperator::TYPE_GREATER:
      case ImplementableOperator::TYPE_LESS:
        if ($otherType instanceof DateTimeImmutableType) {
          return new BooleanType();
        }
        break;
      case ImplementableOperator::TYPE_TYPE_CAST:
        if ($otherType instanceof TypeType && $otherType->getType() instanceof IntegerType) {
          return new IntegerType();
        }
        break;
    }
    return null;
  }

  public function getIdentifier(bool $nested = false): string {
    return 'DateTimeImmutable';
  }
}
