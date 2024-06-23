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

  // public function getImplementedOperators(): array {
  //   return [];
  // }

  protected function getTypeCompatibleOperands(ImplementableOperator $operator): array {
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
    }
    return null;
  }
}
