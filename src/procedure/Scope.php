<?php

declare(strict_types=1);

namespace TimoLehnertz\formula\procedure;

use TimoLehnertz\formula\FormulaRuntimeException;
use TimoLehnertz\formula\nodes\NodeTreeScope;
use TimoLehnertz\formula\type\ArrayType;
use TimoLehnertz\formula\type\ArrayValue;
use TimoLehnertz\formula\type\BooleanType;
use TimoLehnertz\formula\type\BooleanValue;
use TimoLehnertz\formula\type\CompoundType;
use TimoLehnertz\formula\type\DateIntervalType;
use TimoLehnertz\formula\type\DateIntervalValue;
use TimoLehnertz\formula\type\DateTimeImmutableType;
use TimoLehnertz\formula\type\DateTimeImmutableValue;
use TimoLehnertz\formula\type\FloatType;
use TimoLehnertz\formula\type\FloatValue;
use TimoLehnertz\formula\type\IntegerType;
use TimoLehnertz\formula\type\IntegerValue;
use TimoLehnertz\formula\type\MixedType;
use TimoLehnertz\formula\type\NullType;
use TimoLehnertz\formula\type\NullValue;
use TimoLehnertz\formula\type\StringType;
use TimoLehnertz\formula\type\StringValue;
use TimoLehnertz\formula\type\Type;
use TimoLehnertz\formula\type\Value;
use TimoLehnertz\formula\type\VoidType;
use TimoLehnertz\formula\type\classes\ClassType;
use TimoLehnertz\formula\type\classes\ClassTypeType;
use TimoLehnertz\formula\type\classes\ClassTypeValue;
use TimoLehnertz\formula\type\classes\ConstructorType;
use TimoLehnertz\formula\type\classes\ConstructorValue;
use TimoLehnertz\formula\type\classes\FieldType;
use TimoLehnertz\formula\type\classes\PHPClassInstanceValue;
use TimoLehnertz\formula\type\functions\FunctionType;
use TimoLehnertz\formula\type\functions\FunctionValue;
use TimoLehnertz\formula\type\functions\OuterFunctionArgument;
use TimoLehnertz\formula\type\functions\OuterFunctionArgumentListType;
use TimoLehnertz\formula\type\functions\PHPFunctionBody;
use TimoLehnertz\formula\type\EnumInstanceType;
use TimoLehnertz\formula\type\EnumTypeType;
use TimoLehnertz\formula\type\EnumInstanceValue;
use TimoLehnertz\formula\type\EnumTypeValue;
use TimoLehnertz\formula\type\NeverType;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;

/**
 * @author Timo Lehnertz
 */
class Scope {

  /**
   * @var array<string, DefinedValue>
   */
  private array $defined = [];

  private ?Scope $parent = null;

  public function buildChild(): Scope {
    $child = new Scope();
    $child->parent = $this;
    return $child;
  }

  public function isDefined(string $identifier): bool {
    if (isset($this->defined[$identifier])) {
      return true;
    } else {
      return $this->parent?->isDefined($identifier) ?? false;
    }
  }

  public static function reflectionTypeToFormulaType(?ReflectionType $reflectionType): Type {
    if ($reflectionType === null) {
      return new MixedType();
    }
    if ($reflectionType instanceof ReflectionNamedType) {
      if ($reflectionType->isBuiltin()) {
        switch ($reflectionType->getName()) {
          case 'string':
            return new StringType();
          case 'int':
            return new IntegerType();
          case 'float':
            return new FloatType();
          case 'bool':
            return new BooleanType();
          case 'array':
            return new ArrayType(new MixedType(), new MixedType());
          case 'mixed':
            return new MixedType();
          case 'void':
            return new VoidType();
          case 'object':
            return new MixedType();
          case 'callable':
            return new FunctionType(new OuterFunctionArgumentListType([new OuterFunctionArgument(new MixedType(), true, false)], true), new MixedType());
          case 'null':
            return new NullType();
          case 'never':
            return new NeverType();
        }
      } else if (enum_exists($reflectionType->getName())) {
        return new EnumInstanceType(new EnumTypeType(new \ReflectionEnum($reflectionType->getName())));
      } else if (class_exists($reflectionType->getName())) {
        if($reflectionType->getName() === \DateInterval::class) {
          return new DateIntervalType();
        } else if($reflectionType->getName() === \DateTimeImmutable::class) {
          return new DateTimeImmutableType();
        }
        return Scope::reflectionClassToType(new \ReflectionClass($reflectionType->getName()));
      } else if (interface_exists($reflectionType->getName())) {
        return Scope::reflectionClassToType(new \ReflectionClass($reflectionType->getName()));
      }
    } else if ($reflectionType instanceof \ReflectionUnionType) {
      $types = [];
      foreach ($reflectionType->getTypes() as $type) {
        $types[] = self::reflectionTypeToFormulaType($type);
      }
      return CompoundType::buildFromTypes($types);
    }
    throw new \BadMethodCallException('PHP type ' . $reflectionType . ' is not supported');
  }

  /**
   * @param OuterFunctionArgumentListType|array<string, Type>|null|null $argumentType
   * @param ?callable(OuterFunctionArgumentListType): ?Type $specificFunctionReturnType
   */
  public function definePHP(bool $final, string $identifier, mixed $value = '__undefined__', OuterFunctionArgumentListType|array|null $argumentType = null, ?Type $generalReturnType = null, ?callable $specificFunctionReturnType = null): void {
    if ($value !== '__undefined__') {
      $value = Scope::convertPHPVar($value, false, $argumentType, $generalReturnType, $specificFunctionReturnType);
    }
    $this->define($final, $value[0], $identifier, $value[1]);
  }

  public function define(bool $final, Type $type, string $identifier, ?Value $value = null): void {
    if (isset($this->defined[$identifier])) {
      throw new FormulaRuntimeException('Can\'t redefine ' . $identifier);
    }
    $this->defined[$identifier] = new DefinedValue($final, $type, $identifier, $value);
  }

  public function get(string $identifier): Value {
    if (isset($this->defined[$identifier])) {
      return $this->defined[$identifier]->get();
    } else if ($this->parent !== null) {
      return $this->parent->get($identifier);
    } else {
      throw new FormulaRuntimeException($identifier . ' is not defined');
    }
  }

  public function use(string $identifier): Type {
    if (isset($this->defined[$identifier])) {
      $this->defined[$identifier]->setUsed(true);
      return $this->defined[$identifier]->getType();
    } else if ($this->parent !== null) {
      return $this->parent->use($identifier);
    } else {
      throw new FormulaRuntimeException($identifier . ' is not defined');
    }
  }

  public function isUsed(string $identifier): bool {
    if (isset($this->defined[$identifier])) {
      return $this->defined[$identifier]->isUsed();
    } else {
      throw new \BadMethodCallException($identifier . ' is not defined');
    }
  }

  /**
   * @param OuterFunctionArgumentListType|array<string, Type>|null|null $argumentType
   * @param ?callable(OuterFunctionArgumentListType): ?Type $specificFunctionReturnType
   */
  private static function reflectionFunctionToType(ReflectionFunctionAbstract $reflection, OuterFunctionArgumentListType|array|null $argumentType = null, ?Type $generalReturnType = null, ?callable $specificFunctionReturnType = null): FunctionType {
    $reflectionReturnType = $reflection->getReturnType();
    if ($reflectionReturnType !== null) {
      $returnType = Scope::reflectionTypeToFormulaType($reflectionReturnType);
      if($reflectionReturnType->allowsNull()) {
        $returnType = CompoundType::buildFromTypes([$returnType, new NullType()]);
      }
    } else {
      $returnType = new MixedType();
    }
    $arguments = [];
    $reflectionArguments = $reflection->getParameters();
    $vargs = false;
    /**  @var ReflectionParameter  $reflectionArgument */
    foreach ($reflectionArguments as $reflectionArgument) {
      if ($reflectionArgument->isVariadic()) {
        $vargs = true;
      }
      $arguments[] = new OuterFunctionArgument(Scope::reflectionTypeToFormulaType($reflectionArgument->getType()), $reflectionArgument->isOptional(), false, $reflectionArgument->getName());
    }
    if ($argumentType instanceof OuterFunctionArgumentListType) {
      $outArgumentType = $argumentType;
    } else {
      $outArgumentType = new OuterFunctionArgumentListType($arguments, $vargs);
      if (is_array($argumentType)) {
        $outArgumentType = $outArgumentType->mergeArgumentTypes($argumentType);
      }
    }
    return new FunctionType($outArgumentType, $generalReturnType ?? $returnType, $specificFunctionReturnType);
  }

  private static array $phpClassTypes = [];

  public static function reflectionClassToType(\ReflectionClass $reflection, bool $force = false): ClassType {
    if (!$force && isset(Scope::$phpClassTypes[$reflection->getName()])) {
      return Scope::$phpClassTypes[$reflection->getName()];
    }
    Scope::$phpClassTypes[$reflection->getName()] = new ClassType(null, '--', []); // dummy

    $fieldTypes = [];
    /** @var ReflectionProperty $refelctionProperty */
    foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $refelctionProperty) {
      $fieldTypes[$refelctionProperty->getName()] = new FieldType($refelctionProperty->isReadOnly(), Scope::reflectionTypeToFormulaType($refelctionProperty->getType()));
    }
    /** @var ReflectionMethod $reflectionMethod */
    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
      if ($reflectionMethod->isConstructor()) {
        continue;
      }
      $functionType = Scope::reflectionFunctionToType($reflectionMethod);
      $fieldTypes[$reflectionMethod->getName()] = new FieldType(true, $functionType);
    }
    $parentReflection = $reflection->getParentClass();
    $parentClassType = null;
    if ($parentReflection !== false) {
      $parentClassType = Scope::reflectionClassToType($parentReflection);
    }
    $classType = new ClassType($parentClassType, $reflection->getName(), $fieldTypes);
    Scope::$phpClassTypes[$reflection->getName()] = $classType;
    return $classType;
  }

  /**
   * @param OuterFunctionArgumentListType|array<string, Type>|null|null $argumentType
   * @param ?callable(OuterFunctionArgumentListType): ?Type $specificFunctionReturnType
   * @return array [Type, Value]
   */
  public static function convertPHPVar(mixed $value, bool $onlyValue = false, OuterFunctionArgumentListType|array|null $argumentType = null, ?Type $generalReturnType = null, ?callable $specificFunctionReturnType = null): array {
    if ($value instanceof Value) {
      return [null, $value];
    } else if ($value instanceof \DateTimeImmutable) {
      return [new DateTimeImmutableType(), new DateTimeImmutableValue($value)];
    } else if ($value instanceof \DateInterval) {
      return [new DateIntervalType(), new DateIntervalValue($value)];
    } else if ($value instanceof \UnitEnum) {
      return [new EnumInstanceType(new EnumTypeType(new \ReflectionEnum($value::class))), new EnumInstanceValue($value)];
    } else if (is_string($value) && enum_exists($value)) {
      $reflection = new \ReflectionEnum($value);
      return [new EnumTypeType($reflection), new EnumTypeValue($reflection)];
    } else if (is_string($value) && class_exists($value)) {
      $reflection = new \ReflectionClass($value);
      $classType = Scope::reflectionClassToType($reflection);
      if ($reflection->getConstructor() === null) {
        $constructorFunctionType = new FunctionType(new OuterFunctionArgumentListType([], false), new VoidType());
      } else {
        $constructorFunctionType = Scope::reflectionFunctionToType($reflection->getConstructor());
      }
      $constructor = new ConstructorValue(new PHPFunctionBody(function (...$args) use ($reflection) {
        $phpArgs = [];
        foreach ($args as $arg) {
          $phpArgs[] = $arg;
        }
        return new PHPClassInstanceValue($reflection->newInstance(...$phpArgs));
      }, false));

      $contructorType = new ConstructorType($constructorFunctionType->arguments, $classType);

      return [new ClassTypeType($contructorType), new ClassTypeValue($constructor)];
    } else if (is_int($value)) {
      return [new IntegerType(), new IntegerValue($value)];
    } else if (is_float($value)) {
      return [new FloatType(), new FloatValue($value)];
    } else if (is_bool($value)) {
      return [new BooleanType(), new BooleanValue($value)];
    } else if (is_string($value)) {
      return [new StringType(), new StringValue($value)];
    } else if ($value === null) {
      return [new NullType(), new NullValue()];
    } else if (is_callable($value)) {
      if (is_array($value)) {
        $reflection = new ReflectionMethod($value[0], $value[1]);
      } else {
        $reflection = new \ReflectionFunction($value);
      }
      $functionType = Scope::reflectionFunctionToType($reflection, $argumentType, $generalReturnType, $specificFunctionReturnType);
      $functionBody = new PHPFunctionBody($value, $functionType->generalReturnType instanceof VoidType);
      return [$functionType, new FunctionValue($functionBody)];
    } else if (is_array($value)) {
      $values = [];
      $valueTypes = [];
      $keyTypes = [];
      foreach ($value as $key => $element) {
        if (!$onlyValue) {
          $keyRes = Scope::convertPHPVar($key);
          $keyTypes[] = $keyRes[0];
        }
        $elementRes = Scope::convertPHPVar($element);
        $valueTypes[] = $elementRes[0];
        $values[$key] = $elementRes[1];
      }
      if ($onlyValue) {
        return [null, new ArrayValue($values)];
      } else {
        return [new ArrayType(CompoundType::buildFromTypes($keyTypes), CompoundType::buildFromTypes($valueTypes)), new ArrayValue($values)];
      }
    } else if (is_object($value)) {
      $reflection = new \ReflectionClass($value);
      $fieldTypes = [];
      //       $fieldValues = [];
      /** @var ReflectionProperty $refelctionProperty */
      foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $refelctionProperty) {
        $fieldTypes[$refelctionProperty->getName()] = new FieldType($refelctionProperty->isReadOnly(), Scope::reflectionTypeToFormulaType($refelctionProperty->getType()));
        //         $fieldValues[$refelctionProperty->getName()] = new FieldValue(Scope::convertPHPVar($refelctionProperty->getValue($value), true)[1]);
      }
      /** @var ReflectionMethod $reflectionMethod */
      foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
        if ($reflectionMethod->isConstructor()) {
          continue;
        }
        $functionType = Scope::reflectionFunctionToType($reflectionMethod);
        $fieldTypes[$reflectionMethod->getName()] = new FieldType(true, $functionType);
        //         $fieldValues[$reflectionMethod->getName()] = new FieldValue(new FunctionValue(new PHPFunctionBody([$value,$reflectionMethod->getName()])));
      }
      return [new ClassType(null, $reflection->getName(), $fieldTypes), new PHPClassInstanceValue($value)];
      //       return [new ClassType(null, $reflection->getName(), $fieldTypes),new ClassInstanceValue($fieldValues)];
    }
    throw new FormulaRuntimeException('Unsupported php type');
  }

  public function assignPHP(string $identifier, mixed $value, bool $ignoreFinal = false): void {
    $res = Scope::convertPHPVar($value);
    $this->assign($identifier, $res[1], $ignoreFinal);
  }

  public function assign(string $identifier, Value $value, bool $ignoreFinal = false): void {
    if (isset($this->defined[$identifier])) {
      $this->defined[$identifier]->assign($value, $ignoreFinal);
    } else if ($this->parent !== null) {
      $this->parent->assign($identifier, $value, $ignoreFinal);
    } else {
      throw new FormulaRuntimeException($identifier . ' is not defined');
    }
  }

  public function unset(string $identifier): void {
    if (isset($this->defined[$identifier])) {
      $this->defined[$identifier]->unset();
    } else if ($this->parent !== null) {
      $this->parent->unset($identifier);
    } else {
      throw new FormulaRuntimeException($identifier . ' is not defined');
    }
  }

  public function setParent(Scope $parent): void {
    $this->parent = $parent;
  }

  public function toNodeTreeScope(): array {
    $definedValues = [];
    if($this->parent !== null) {
      $definedValues = $this->parent->toNodeTreeScope();
    }
    foreach ($this->defined as $identifier => $definedValue) {
      $definedValues[$identifier] = $definedValue->getType()->getInterfaceType();
    }
    return $definedValues;
  }
}
