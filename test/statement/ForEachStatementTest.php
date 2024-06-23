<?php
namespace test\statement;

use PHPUnit\Framework\TestCase;
use TimoLehnertz\formula\Formula;
use TimoLehnertz\formula\FormulaValidationException;
use TimoLehnertz\formula\type\IntegerType;

class ForEachStatementTest extends TestCase {

  public function testNotIteratable(): void {
    $this->expectException(FormulaValidationException::class);
    $this->expectExceptionMessage('For each getter must iteratable');
    new Formula('for(var a : "abc") {}');
  }

  public function testInvalidType(): void {
    $this->expectException(FormulaValidationException::class);
    $this->expectExceptionMessage('1:0 Validation error: int is not assignable by String');
    new Formula('for(int a : {"abc"}) {}');
  }

  public function testInnerReturn(): void {
    $formula = new Formula('for(String a : {"abc"}) {return a;}');
    $this->assertEquals('abc', $formula->calculate()->toPHPValue());
  }

  public function testBreak(): void {
    $formula = new Formula('int i = 0; for(String a : {"abc"}) {break;i++;}return i;');
    $this->assertEquals(0, $formula->calculate()->toPHPValue());
  }
}
