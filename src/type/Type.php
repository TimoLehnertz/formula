<?php

declare(strict_types=1);

namespace TimoLehnertz\formula\type;

use TimoLehnertz\formula\FormulaBugException;
use TimoLehnertz\formula\FormulaPart;
use TimoLehnertz\formula\FormulaRuntimeException;
use TimoLehnertz\formula\FormulaValidationException;
use TimoLehnertz\formula\PrettyPrintOptions;
use TimoLehnertz\formula\operator\ImplementableOperator;

/**
 * @author Timo Lehnertz
 */
abstract class Type implements OperatorMeta, FormulaPart {

  // final per default
  private bool $assignable = false;

  /**
   * If the values of this type can be restricted to a limited number of specific values, this variable holds these values.
   * Otherwise null.
   * @var array<Value>|null
   */
  private ?array $restrictedValues = null;

  public function __construct() {
  }

  public function getCompatibleOperands(ImplementableOperator $operator): array {
    $array = $this->getTypeCompatibleOperands($operator);
    switch ($operator->getID()) {
      case ImplementableOperator::TYPE_DIRECT_ASSIGNMENT:
      case ImplementableOperator::TYPE_DIRECT_ASSIGNMENT_OLD_VAL:
        if (!$this->assignable) {
          throw new FormulaValidationException('Can\'t assign final value');
        }
        $array[] = $this;
        break;
      case ImplementableOperator::TYPE_EQUALS:
        $array[] = $this;
        $array[] = new NullType();
        break;
      case ImplementableOperator::TYPE_TYPE_CAST:
        foreach ($array as $type) {
          if (!($type instanceof TypeType)) {
            throw new FormulaBugException('Cast operator has to expect TypeType');
          }
        }
        $array[] = new TypeType(new BooleanType());
        $array[] = new TypeType(new StringType());
        break;
      case ImplementableOperator::TYPE_LOGICAL_AND:
        return [new MixedType()];
      case ImplementableOperator::TYPE_LOGICAL_OR:
        return [new MixedType()];
      case ImplementableOperator::TYPE_LOGICAL_XOR:
        return [new MixedType()];
      case ImplementableOperator::TYPE_LOGICAL_NOT:
        return [];
    }
    return $array;
  }

  public function getOperatorResultType(ImplementableOperator $operator, ?Type $otherType): ?Type {
    $type = null;
    switch ($operator->getID()) {
      case ImplementableOperator::TYPE_DIRECT_ASSIGNMENT:
      case ImplementableOperator::TYPE_DIRECT_ASSIGNMENT_OLD_VAL:
        if (!$this->assignable) {
          return null;
        }
        if ($otherType === null || !$this->assignableBy($otherType)) {
          break;
        }
        $type = $this->setAssignable(false); // result of operation is r value
        break;
      case ImplementableOperator::TYPE_EQUALS:
        if ($otherType === null || (!$this->assignableBy($otherType) && !($otherType instanceof NullType))) {
          break;
        }
        $type = new BooleanType();
      case ImplementableOperator::TYPE_TYPE_CAST:
        if ($otherType instanceof TypeType) {
          if ($otherType->getType() instanceof BooleanType) {
            $type = new BooleanType();
            break;
          }
          if ($otherType->getType()->equals(new StringType())) {
            $type = new StringType();
            break;
          }
        }
        break;
      case ImplementableOperator::TYPE_LOGICAL_AND:
      case ImplementableOperator::TYPE_LOGICAL_OR:
      case ImplementableOperator::TYPE_LOGICAL_XOR:
        if ($otherType !== null) {
          $type = new BooleanType();
        }
        break;
      case ImplementableOperator::TYPE_LOGICAL_NOT:
        if($otherType === null) {
          $type = new BooleanType();
        }
        break;
    }
    if($type === null) {
      $type = $this->getTypeOperatorResultType($operator, $otherType);
    }
    if($type !== null && $this->restrictedValues !== null) {
      $otherRestrictedValues = $otherType?->getRestrictedValues();
      if($otherType === null) {
        $restrictedValues = [];
        foreach ($this->restrictedValues as $value) {
          try {
            $restrictedValues[] = $value->operate($operator, null);
          } catch(FormulaRuntimeException $e) { // catch division by zero and similar
            $restrictedValues = null;
            break;
          }
        }
        $type = $type->setRestrictedValues($restrictedValues);
      } else if($otherRestrictedValues !== null && count($otherRestrictedValues) === 1 && count($this->restrictedValues) === 1) {
        $valueA = $this->restrictedValues[0];
        $valueB = $otherRestrictedValues[0];
        try {
          $type = $type->setRestrictedValues([$valueA->operate($operator, $valueB)]);
        } catch(FormulaRuntimeException $e) { // catch division by zero and similar
          $type = $type->setRestrictedValues(null);
        }
      } else {
        $type = $type->setRestrictedValues(null);
      }
    }
    return $type;
  }

  public function setAssignable(bool $assignable): Type {
    $clone = clone $this;
    $clone->assignable = $assignable;
    if($assignable) {
      $clone->restrictedValues = null;
    }
    return $clone;
  }

  public function isAssignable(): bool {
    return $this->assignable;
  }

  public function setRestrictedValues(?array $restrictedValues): Type {
    if($this->assignable) {
      throw new \UnexpectedValueException('Assignable type can\'t have value restrictions');
    }
    $clone = clone $this;
    $clone->restrictedValues = $restrictedValues;
    return $clone;
  }

  public function getRestrictedValues(): ?array {
    return $this->restrictedValues;
  }

  /**
   * @return array<Type>
   */
  protected abstract function getTypeCompatibleOperands(ImplementableOperator $operator): array;

  protected abstract function getTypeOperatorResultType(ImplementableOperator $operator, ?Type $otherType): ?Type;

  /**
   * @return string a unique identifier for this type. Equal identifier => equal type
   */
  public abstract function getIdentifier(bool $nested = false): string;

  public abstract function equals(Type $type): bool;

  public function assignableBy(Type $type): bool {
    return $this->typeAssignableBy($type);
  }

  protected function getProperties(): ?array {
    return null;
  }

  public function getInterfaceType(): array {
    $reflection = new \ReflectionClass($this::class);
    $properties = $this->getProperties();
    if ($properties === null) {
      return ['typeName' => $reflection->getShortName()];
    } else {
      return ['typeName' => $reflection->getShortName(), 'properties' => $properties];
    }
  }

  protected abstract function typeAssignableBy(Type $type): bool;

  public function toString(PrettyPrintOptions $prettyPrintOptions): string {
    return $this->getIdentifier();
  }
}
