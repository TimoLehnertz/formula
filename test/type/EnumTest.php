<?php
namespace test\type;

use PHPUnit\Framework\TestCase;
use TimoLehnertz\formula\Formula;
use TimoLehnertz\formula\procedure\Scope;
use const test\type\TestInterval\DAILY;

class EnumTest extends TestCase {

  function stringify(TestInterval $interval): string {
    switch($interval) {
      case TestInterval::DAILY:
        return 'daily';
      case TestInterval::MINUTELY:
        return 'minutely';
    }
  }

  public function testEnum(): void {
    $scope = new Scope();
    $scope->definePHP(true, 'TestInterval', TestInterval::class);
    $formula = new Formula('TestInterval.DAILY', $scope);
    $this->assertEquals(TestInterval::DAILY, $formula->calculate()->toPHPValue());
  }

  public function testEnumToFunction(): void {
    $scope = new Scope();
    $scope->definePHP(true, 'TestInterval', TestInterval::class);
    $scope->definePHP(true, 'stringify', [$this,'stringify']);
    $formula = new Formula('"" + stringify(TestInterval.DAILY) + " " + stringify(TestInterval.MINUTELY)', $scope);
    $this->assertEquals('daily minutely', $formula->calculate()->toPHPValue());
  }
}

enum TestInterval: string {

  case DAILY = "1";

  case MINUTELY = "2";
}