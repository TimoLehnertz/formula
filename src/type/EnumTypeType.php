<?php

declare(strict_types=1);

namespace TimoLehnertz\formula\type;

use ReflectionEnumBackedCase;
use ReflectionEnumUnitCase;
use TimoLehnertz\formula\type\classes\ClassType;
use TimoLehnertz\formula\type\classes\FieldType;

/**
 * @author Timo Lehnertz
 */
class EnumTypeType extends ClassType {

  private readonly \ReflectionEnum $reflection;

  public function __construct(\ReflectionEnum $reflection) {
    $fields = [];
    /**  @var \ReflectionEnumUnitCase|\ReflectionEnumBackedCase $enumCase */
    foreach ($reflection->getCases() as $enumCase) {
      $fields[$enumCase->getName()] = new FieldType(true, new EnumInstanceType($this));
    }
    parent::__construct(null, $reflection->getName(), $fields, [new EnumTypeValue($reflection)]);
    $this->reflection = $reflection;
  }

  protected function typeAssignableBy(Type $type): bool {
    return $this->equals($type);
  }

  public function getIdentifier(bool $isNested = false): string {
    return 'EnumTypeType(' . $this->reflection->getName() . ')';
  }
}
