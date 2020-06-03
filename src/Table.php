<?php
namespace GarryDzeng\Route {

  use InvalidArgumentException;

  /**
   * @package Lodestone\Router
   * @inheritDoc
   *
   * For technical reason, this class used stdClass to represents Node structure
   */
  class Table implements Contract\Table {

    private $tokenizer;

    /**
     * @param Contract\Tokenizer $tokenizer
     */
    public function __construct(Contract\Tokenizer $tokenizer) {
      $this->tokenizer = $tokenizer;
    }

    /**
     * @inheritDoc
     * @throws
     */
    public function match($state, $pathname) {

      $callbacks = [];
      $arguments = [];

      // search
      while ($state && $pathname !== '') {

        $match = null;
        $start = 0;

        foreach ($state as $i) {

          $token = $i->token;

          // literal
          if (!is_array($token)) {

            $where = strpos($pathname, $token);

            // Matched! pathname starts with current token (there is literal)
            if ($where === 0) {
              $start = strlen($token);
              $match = $i;
            }
          }

          // parameter
          else {

            [
              'name'=> $key,
              'pattern'=> $pattern,
              'type'=> $type,
            ] = $token;

            // split pathname by type
            switch ($type) {

              case Contract\Tokenizer::TYPE_REGEX: $got = $this->regex($pathname, $pattern); break;
              case Contract\Tokenizer::TYPE_STRING: $got = $this->string($pathname); break;
              case Contract\Tokenizer::TYPE_HEXADECIMAL: $got = $this->hexadecimal($pathname); break;
              case Contract\Tokenizer::TYPE_DIGIT: $got = $this->digit($pathname); break;

              default: {
                throw new InvalidArgumentException(
                  'Invalid type, '.
                  'an unrecognized type found in your pattern,'.
                  'please check.'
                );
              }
            }

            if ($got) {

              [
                'value'=> $value,
                'index'=> $start,
              ] = $got;

              $match = $i;

              /*
               * Save matched value into argument list in access sequence,
               * ordinal is more important,
               * name used to improve readability
               */
              $arguments[] = [
                'value'=> $value,
                'key'=> $key,
              ];
            }
          }

          // literal or parameter matched
          if ($match) {
            break;
          }
        }

        $state = [];

        // someting matched
        if ($match) {

          $callbacks = $match->callbacks;
          $state = $match->edges;

          // discard the matched part of pathname
          $pathname = substr(
            $pathname,
            $start
          );
        }
      }

      /*
       * Remaining character found after traversed,
       * it means given pathname isn't matched to current routes exactly,
       * return NULL
       */
      if (strlen($pathname) > 0) {
        return null;
      }

      /*
       * Match {
       *  callbacks: [],
       *  arguments: {
       *   [name: string]: string
       *  }
       * }
       */
      return [
        'callbacks'=> $callbacks,
        'arguments'=> $arguments,
      ];
    }

    /**
     * @inheritDoc
     * @throws
     */
    public function register($state, $pattern, ...$callbacks) {

      $tokenizer = $this->tokenizer;

      if ($pattern == '') {
        throw new InvalidArgumentException('Pattern for pathname is empty, please given someting.');
      }

      $tokens = $tokenizer->tokenize($pattern);

      /*
       * initial state is empty
       * use init() because its more simple then others ...
       * speed up also
       */
      if (!$state) {
        return [
          $this->new($tokens, $callbacks)
        ];
      }

      // This is N level of route ...
      $level = &$state;

      // compare with nodes
      for ($step = 0, $length = count($tokens); $step < $length; $step++) {

        $input = &$tokens[$step];
        $match = null;
        $cutat = 0;

        /*
         * Match cases:
         * 1 they are parameter both and has same name & type
         * 2 they has a common prefix
         * 3 input == token
         */
        foreach ($level as /* stdClass */ &$value) {

          $exist = $value->token;

          $isAliceParamized = is_array($exist);
          $isInputParamized = is_array($input);

          // all literal
          if (
            $isAliceParamized == false &&
            $isInputParamized == false
          )
          {
            $cutat = $this->commonPrefixLength($exist, $input);

            if ($cutat > 0) {
              $replaceable = &$value;
              $match = $value;
              break;
            }
          }

          // all param
          elseif (
            $isAliceParamized &&
            $isInputParamized
          )
          {
            // compare two associative array directly
            if ($exist == $input) {
              $match = $value;
              break;
            }
          }
        }

        /*
         * Create to new branch if leaf mismatched ...
         * discontinue also
         */
        if (!$match) {
          $level[] = $this->new(array_slice($tokens, $step), $callbacks);
          break;
        }

        /*
         * Move to branch if ...
         * 1 leaf & input token are parameter both & they are equals ...
         * 2 literal are equals
         */
        if ($cutat == 0 || $match->token == $input) {
          $level = &$match->edges;
        }

        else {

          // discard the common prefix then became a branch of current leaf
          if ($cutat < strlen($input)) {
            $input = substr(
              $input,
              $cutat
            );
          }

          if ($cutat === strlen($match->token)) {
            $level = &$match->edges;
            $step--;
          }

          // split edge
          else {

            $token = substr($match->token, 0, $cutat);

            // split a branch
            $branch = $match->callbacks ?
              (object)['token'=> substr($match->token, $cutat),'edges'=> $match->edges,'callbacks'=> $match->callbacks] :
              (object)['token'=> substr($match->token, $cutat),'edges'=> $match->edges];

            /*
             * We meet a Backslash,
             * recognized as tailer if its last element of Token stream ...
             * attach to prefix
             */
            if ($input === '/' && ($length - 1) === $step) {
              $replaceable = (object)[
                'token'=> $token,
                'callbacks'=> $callbacks,
                'edges'=> [
                  $branch
                ]
              ];
            }
            else {

              // just replace memory to new stdClass rather then unset & update properties
              $replaceable = (object)[
                'token'=> $token,
                'edges'=> [
                  $branch,
                  $this->new(
                    array_slice($tokens, $step),
                    $callbacks
                  )
                ]
              ];
            }

            $level = &$replaceable->edges;
          }
        }
      }

      return $state;
    }

    /**
     * @inheritDoc
     *
     * Node {
     *   token: <Token; see Tokenizer>,
     *   callbacks: [],
     *   edges: [
     *     Node
     *   ]
     * }
     */
    private function new(array $tokens, $callbacks = []) {

      if (!$tokens) {
        throw new InvalidArgumentException();
      }

      $length = count($tokens);
      $node = null;

      // reverse visit the list & collapse to single node
      while ($length-- > 0) {

        $token = $tokens[$length];

        /*
         * is firt run?
         * we attach callback to last element because callback make significance
         * when rule match exactly
         */
        if (!$node) {
          $node = (object)[
            'token'=> $token,
            'callbacks'=> $callbacks,
            'edges'=> []
          ];
        }
        else {
          $node = (object)[
            'token'=> $token,
            'edges'=> [
              $node
            ]
          ];
        }
      }

      return $node;
    }

    /**
     * @inheritDoc
     * @throws
     */
    private function commonPrefixLength($lvalue, $rvalue) {

      // two strings are equals actually...
      if ($lvalue == $rvalue) {
        return strlen(
          $lvalue
        );
      }

      for (
        $last = 0, $length = min(strlen($lvalue), strlen($rvalue));
        $last < $length;
        $last++
      )
      {
        if ($lvalue[$last] != $rvalue[$last]) {
          return $last;
        }
      }

      return $length;
    }

    protected function regex($input, $format) {

      /*
       * Escape backslash,
       * because we use it as delimiter in expression,
       * to avoid parsing error
       */
      if (!preg_match('/^'.addcslashes($format, '/').'/', $input, $matches)) {
        return null;
      }

      [
        $match
      ] = $matches;

      return [
        'index'=> strlen($match),
        'value'=> $match,
      ];
    }

    /**
     * Match non-terminated string (terminator is Blackslash) from start
     * @param string $source
     * @return array
     */
    protected function string($source) {

      for ($length = strlen($source), $i = 0; $i < $length; $i++) {

        // Returns if character is terminator
        if ($source[$i] == '/') {
          return [
            'value'=> substr($source, 0, $i),
            'index'=> $i,
          ];
        }
      }

      return [
        'value'=> $source,
        'index'=> $length,
      ];
    }

    /**
     * Match hexadecimal string (0-9,a-f) from start
     * @param string $source
     * @return array
     */
    protected function hexadecimal($source) {

      for ($length = strlen($source), $i = 0; $i < $length; $i++) {

        $char = $source[$i];

        if (
          $char !== '0' &&
          $char !== '1' &&
          $char !== '2' &&
          $char !== '3' &&
          $char !== '4' &&
          $char !== '5' &&
          $char !== '6' &&
          $char !== '7' &&
          $char !== '8' &&
          $char !== '9' &&
          $char !== 'a' &&
          $char !== 'b' &&
          $char !== 'c' &&
          $char !== 'd' &&
          $char !== 'e' &&
          $char !== 'f'
        )
        {
          return [
            'value'=> substr($source, 0, $i),
            'index'=> $i,
          ];
        }
      }

      return [
        'value'=> $source,
        'index'=> $length,
      ];
    }

    /**
     * Match as integer from start (pattern: 0|[1-9]\d*)
     * @param string $source
     * @return array
     */
    protected function digit($source) {

      // zero can't starts with negative sign or follows other number
      if ($source[0] === '0') {
        return [
          'value'=> 0,
          'index'=> 1
        ];
      }

      $value = null;

      // check other symbol
      for ($length = strlen($source), $i = 0; $i < $length; $i++) {

        $char = $source[$i];

        if (
          $char !== '0' &&
          $char !== '1' &&
          $char !== '2' &&
          $char !== '3' &&
          $char !== '4' &&
          $char !== '5' &&
          $char !== '6' &&
          $char !== '7' &&
          $char !== '8' &&
          $char !== '9'
        ){
          return [
            'value'=> substr($source, 0, $i),
            'index'=> $i,
          ];
        }
      }

      return [
        'value'=> $source,
        'index'=> $length,
      ];
    }
  }
}