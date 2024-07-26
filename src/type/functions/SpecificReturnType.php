<?php

declare(strict_types=1);

namespace TimoLehnertz\formula\type\functions;

use TimoLehnertz\formula\type\Type;

/**
 * @author Timo Lehnertz
 */
class SpecificReturnType {

  public readonly string $identifier;
  
  /**
   * @var ?callable(OuterFunctionArgumentListType): ?Type
   */
  public readonly mixed $specificReturnType;

  /**
   * @param ?callable(OuterFunctionArgumentListType): ?Type $specificReturnType
   */
  public function __construct(string $identifier, callable $specificReturnType) {
    $this->identifier = $identifier;
    $this->specificReturnType = $specificReturnType;
  }
}
