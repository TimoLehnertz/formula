<?php
declare(strict_types = 1);
namespace TimoLehnertz\formula\type;

use TimoLehnertz\formula\nodes\NodeInterfaceType;
use TimoLehnertz\formula\operator\ImplementableOperator;

/**
 * @author Timo Lehnertz
 */
class CompoundType extends Type {

  /**
   * @var array<Type>
   */
  private readonly array $types;

  private function __construct(array $types) {
    parent::__construct();
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

  // public function getImplementedOperators(): array {
  //   $implemented = [];
  //   foreach ($this->types[0]->getImplementedOperators() as $implementedOperator) {
  //     $implemented[$implementedOperator->getID()] = true;
  //   }
  //   foreach ($this->types as $type) {
  //     foreach ($implemented as $key => $value) {
  //       $implemented[$key] = false;
  //     }
  //     foreach ($type->getImplementedOperators() as $implementedOperator) {
  //       $implemented[$implementedOperator->getID()] = true;
  //     }
  //     $newImplemented = [];
  //     foreach ($implemented as $key => $value) {
  //       if($value) {
  //         $newImplemented[$key] = false;
  //       }
  //     }
  //     $implemented = $newImplemented;
  //   }
  //   $operators = [];
  //   foreach (array_keys($implemented) as $operatorID) {
  //     $operators[] = new ImplementableOperator($operatorID);
  //   }
  //   return $operators;
  // }

  protected function getTypeCompatibleOperands(ImplementableOperator $operator): array {
    $operandLists = [];
    /** @var Type $type */
    foreach($this->types as $type) {
      $operandLists[] = $type->getCompatibleOperands($operator);
    }
    $intersection = $operandLists[0];
    // var_dump('Moin');
    foreach ($operandLists as $list) {
      // var_dump($intersection);
      $intersection = array_uintersect($intersection, $list, function(Type $a, Type $b) {return $a->equals($b);});
      // var_dump($intersection);
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

  public function setFinal(bool $final): Type {
    $changeRequired = false;
    foreach($this->types as $type) {
      if($type->isFinal() !== $final) {
        $changeRequired = true;
        break;
      }
    }
    if(!$changeRequired) {
      return parent::setFinal($final);
    } else {
      $newTypes = [];
      foreach($this->types as $type) {
        $newTypes[] = $type->setFinal($final);
      }
      return (new CompoundType($newTypes))->setFinal($final);
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

