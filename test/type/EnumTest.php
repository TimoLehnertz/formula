<?php

namespace test\type;

use PHPUnit\Framework\TestCase;
use ReflectionEnum;
use ReflectionEnumBackedCase;
use ReflectionEnumUnitCase;
use TimoLehnertz\formula\Formula;
use TimoLehnertz\formula\procedure\Scope;
use TimoLehnertz\formula\type\EnumInstanceType;

class EnumTest extends TestCase {

  function stringify(TestInterval $interval): string {
    switch ($interval) {
      case TestInterval::DAILY:
        return 'daily';
      case TestInterval::MINUTELY:
        return 'minutely';
    }
  }

  function stringifyBacked(TestBackedInterval $interval): string {
    switch ($interval) {
      case TestBackedInterval::DAILY:
        return 'daily';
      case TestBackedInterval::MINUTELY:
        return 'minutely';
    }
  }

  public function testEnum(): void {
    $scope = new Scope();
    $scope->definePHP(true, 'TestInterval', TestInterval::class);
    $formula = new Formula('TestInterval.DAILY', $scope);
    $this->assertEquals(TestInterval::DAILY, $formula->calculate()->toPHPValue());
    $returnType = $formula->getReturnType();
    $this->assertInstanceOf(EnumInstanceType::class, $returnType);
    $this->assertEquals(TestInterval::DAILY, $returnType->getRestrictedValues()[0]->toPHPValue());
  }

  public function testEnumToFunction(): void {
    $scope = new Scope();
    $scope->definePHP(true, 'TestInterval', TestInterval::class);
    $scope->definePHP(true, 'stringify', $this->stringify(...));
    $formula = new Formula('"" + stringify(TestInterval.DAILY) + " " + stringify(TestInterval.MINUTELY)', $scope);
    $this->assertEquals('daily minutely', $formula->calculate()->toPHPValue());
  }

  public function testEnumBacked(): void {
    $scope = new Scope();
    $scope->definePHP(true, 'TestBackedInterval', TestBackedInterval::class);
    $formula = new Formula('TestBackedInterval.DAILY', $scope);
    $this->assertEquals(TestBackedInterval::DAILY, $formula->calculate()->toPHPValue());
  }

  public function testEnumBackedToFunction(): void {
    $scope = new Scope();
    $scope->definePHP(true, 'TestBackedInterval', TestBackedInterval::class);
    $scope->definePHP(true, 'stringifyBacked', $this->stringifyBacked(...));
    $formula = new Formula('"" + stringifyBacked(TestBackedInterval.DAILY) + " " + stringifyBacked(TestBackedInterval.MINUTELY)', $scope);
    $this->assertEquals('daily minutely', $formula->calculate()->toPHPValue());
  }
}

enum TestBackedInterval: string {
  case DAILY = "1";
  case MINUTELY = "2";
}

enum TestInterval {
  case DAILY;
  case MINUTELY;
}
