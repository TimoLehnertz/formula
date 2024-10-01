<?php

declare(strict_types=1);

namespace TimoLehnertz\formula\procedure;

use TimoLehnertz\formula\FormulaRuntimeException;
use TimoLehnertz\formula\type\ArrayType;
use TimoLehnertz\formula\type\BooleanType;
use TimoLehnertz\formula\type\CompoundType;
use TimoLehnertz\formula\type\MixedType;
use TimoLehnertz\formula\type\NeverType;
use TimoLehnertz\formula\type\NullType;
use TimoLehnertz\formula\type\Type;
use TimoLehnertz\formula\type\functions\FunctionType;
use TimoLehnertz\formula\type\functions\OuterFunctionArgument;
use TimoLehnertz\formula\type\functions\OuterFunctionArgumentListType;
use TimoLehnertz\formula\ExitIfNullException;
use TimoLehnertz\formula\type\FloatType;
use TimoLehnertz\formula\type\functions\SpecificReturnType;
use TimoLehnertz\formula\type\IntegerType;

/**
 * @author Timo Lehnertz
 */
class DefaultScope extends Scope {

  public function __construct() {
    $this->definePHP(true, 'print', [DefaultScope::class, 'printFunc']);
    $this->definePHP(true, 'println', [DefaultScope::class, 'printlnFunc']);
    $this->definePHP(true, 'pow', [DefaultScope::class, 'powFunc']);
    $this->definePHP(true, "min", [DefaultScope::class, "minFunc"]);
    $this->definePHP(true, "max", [DefaultScope::class, "maxFunc"]);
    $this->definePHP(true, "sqrt", [DefaultScope::class, "sqrtFunc"]);
    $this->definePHP(true, "ceil", [DefaultScope::class, "ceilFunc"]);
    $this->definePHP(true, "floor", [DefaultScope::class, "floorFunc"]);
    $this->definePHP(true, "round", [DefaultScope::class, "roundFunc"]);
    $this->definePHP(true, "sin", [DefaultScope::class, "sinFunc"]);
    $this->definePHP(true, "cos", [DefaultScope::class, "cosFunc"]);
    $this->definePHP(true, "tan", [DefaultScope::class, "tanFunc"]);
    $this->definePHP(true, "is_nan", [DefaultScope::class, "is_nanFunc"]);
    $this->definePHP(true, "abs", [DefaultScope::class, "absFunc"]);
    $this->definePHP(true, "asVector", [DefaultScope::class, "asVectorFunc"]);
    $this->definePHP(true, "sizeof", [DefaultScope::class, "sizeofFunc"]);
    $this->definePHP(true, "inRange", [DefaultScope::class, "inRangeFunc"]);
    $this->definePHP(true, "reduce", [DefaultScope::class, "reduceFunc"], null, null, new SpecificReturnType('FORMULA_REDUCE', function (OuterFunctionArgumentListType $args): ?Type {
      return $args->getArgumentType(0);
    }));
    $this->definePHP(true, "firstOrNull", [DefaultScope::class, "firstOrNullFunc"], null, null, new SpecificReturnType('FORMULA_FIRST_OR_NULL', function (OuterFunctionArgumentListType $args): ?Type {
      $type = $args->getArgumentType(0);
      if ($type instanceof ArrayType) {
        if ($type->getElementsType() instanceof NeverType) {
          return new NullType();
        }
        return CompoundType::buildFromTypes([new NullType(), $type->getElementsType()]);
      }
    }));
    $this->definePHP(true, "lastOrNull", [DefaultScope::class, "lastOrNullFunc"], null, null, new SpecificReturnType('FORMULA_FIRST_OR_NULL', function (OuterFunctionArgumentListType $args): ?Type {
      $type = $args->getArgumentType(0);
      if ($type instanceof ArrayType) {
        if ($type->getElementsType() instanceof NeverType) {
          return new NullType();
        }
        return CompoundType::buildFromTypes([new NullType(), $type->getElementsType()]);
      }
    }));
    $this->definePHP(true, "assertTrue", [DefaultScope::class, "assertTrueFunc"]);
    $this->definePHP(true, "assertFalse", [DefaultScope::class, "assertFalseFunc"]);
    $this->definePHP(true, "assertEquals", [DefaultScope::class, "assertEqualsFunc"]);
    $intOrFloat = CompoundType::buildFromTypes([new FloatType(), new IntegerType()]);
    $this->definePHP(true, "sum", [DefaultScope::class, "sumFunc"], new OuterFunctionArgumentListType([new OuterFunctionArgument(CompoundType::buildFromTypes([$intOrFloat, new ArrayType(new MixedType(), $intOrFloat)]), true, true, 'values')], true));
    $this->definePHP(true, "avg", [DefaultScope::class, "avgFunc"], new OuterFunctionArgumentListType([new OuterFunctionArgument(CompoundType::buildFromTypes([$intOrFloat, new ArrayType(new MixedType(), $intOrFloat)]), true, true, 'values')], true));
    $callbackType = new FunctionType(new OuterFunctionArgumentListType([new OuterFunctionArgument(new MixedType())]), new BooleanType());
    $this->definePHP(true, "array_filter", [DefaultScope::class, "array_filterFunc"], ['callback' => $callbackType], null, new SpecificReturnType('FORMULA_ARRAY_FILTER', function (OuterFunctionArgumentListType $args): ?Type {
      return $args->getArgumentType(0);
    }));

    $this->definePHP(true, "earlyReturnIfNull", [DefaultScope::class, "earlyReturnIfNullFunc"], null, null, new SpecificReturnType('FORMULA_EARLY_RETURN_IF_NULL', function (OuterFunctionArgumentListType $args): ?Type {
      $type = $args->getArgumentType(0);
      if ($type instanceof CompoundType) {
        return $type->eliminateType(new NullType());
      } else if ($type instanceof NullType) {
        return new NeverType();
      } else {
        return $type;
      }
    }));
    // constants
    $this->definePHP(true, "PI", M_PI);
  }

  public static function earlyReturnIfNullFunc(mixed $value): mixed {
    if ($value === null) {
      throw new ExitIfNullException();
    } else {
      return $value;
    }
  }

  public static function array_filterFunc(array $array, callable $callback): array {
    return array_filter($array, $callback);
  }

  public static function printFunc(string $str): void {
    echo $str;
  }

  public static function printlnFunc(string $str): void {
    self::printFunc($str . PHP_EOL);
  }

  private static function mergeArraysRecursive(array $arrays): array {
    $merged = [];
    foreach ($arrays as $val) {
      if (is_array($val)) {
        $merged = array_merge($merged, DefaultScope::mergeArraysRecursive($val));
      } else {
        $merged[] = $val;
      }
    }
    return $merged;
  }

  public static function minFunc(float|array ...$values): float {
    $values = DefaultScope::mergeArraysRecursive($values);
    return min($values);
  }

  public static function maxFunc(float|array ...$values): float {
    $values = DefaultScope::mergeArraysRecursive($values);
    return max($values);
  }

  public static function powFunc(float $base, float $exp): float {
    return (float) pow($base, $exp);
  }

  public static function sqrtFunc(float $arg): float {
    return sqrt($arg);
  }

  public static function ceilFunc(float $value): float {
    return ceil($value);
  }

  public static function floorFunc(float $value): float {
    return floor($value);
  }

  public static function roundFunc(int|float $num, int $precision = 0): float {
    return round($num, $precision);
  }

  public static function sinFunc(float $arg): float {
    return sin($arg);
  }

  public static function cosFunc(float $arg): float {
    return cos($arg);
  }

  public static function tanFunc(float $arg): float {
    return tan($arg);
  }

  public static function is_nanFunc(float $val): bool {
    return is_nan($val);
  }

  public static function absFunc(float $number): float {
    return abs($number);
  }

  public static function asVectorFunc(mixed ...$values): array {
    return $values;
  }

  public static function sizeofFunc(mixed ...$values): int {
    return count(DefaultScope::mergeArraysRecursive($values));
  }

  public static function inRangeFunc(float $value, float $min, float $max): bool {
    return ($min <= $value) && ($value <= $max);
  }

  public static function reduceFunc(array $values, array $filter): array {
    $result = [];
    foreach ($values as $value) {
      if (in_array($value, $filter)) {
        $result[] = $value;
      }
    }
    return $result;
  }

  public static function firstOrNullFunc(array $array): mixed {
    if (sizeof($array) === 0)
      return null;
    return $array[0];
  }

  public static function lastOrNullFunc(array $array): mixed {
    if (sizeof($array) === 0)
      return null;
    return end($array);
  }

  public static function sumFunc(float|array ...$values): float {
    $arr = DefaultScope::mergeArraysRecursive($values);
    $res = 0;
    foreach ($arr as $value) {
      if (!is_numeric($value)) {
        throw new FormulaRuntimeException('Only numeric values or vectors are allowed for sum');
      }
      $res += $value;
    }
    return $res;
  }

  public static function avgFunc(float|array ...$values): float {
    $sum = self::sumFunc($values);
    return $sum / self::sizeofFunc($values);
  }

  public static function assertTrueFunc(bool $condition) {
    if($condition === false) {
      throw new FormulaRuntimeException('failed asserting that false is true');
    }
  }

  public static function assertEqualsFunc($expected, $actual, string $message = '') {
    if($expected != $actual) {
      throw new FormulaRuntimeException('failed asserting that '.var_export($actual, true).' equals '.var_export($expected, true).' '.$message);
    }
  }

  public static function assertFalseFunc(bool $condition, string $message = '') {
    if($condition === true) {
      throw new FormulaRuntimeException('failed asserting that true is false');
    }
  }
}
