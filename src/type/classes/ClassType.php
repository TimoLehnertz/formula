<?php
declare(strict_types = 1);
namespace TimoLehnertz\formula\type\classes;

use TimoLehnertz\formula\operator\ImplementableOperator;
use TimoLehnertz\formula\type\MemberAccsessType;
use TimoLehnertz\formula\type\Type;

/**
 * @author Timo Lehnertz
 */
class ClassType extends Type {

  protected readonly ?ClassType $parentType;

  protected readonly string $identifier;

  /**
   * @var array<string, FieldType>
   */
  protected readonly array $fields;

  /**
   * @param array<string, FieldType> $fields
   */
  public function __construct(?ClassType $parentType, string $identifier, array $fields, ?array $restrictedValues = null) {
    parent::__construct($restrictedValues);
    $this->parentType = $parentType;
    $this->identifier = $identifier;
    if($this->parentType !== null) {
      $this->fields = array_merge($fields, $this->parentType->fields);
    } else {
      $this->fields = $fields;
    }
  }

  protected function typeAssignableBy(Type $type): bool {
    if(!($type instanceof ClassType)) {
      return false;
    }
    return $this->equals($type) || $type->extends($this);
  }

  public function extends(ClassType $classType): bool {
    if($this->parentType === null) {
      return false;
    }
    if($this->parentType->equals($classType)) {
      return true;
    }
    return $this->parentType->extends($classType);
  }

  public function typeEquals(Type $type): bool {
    if(!($type instanceof ClassType)) {
      return false;
    }
    return $this->identifier === $type->identifier;
  }

  public function getIdentifier(bool $isNested = false): string {
    return 'classType('.$this->identifier.')';
  }

  // public function getImplementedOperators(): array {
  //   return [new ImplementableOperator(ImplementableOperator::TYPE_MEMBER_ACCESS)];
  // }

  protected function getTypeCompatibleOperands(ImplementableOperator $operator): array {
    switch($operator->getID()) {
      case ImplementableOperator::TYPE_MEMBER_ACCESS:
        $compatible = [];
        foreach(array_keys($this->fields) as $identifier) {
          $compatible[] = new MemberAccsessType($identifier);
        }
        return $compatible;
      default:
        return [];
    }
  }

  protected function getTypeOperatorResultType(ImplementableOperator $operator, ?Type $otherType): ?Type {
    switch($operator->getID()) {
      case ImplementableOperator::TYPE_MEMBER_ACCESS:
        if(!($otherType instanceof MemberAccsessType)) {
          break;
        }
        if(!isset($this->fields[$otherType->getMemberIdentifier()])) {
          break;
        }
        return $this->fields[$otherType->getMemberIdentifier()]->type;
    }
    return null;
  }

  protected function getProperties(): ?array {
    $fields = [];
    foreach ($this->fields as $identifier => $field) {
      $fields [] = [
        'identifier' => $identifier,
        'type' => $field->type->getIdentifier(),
        'final' => $field->final,
      ];
    }
    return ['parentType' => $this->parentType?->getInterfaceType() ?? null, 'fields' => $fields, 'identifier' => $this->identifier];
  }
}
