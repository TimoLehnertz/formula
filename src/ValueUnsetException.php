<?php

declare(strict_types=1);

namespace TimoLehnertz\formula;

/**
 * @author Timo Lehnertz
 */
class ValueUnsetException extends FormulaRuntimeException {

  private readonly string $property;

  public function __construct(string $property) {
    parent::__construct('Property ' . $property . ' is unset');
    $this->property = $property;
  }

  public function getUnsetPropertyName(): string {
    return $this->property;
  }
}
