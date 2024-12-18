<?php

declare(strict_types=1);

namespace TimoLehnertz\formula\procedure;

use TimoLehnertz\formula\FormulaRuntimeException;
use TimoLehnertz\formula\type\Type;
use TimoLehnertz\formula\type\Value;
use TimoLehnertz\formula\ValueUnsetException;

use const false;

/**
 * @author Timo Lehnertz
 */
class DefinedValue implements ValueContainer {

  private readonly string $name;

  private readonly bool $final;

  private readonly Type $type;

  private ?Value $value = null;

  private bool $used = false;

  public function __construct(bool $final, Type $type, string $name, ?Value $initialValue) {
    $this->final = $final;
    $this->type = $type->setAssignable(!$this->final);
    $this->name = $name;
    $this->value = $initialValue;
    $this->value?->setContainer($this->final ? null : $this);
  }

  public function assign(Value $value, bool $ignoreFinal = false): void {
    if ($this->final && !$ignoreFinal) {
      throw new FormulaRuntimeException('Cant mutate immutable value');
    }
    $this->value?->setContainer(null);
    $this->value = $value;
    $this->value->setContainer($this);
  }

  public function unset(): void {
    $this->value = null;
  }

  public function get(): Value {
    if ($this->value === null) {
      throw new ValueUnsetException($this->name);
    }
    return $this->value;
  }

  public function getType(): Type {
    return $this->type;
  }

  public function setUsed(bool $used): void {
    $this->used = $used;
  }

  public function isUsed(): bool {
    return $this->used;
  }
}
