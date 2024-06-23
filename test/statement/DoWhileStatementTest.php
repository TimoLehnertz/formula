<?php
namespace test\statement;

use PHPUnit\Framework\TestCase;
use TimoLehnertz\formula\Formula;
use TimoLehnertz\formula\type\IntegerType;

class DoWhileStatementTest extends TestCase {

  public function testCondition(): void {
    $formula = new Formula('int i = 0; do {i++;} while(i < 5); return i;');
    $this->assertEquals(5, $formula->calculate()->toPHPValue());
  }

  public function testInnerReturn(): void {
    $formula = new Formula('int i = 0; do {return i;} while(i < 5);');
    $this->assertInstanceOf(IntegerType::class, $formula->getReturnType());
    $this->assertEquals(0, $formula->calculate()->toPHPValue());
  }
}
