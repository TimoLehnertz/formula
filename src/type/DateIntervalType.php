<?php

declare(strict_types=1);

namespace TimoLehnertz\formula\type;

use TimoLehnertz\formula\operator\ImplementableOperator;

/**
 * @author Timo Lehnertz
 */
class DateIntervalType extends Type {

  protected function typeAssignableBy(Type $type): bool {
    return $this->equals($type);
  }

  public function equals(Type $type): bool {
    return $type instanceof DateIntervalType;
  }

  protected function getTypeCompatibleOperands(ImplementableOperator $operator): array {
    switch ($operator->getID()) {
      case ImplementableOperator::TYPE_TYPE_CAST:
        return [new TypeType(new IntegerType())];
    }
    return [];
  }

  public function getIdentifier(bool $nested = false): string {
    return 'DateInterval';
  }

  protected function getTypeOperatorResultType(ImplementableOperator $operator, ?Type $otherType): ?Type {
    switch ($operator->getID()) {
      case ImplementableOperator::TYPE_UNARY_PLUS:
        return new DateIntervalType();
      case ImplementableOperator::TYPE_UNARY_MINUS:
        return new DateIntervalType();
      case ImplementableOperator::TYPE_TYPE_CAST:
        if ($otherType instanceof TypeType && $otherType->getType() instanceof IntegerType) {
          return new IntegerType();
        }
        break;
    }
    return null;
  }
}
