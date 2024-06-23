<?php
namespace test\statement;

use PHPUnit\Framework\TestCase;
use TimoLehnertz\formula\Formula;
use TimoLehnertz\formula\FormulaValidationException;
use TimoLehnertz\formula\type\IntegerType;

class WhileStatementTest extends TestCase {

  public function testBreak(): void {
    $formula = new Formula('int i = 0; while(true) {break;i++;}return i;');
    $this->assertEquals(0, $formula->calculate()->toPHPValue());
  }
}
