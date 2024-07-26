<?php

namespace test\parsing;

use PHPUnit\Framework\TestCase;
use TimoLehnertz\formula\parsing\CodeBlockParser;
use TimoLehnertz\formula\statement\CodeBlock;
use TimoLehnertz\formula\tokens\Tokenizer;

class StatementParserTest extends TestCase {

  public function testNull(): void {
    $firstToken = Tokenizer::tokenize("final null abc = null;");
    $type = (new CodeBlockParser(false, true))->parse($firstToken);
    $this->assertNull($type->nextToken);
    $this->assertInstanceOf(CodeBlock::class, $type->parsed);
  }
}
