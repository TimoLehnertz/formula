<?php

declare(strict_types=1);

namespace TimoLehnertz\formula\type;

use TimoLehnertz\formula\operator\ImplementableOperator;
use const false;

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

  // public function getImplementedOperators(): array {
  //   return [
  //     new ImplementableOperator(ImplementableOperator::TYPE_ADDITION),
  //     new ImplementableOperator(ImplementableOperator::TYPE_SUBTRACTION),
  //     new ImplementableOperator(ImplementableOperator::TYPE_TYPE_CAST)
  //   ];
  // }

  protected function getTypeCompatibleOperands(ImplementableOperator $operator): array {
    switch ($operator->getID()) {
      case ImplementableOperator::TYPE_ADDITION:
      case ImplementableOperator::TYPE_SUBTRACTION:
        return [new DateIntervalType()];
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
