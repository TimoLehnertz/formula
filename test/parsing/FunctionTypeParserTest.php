<?php
namespace test\parsing;

use PHPUnit\Framework\TestCase;
use TimoLehnertz\formula\Formula;
use TimoLehnertz\formula\parsing\FunctionTypeParser;
use TimoLehnertz\formula\tokens\Tokenizer;
use TimoLehnertz\formula\type\functions\FunctionType;
use TimoLehnertz\formula\type\VoidType;

class FunctionTypeParserTest extends TestCase {

  public function testOK(): void {
    //     new Formula('function(int) -> int a = (int a) -> a;');
    $formula = new Formula('int a = 0;function(int... args) -> float vargFunc = (int... args) -> sum(args); return vargFunc(1,2,3);');
    $this->assertEquals(6, $formula->calculate()->toPHPValue());
  }

  public function testVoidFunction(): void {
    $tokens = Tokenizer::tokenize('function(int) -> void');
    $functionType = (new FunctionTypeParser())->parse($tokens);
    $this->assertInstanceOf(FunctionType::class, $functionType->parsed);
    $this->assertInstanceOf(VoidType::class, $functionType->parsed->generalReturnType);
    
  }
}
