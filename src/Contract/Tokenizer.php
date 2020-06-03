<?php
namespace GarryDzeng\Route\Contract {

  interface Tokenizer {

    const TYPE_REGEX = 'regex';
    const TYPE_HEXADECIMAL = 'hexadecimal';
    const TYPE_STRING = 'string';
    const TYPE_DIGIT = 'digit';

    /**
     * Parse URL pattern as router tokens
     * @param string $source
     * @return array
     */
    public function tokenize(string $source);
  }
}