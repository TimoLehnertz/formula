<?php
declare(strict_types = 1);
namespace TimoLehnertz\formula\type;

use TimoLehnertz\formula\operator\ImplementableOperator;

/**
 * @author Timo Lehnertz
 */
class EnumInstanceType extends Type {

  private readonly EnumTypeType $enumType;

  public function __construct(EnumTypeType $enumType) {
    $this->enumType = $enumType;
  }

  protected function typeAssignableBy(Type $type): bool {
    return $type instanceof EnumInstanceType && $this->enumType->equals($type->enumType);
  }

  public function typeEquals(Type $type): bool {
    return $type instanceof EnumInstanceType && $this->enumType->equals($type->enumType);
  }

  protected function getTypeCompatibleOperands(ImplementableOperator $operator): array {
    return [];
  }

  protected function getTypeOperatorResultType(ImplementableOperator $operator, ?Type $otherType): ?Type {
    return null;
  }

  public function getIdentifier(bool $isNested = false): string {
    return 'EnumInstanceType('.$this->enumType->getIdentifier().')';
  }

  protected function getProperties(): ?array {
    return ['enumType' => $this->enumType->getInterfaceType()];
  }
}
