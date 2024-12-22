<?php
declare(strict_types = 1);
namespace TimoLehnertz\formula\type;

use TimoLehnertz\formula\operator\ImplementableOperator;

/**
 * @author Timo Lehnertz
 */
class CompoundType extends Type {

  /**
   * @var array<Type>
   */
  private readonly array $types;

  /**
   * @param array<Type> $types
   */
  private function __construct(array $types) {
    $restrictedValues = [];
    foreach ($types as $type) {
      if($type->getRestrictedValues() !== null) {
        $restrictedValues = array_merge($restrictedValues, $type->getRestrictedValues());
      } else {
        $restrictedValues = null;
        break;
      }
    }
    parent::__construct($restrictedValues);
    $this->types = $types;
  }

  public static function buildFromTypes(array $types): Type {
    if(count($types) === 0) {
      return new NeverType();
    }
    // flatten
    $notCompoundTypes = [];
    foreach($types as $type) {
      if($type instanceof CompoundType) {
        $notCompoundTypes = array_merge($notCompoundTypes, $type->types);
      } else {
        $notCompoundTypes[] = $type;
      }
    }
    $uniqueTypes = [];
    // eliminate clones
    foreach($notCompoundTypes as $type) {
      $found = false;
      foreach($uniqueTypes as $uniqueType) {
        if($uniqueType->equals($type)) {
          $found = true;
          break;
        }
      }
      if(!$found) {
        $uniqueTypes[] = $type;
      }
    }
    if(count($uniqueTypes) === 1) {
      return $uniqueTypes[0];
    } else {
      return new CompoundType($uniqueTypes);
    }
  }

  protected function getTypeCompatibleOperands(ImplementableOperator $operator): array {
    $operandLists = [];
    /** @var Type $type */
    foreach($this->types as $type) {
      $operandLists[] = $type->getCompatibleOperands($operator);
    }
    $intersection = $operandLists[0];
    foreach ($operandLists as $list) {
      $intersection = array_uintersect($intersection, $list, function(Type $a, Type $b) {return $a->equals($b) ? 0 : -1;});
    }
    return $intersection;
  }

  protected function getTypeOperatorResultType(ImplementableOperator $operator, ?Type $otherType): ?Type {
    $resultTypes = [];
    foreach($this->types as $type) {
      $result = $type->getOperatorResultType($operator, $otherType);
      if($result !== null) {
        $resultTypes[] = $result;
      }
    }
    return CompoundType::buildFromTypes($resultTypes);
  }

  public function getIdentifier(bool $nested = false): string {
    $identifier = '';
    $delimiter = '';
    foreach($this->types as $type) {
      $identifier .= $delimiter.$type->getIdentifier(true);
      $delimiter = '|';
    }
    if($nested) {
      return '('.$identifier.')';
    } else {
      return $identifier;
    }
  }

  protected function typeAssignableBy(Type $type): bool {
    if($type instanceof CompoundType) {
      foreach($type->types as $otherType) {
        if(!$this->assignableBy($otherType)) {
          return false;
        }
      }
      return true;
    } else {
      foreach($this->types as $ownType) {
        if($ownType->assignableBy($type, true)) {
          return true;
        }
      }
      return false;
    }
  }

  public function equals(Type $type): bool {
    if($type instanceof CompoundType) {
      if(count($type->types) !== count($this->types)) {
        return false;
      }
      foreach($type->types as $otherType) {
        $found = false;
        foreach($this->types as $ownType) {
          if($ownType->equals($otherType)) {
            $found = true;
            break;
          }
        }
        if(!$found) {
          return false;
        }
      }
      return true;
    } else {
      return false;
    }
  }

  public function eliminateType(Type $type): Type {
    $newTypes = [];
    foreach($this->types as $ownType) {
      if(!$ownType->equals($type)) {
        $newTypes[] = $ownType;
      }
    }
    return CompoundType::buildFromTypes($newTypes);
  }

  public function setAssignable(bool $assignable): Type {
    $changeRequired = false;
    foreach($this->types as $type) {
      if($type->isAssignable() !== $assignable) {
        $changeRequired = true;
        break;
      }
    }
    if(!$changeRequired) {
      return parent::setAssignable($assignable);
    } else {
      $newTypes = [];
      foreach($this->types as $type) {
        $newTypes[] = $type->setAssignable($assignable);
      }
      return (new CompoundType($newTypes))->setAssignable($assignable);
    }
  }

  protected function getProperties(): ?array {
    $types = [];
    foreach ($this->types as $type) {
      $types[] = $type->getInterfaceType();
    }
    return ['types' => $types];
  }
}

