<?php
namespace GarryDzeng\Route {

  class TokenizerTest extends \PHPUnit\Framework\TestCase {

    public function testMissingBracket() {

      $this->expectException(InvalidTokenException::class);

      $tokenizer = new Tokenizer();
      $tokenizer->tokenize('{example');
    }

    public function testUnescapedBracketDuringParsing() {

      $this->expectException(InvalidTokenException::class);

      $tokenizer = new Tokenizer();
      $tokenizer->tokenize('{{example}');
    }

    public function testTokenize() {

      $tokenizer = new Tokenizer();

      $tokens_01 = $tokenizer->tokenize('/');
      $tokens_02 = $tokenizer->tokenize('/users/{id}');
      $tokens_03 = $tokenizer->tokenize('/users/{id}/drafts/{id=[A-Fa-f0-9]\\{32\\}}');
      $tokens_04 = $tokenizer->tokenize('/{=digit}');
      $tokens_05 = $tokenizer->tokenize('/{}/');

      $this->assertEquals(['/'], $tokens_01);
      $this->assertEquals(['/users/',['name'=> 'id','type'=> Tokenizer::TYPE_STRING]], $tokens_02);
      $this->assertEquals(['/users/',['name'=> 'id','type'=> Tokenizer::TYPE_STRING],'/drafts/',['name'=> 'id','pattern'=> '[A-Fa-f0-9]{32}','type'=> Tokenizer::TYPE_REGEX]], $tokens_03);
      $this->assertEquals(['/',['name'=> 0,'type'=> Tokenizer::TYPE_DIGIT]], $tokens_04);
      $this->assertEquals(['/',['name'=> 0,'type'=> Tokenizer::TYPE_STRING],'/'], $tokens_05);
    }
  }
}