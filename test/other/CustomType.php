<?php

declare(strict_types=1);

namespace test\other;

use TimoLehnertz\formula\operator\ImplementableOperator;
use TimoLehnertz\formula\type\Type;

/**
 * @author Timo Lehnertz
 */
class CustomType extends Type {

  protected function typeAssignableBy(Type $type): bool {
    return $this->equals($type);
  }

  public function typeEquals(Type $type): bool {
    return $type instanceof CustomType;
  }

  public function getIdentifier(bool $nested = false): string {
    return 'CustomType';
  }

  public function getTypeCompatibleOperands(ImplementableOperator $operator): array {
    return [];
  }

  public function getTypeOperatorResultType(ImplementableOperator $operator, ?Type $otherType): ?Type {
    return null;
  }
}
