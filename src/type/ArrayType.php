<?php

declare(strict_types=1);

namespace TimoLehnertz\formula\type;

use TimoLehnertz\formula\operator\ImplementableOperator;
use TimoLehnertz\formula\type\classes\ClassType;
use TimoLehnertz\formula\type\classes\FieldType;

/**
 * @author Timo Lehnertz
 */
class ArrayType extends ClassType implements IteratableType {

  private Type $keyType;

  private Type $elementsType;

  public function __construct(Type $keyType, Type $elementsType) {
    parent::__construct(null, 'array', ['length' => new FieldType(true, new IntegerType())]);
    $this->keyType = $keyType->setAssignable(true);
    $this->elementsType = $elementsType->setAssignable(true);
  }

  protected function typeAssignableBy(Type $type): bool {
    if (!($type instanceof ArrayType)) {
      return false;
    }
    $keysCompatible = $this->keyType->assignableBy($type->keyType, true) || ($type->keyType instanceof NeverType);
    $elementsCompatible = $this->elementsType->assignableBy($type->elementsType, true) || ($type->elementsType instanceof NeverType);
    return $keysCompatible && $elementsCompatible;
  }

  public function equals(Type $type): bool {
    if (!($type instanceof ArrayType)) {
      return false;
    }
    return $this->keyType->equals($type->keyType) && $this->elementsType->equals($type->elementsType);
  }

  public function getIdentifier(bool $isNested = false): string {
    if ($this->keyType instanceof IntegerType) {
      return $this->elementsType->getIdentifier(true) . '[]';
    } else {
      return 'array<' . $this->keyType->getIdentifier() . ',' . $this->elementsType->getIdentifier() . '>';
    }
  }

  // public function getImplementedOperators(): array {
  //   return [
  //     new ImplementableOperator(ImplementableOperator::TYPE_ARRAY_ACCESS),
  //     new ImplementableOperator(ImplementableOperator::TYPE_MEMBER_ACCESS),
  //     new ImplementableOperator(ImplementableOperator::TYPE_ADDITION),
  //     new ImplementableOperator(ImplementableOperator::TYPE_SUBTRACTION),
  //     new ImplementableOperator(ImplementableOperator::TYPE_MULTIPLICATION),
  //     new ImplementableOperator(ImplementableOperator::TYPE_DIVISION),
  //     new ImplementableOperator(ImplementableOperator::TYPE_UNARY_PLUS),
  //     new ImplementableOperator(ImplementableOperator::TYPE_UNARY_MINUS),
  //     new ImplementableOperator(ImplementableOperator::TYPE_MODULO),
  //     new ImplementableOperator(ImplementableOperator::TYPE_TYPE_CAST),
  //   ];
  // }

  protected function getTypeCompatibleOperands(ImplementableOperator $operator): array {
    switch ($operator->getID()) {
      case ImplementableOperator::TYPE_ARRAY_ACCESS:
        return [$this->keyType];
      case ImplementableOperator::TYPE_MEMBER_ACCESS:
        return parent::getTypeCompatibleOperands($operator);
      case ImplementableOperator::TYPE_ADDITION:
      case ImplementableOperator::TYPE_SUBTRACTION:
      case ImplementableOperator::TYPE_MULTIPLICATION:
      case ImplementableOperator::TYPE_DIVISION:
      case ImplementableOperator::TYPE_UNARY_PLUS:
      case ImplementableOperator::TYPE_UNARY_MINUS:
      case ImplementableOperator::TYPE_MODULO:
        $operands = $this->elementsType->getCompatibleOperands($operator);
        return [...$operands, new ArrayType($this->keyType, CompoundType::buildFromTypes($operands))];
      case ImplementableOperator::TYPE_TYPE_CAST:
        $elementCasts = $this->elementsType->getCompatibleOperands($operator);
        $arrayCasts = [];
        foreach ($elementCasts as $elementCast) {
          if ($elementCast instanceof TypeType) {
            $arrayCasts[] = new TypeType(new ArrayType($this->keyType, $elementCast->getType()));
          }
        }
        return $arrayCasts;
    }
    return [];
  }

  protected function getTypeOperatorResultType(ImplementableOperator $operator, ?Type $otherType): ?Type {
    switch ($operator->getID()) {
      case ImplementableOperator::TYPE_ARRAY_ACCESS:
        if ($otherType !== null && $this->keyType->assignableBy($otherType)) {
          return $this->elementsType;
        }
        break;
      case ImplementableOperator::TYPE_MEMBER_ACCESS:
        return parent::getTypeOperatorResultType($operator, $otherType);
      case ImplementableOperator::TYPE_ADDITION:
      case ImplementableOperator::TYPE_SUBTRACTION:
      case ImplementableOperator::TYPE_MULTIPLICATION:
      case ImplementableOperator::TYPE_DIVISION:
      case ImplementableOperator::TYPE_UNARY_PLUS:
      case ImplementableOperator::TYPE_UNARY_MINUS:
      case ImplementableOperator::TYPE_MODULO:
        if ($otherType instanceof ArrayType) {
          $otherType = $otherType->elementsType;
        }
        $result = $this->elementsType->getOperatorResultType($operator, $otherType);
        if ($result !== null) {
          return new ArrayType($this->keyType, $result);
        }
        break;
      case ImplementableOperator::TYPE_TYPE_CAST:
        if (($otherType instanceof TypeType) && $otherType->getType() instanceof ArrayType) {
          $result = $this->elementsType->getOperatorResultType($operator, new TypeType($otherType->getType()->elementsType));
          if ($result !== null) {
            return new ArrayType($this->keyType, $result);
          }
        }
        break;
    }
    return null;
  }

  public function getKeyType(): Type {
    return $this->keyType;
  }

  public function getElementsType(): Type {
    return $this->elementsType;
  }

  protected function getProperties(): ?array {
    return ['keyType' => $this->keyType->getInterfaceType(), 'elementsType' => $this->elementsType->getInterfaceType()];
  }
}
