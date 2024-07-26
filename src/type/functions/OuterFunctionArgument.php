<?php

declare(strict_types=1);

namespace TimoLehnertz\formula\type\functions;

use TimoLehnertz\formula\type\Type;
use TimoLehnertz\formula\FormulaPart;
use TimoLehnertz\formula\PrettyPrintOptions;

/**
 * @author Timo Lehnertz
 *
 *         Represents a function argument as seen from outside of that function
 */
class OuterFunctionArgument implements FormulaPart {

  public readonly Type $type;

  public readonly bool $optional;

  public readonly ?string $name;

  public readonly bool $varg;

  public function __construct(Type $type, bool $optional = false, bool $varg = false, ?string $name = null) {
    $this->type = $type;
    $this->optional = $optional;
    $this->name = $name;
    $this->varg = $varg;
  }

  public function equals(OuterFunctionArgument $other): bool {
    return $this->optional === $other->optional && $this->type->equals($other->type);
  }

  public function toString(PrettyPrintOptions $prettyPrintOptions): string {
    $str = $this->type->toString($prettyPrintOptions);
    if ($this->varg) {
      $str .= '...';
    }
    if ($this->name !== null) {
      $str .= ' ' . $this->name;
    }
    return $str;
  }

  public function setOptional(bool $optional): OuterFunctionArgument {
    return new OuterFunctionArgument($this->type, $optional, $this->varg, $this->name);
  }
}
