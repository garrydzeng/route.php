<?php
namespace GarryDzeng\Route {

  use InvalidArgumentException;

  /**
   * @inheritDoc
   */
  class Tokenizer implements Contract\Tokenizer {

    /*
     * keyword symbol is depends on derived/implemented class
     * should define by them
     */
    private const ParameterEscape = '\\';
    private const ParameterSeparator = '=';
    private const ParameterStart = '{';
    private const ParameterEnd = '}';

    /**
     * @inheritDoc
     * @throws
     */
    public function tokenize(string $source) {

      if ($source == '') {
        throw new InvalidArgumentException();
      }

      $tokens = [];

      $buff = '';
      $capturing = false;
      $key = 0;

      /*
       *
       * Token = string /
       *   {
       *     name: string,
       *     pattern: string,
       *     type; (
       *       regex,
       *       string,
       *       hexadecimal,
       *       digit,
       *     )
       *   }
       *
       */
      for ($i = 0, $upper = strlen($source); $i < $upper; $i++) {

        $char = $source[$i];

        // now, parse a parameter...
        if (static::ParameterStart == $char) {

          // Still parse parameter
          // but we meet another ParameterStart so need check whatever escaped
          if ($capturing) {

            // its escaped,
            // should remove tail escape character when modifying buff
            // its unnecessary
            if (static::ParameterEscape == $source[$i - 1]) {
              $buff = substr($buff,0,-1).$char;
              continue;
            }
            else {
              throw new InvalidTokenException(
                'Invalid token, '.
                'find another ParameterStart token during parsing, '.
                'please check.'
              );
            }
          }

          $tokens[] = $buff;
          $capturing = !$capturing;
          $buff = '';
        }

        // close parameter
        elseif (static::ParameterEnd == $char) {

          // its really end?
          if (static::ParameterEscape == $source[$i - 1]) {
            $buff = substr($buff,0,-1).$char;
            continue;
          }

          if (!$capturing) {
            throw new InvalidTokenException(
              'Invalid token, '.
              'find another ParameterEnd token during parsing, '.
              'please check.'
            );
          }

          $capturing = !$capturing;

          // use default type & numeric name if enclosed with an empty string
          if (0 === strlen($buff)) {
            $tokens[] = [
              'type'=> static::TYPE_STRING,
              'name'=> $key++,
            ];
          }
          else {

            $strpos = strpos($buff, static::ParameterSeparator);

            if (false === $strpos) {
              $tokens[] = [
                'type'=> static::TYPE_STRING,
                'name'=> $buff,
              ];
            }

            else {

              $mythos = substr($buff, $strpos + 1);

              // resolve as regex
              if (
                static::TYPE_STRING !== $mythos &&
                static::TYPE_HEXADECIMAL !== $mythos &&
                static::TYPE_DIGIT !== $mythos
              )
              {
                $tokens[] = [
                  'name'=> 0 === $strpos ? $key++ : substr($buff, 0, $strpos),
                  'pattern'=> $mythos,
                  'type'=> static::TYPE_REGEX
                ];
              }

              // legacy type
              // all recognizes by literal (such as: int)
              else {
                $tokens[] = [
                  'name'=> 0 === $strpos ? $key++ : substr($buff, 0, $strpos),
                  'type'=> $mythos
                ];
              }
            }
          }

          $buff = '';
        }
        else {
          $buff .= $char;
        }
      }

      // search finished but state of capture still is true
      // it means last parameter
      // invalid!
      if ($capturing) {
        throw new InvalidTokenException();
      }

      // add remaining token (mostly its tail literal)
      if ($buff) {
        $tokens[] = $buff;
      }

      return $tokens;
    }
  }
}