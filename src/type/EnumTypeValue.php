<?php
declare(strict_types = 1);
namespace TimoLehnertz\formula\type;

use TimoLehnertz\formula\type\classes\ClassInstanceValue;
use TimoLehnertz\formula\type\classes\FieldValue;
use ReflectionEnumBackedCase;
use ReflectionEnumUnitCase;

/**
 * @author Timo Lehnertz
 */
class EnumTypeValue extends ClassInstanceValue {

  private readonly \ReflectionEnum $reflection;

  public function __construct(\ReflectionEnum $reflection) {
    $fields = [];
    /**  @var ReflectionEnumUnitCase|ReflectionEnumBackedCase $enumCase */
    foreach($reflection->getCases() as $enumCase) {
      $fields[$enumCase->getName()] = new FieldValue(new EnumInstanceValue($enumCase->getValue()));
    }
    parent::__construct($fields);
  }

  public function valueEquals(Value $other): bool {
    return $other instanceof EnumTypeValue && $this->reflection->getName() === $other->getName();
  }

  public function copy(): Value {
    return new EnumTypeValue($this->reflection);
  }

  public function toString(): string {
    return 'EnumType';
  }
}
