<?php

namespace test\type;

use PHPUnit\Framework\TestCase;
use TimoLehnertz\formula\Formula;
use TimoLehnertz\formula\FormulaRuntimeException;

class IntegerTypeTest extends TestCase {

  public function testDivisionByZero(): void {
    $formula = new Formula('1 / 0');
    $this->expectException(FormulaRuntimeException::class);
    $this->expectExceptionMessage('Division by zero');
    $formula->calculate();
  }
}
