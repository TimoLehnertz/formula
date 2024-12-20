<?php

namespace test\type;

use PHPUnit\Framework\TestCase;
use TimoLehnertz\formula\Formula;
use TimoLehnertz\formula\procedure\Scope;

class CompoundTypeTest extends TestCase {

  public function funcTest(): int|bool {
    return false;
  }

  public function testTruthy(): void {
    $scope = new Scope();
    $scope->definePHP(true, 'func', [$this, 'funcTest']);
    $formula = new Formula('var a = func(); a = 0; return a;', $scope);
    $this->assertEquals(0, $formula->calculate()->toPHPValue());
  }
}
