<?php
declare(strict_types=1);

/**
 * Checks array keys should be sorted alphabetically if string.
 *
 * @author    Nathan Page <nathan.page@loyaltycorp.com.au>
 *
 * @copyright 2018 Loyalty Corp Pty Ltd (ABN 39 615 958 873)
 *
 * @license   https://github.com/loyaltycorp/standards/blob/master/licence BSD Licence
 */

namespace NatePage\Sniffs\Arrays;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\TokenHelper;

class ArrayKeySortSniff implements Sniff
{
    /**
     * @var string
     */
    public const CODE_ARRAY_KEY_SORT = 'ArrayKeysSortAlphabetically';

    private const PARSE_CONTEXT_FUNCTION_CALL = 'PARSE_CONTEXT_FUNCTION_CALL';

    private const PARSE_CONTEXT_NEW = 'PARSE_CONTEXT_NEW';

    private const PARSE_CONTEXT_SELF = 'PARSE_CONTEXT_SELF';

    private const PARSE_CONTEXT_STATIC_ACCESS = 'PARSE_CONTEXT_STATIC_ACCESS';

    private const PARSE_CONTEXT_VALUE = 'PARSE_CONTEXT_VALUE';

    /**
     * @var int
     */
    private $currentNesting = 0;

    /**
     * @var null|string
     */
    private $currentParseContext = null;

    /**
     * @var \PHP_CodeSniffer\Files\File
     */
    private $phpcsFile;

    /**
     * @var mixed[]
     */
    private $tokens;

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack passed in $tokens.
     *
     * @return void
     *
     * @phpcsSuppress NatePage.Commenting.FunctionComment.ScalarTypeHintMissing
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $this->phpcsFile = $phpcsFile;
        $this->tokens = $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['code'] !== T_OPEN_SHORT_ARRAY) {
            return;
        }

        $actual = $this->getArrayString(
            $tokens,
            $tokens[$stackPtr]['bracket_opener'],
            $tokens[$stackPtr]['bracket_closer']
        );

        if (count($actual) <= 1) {
            return;
        }

        $sorted = $this->checkIsSorted($actual);
        if ($sorted === true) {
            return;
        }

        $expected = $actual;
        \asort($expected);

        $phpcsFile->addError(
            \sprintf(
                'Expected array should be alphabetically sorted. Found `%s`, should be `%s`',
                \implode('`, `', $actual),
                \implode('`, `', $expected)
            ),
            $stackPtr,
            self::CODE_ARRAY_KEY_SORT
        );
    }

    /**
     * Returns the token types that this sniff is interested in
     *
     * @return int[]
     */
    public function register(): array
    {
        return [
            \T_OPEN_SHORT_ARRAY
        ];
    }

    /**
     * Get array string
     *
     * @param mixed[] $tokens
     * @param int $start
     * @param int $end
     *
     * @return string[]
     */
    protected function getArrayString(array $tokens, int $start, int $end): array
    {
        $arrayString = '';
        $level = 0;

        for ($pos = $start; $pos <= $end; $pos++) {
            if ($tokens[$pos]['code'] === \T_WHITESPACE) {
                continue;
            }

            $content = $tokens[$pos]['content'];
            $code = $tokens[$pos]['code'];

            $continue = $this->skipNextLevel($level, $code) === true
                || $this->removeValue($arrayString, $content, $code) === true
                || $this->escapeSelfAccess($arrayString, $content, $code) === true
                || $this->escapeVariable($arrayString, $content, $code, $pos) === true
                || $this->escapeFunctionCall($arrayString, $content, $code, $pos) === true
                || $this->escapeStaticAccess($arrayString, $content, $code, $pos) === true;
                // || $this->escapeNew($arrayString, $content, $code, $pos) === true;
            if ($continue === true) {
                continue;
            }

            $arrayString .= $content;
        }

        $array = null;
        $forEvaluation = '$array=' . \rtrim(\rtrim($arrayString, ','), ';') . ';';
        $forEvaluation = $this->removeEmptyElements($forEvaluation);
        dump($forEvaluation);
        eval($forEvaluation);
        if ($array === null) {
            return [];
        }

        if ($this->isSequential($array)) {
            return $array;
        }

        return array_keys($array);
    }

    /**
     * Check array is sorted
     *
     * @param $array
     *
     * @return bool
     */
    private function checkIsSorted(array $array): bool
    {
        $original = $array;
        sort($array);

        return $array === $original;
    }

    /**
     * Escape variable function call.
     *
     * @param string $arrayString
     * @param string $content
     * @param string|int $code
     * @param int $pos
     *
     * @return bool
     */
    private function escapeFunctionCall(
        string &$arrayString,
        string $content,
        $code,
        $pos
    ): bool {
        $isFunctionCall = $this->currentParseContext === null && $code === \T_VARIABLE
            && $this->findNextCode($pos, [\T_VARIABLE]) === \T_OBJECT_OPERATOR;

        if ($isFunctionCall === true) {
            $this->currentParseContext = self::PARSE_CONTEXT_FUNCTION_CALL;

            // Add quote in front of `$`.
            $arrayString = \sprintf('%s\'%s', $arrayString, $content);

            return true;
        }

        if ($this->currentParseContext !== self::PARSE_CONTEXT_FUNCTION_CALL) {
            return false;
        }

        if ($code === \T_OPEN_PARENTHESIS) {
            $this->currentNesting++;
        }

        if ($code !== \T_CLOSE_PARENTHESIS) {
            return false;
        }

        $this->currentNesting--;

        if ($this->findNextCode($pos) === \T_OBJECT_OPERATOR) {
            return false;
        }

        if ($this->currentNesting > 0) {
            return false;
        }

        $arrayString = \sprintf('%s%s\'', $arrayString, $content);
        $this->currentParseContext = null;

        return true;
    }

    /**
     * Escape instantiation.
     *
     * @param string $arrayString
     * @param string $content
     * @param $code
     * @param $pos
     *
     * @return bool
     */
    private function escapeNew(string &$arrayString, string $content, $code, $pos): bool
    {
        if ($this->currentParseContext === null && $code === \T_NEW) {
            $this->currentParseContext = self::PARSE_CONTEXT_NEW;
            $arrayString .= \sprintf('\'%s', $content);

            return true;
        }
        return false;
    }

    /**
     * Escape self access.
     *
     * @param string $arrayString
     * @param string $content
     * @param string|int $code
     *
     * @return bool
     */
    private function escapeSelfAccess(string &$arrayString, string $content, $code): bool
    {
        if ($this->currentParseContext === null && ($code === \T_SELF || $code === \T_PARENT)) {
            $this->currentParseContext = self::PARSE_CONTEXT_SELF;

            $arrayString .= \sprintf('\'%s', $content);

            return true;
        }

        if ($this->currentParseContext === self::PARSE_CONTEXT_SELF && $code === \T_DOUBLE_COLON) {
            return false;
        }

        if ($this->currentParseContext !== self::PARSE_CONTEXT_SELF) {
            return false;
        }

        $arrayString .= \sprintf('%s\'', $content);
        $this->currentParseContext = null;

        return true;
    }

    /**
     * Escape static access.
     *
     * @param string $arrayString
     * @param string $content
     * @param $code
     * @param $pos
     *
     * @return bool
     */
    private function escapeStaticAccess(string &$arrayString, string $content, $code, $pos): bool
    {
        $isStaticAccess = $this->currentParseContext === null
            && $code === \T_STRING
            && $this->findNextCode($pos) === \T_DOUBLE_COLON;

        if ($isStaticAccess === true) {
            $this->currentParseContext = self::PARSE_CONTEXT_STATIC_ACCESS;
            $arrayString = \sprintf('%s\'%s', $arrayString, $content);

            return true;
        }

        if ($this->currentParseContext !== self::PARSE_CONTEXT_STATIC_ACCESS) {
            return false;
        }

        if ($this->findPreviousCode($pos) !== \T_DOUBLE_COLON) {
            return false;
        }

        $arrayString = \sprintf('%s%s\'', $arrayString, $content);
        $this->currentParseContext = null;

        return true;
    }

    /**
     * Escape variable.
     *
     * @param string $arrayString
     * @param string $content
     * @param $code
     * @param $pos
     *
     * @return bool
     */
    private function escapeVariable(string &$arrayString, string $content, $code, $pos): bool
    {
        if ($this->currentParseContext !== null || $code !== \T_VARIABLE) {
            return false;
        }

        $nextCode = $this->findNextCode($pos, [\T_VARIABLE]);

        if ($nextCode === \T_OBJECT_OPERATOR || $nextCode === \T_DOUBLE_COLON) {
            return false;
        }

        $arrayString .= \sprintf('\'%s\'', $content);

        return true;
    }

    /**
     * Find next token code.
     *
     * @param int $currentPosition
     * @param mixed[] $excludes
     *
     * @return int|string
     */
    private function findNextCode(int $currentPosition, ?array $excludes = null)
    {
        $alwaysExclude = [\T_WHITESPACE];

        return $this->tokens[TokenHelper::findNextExcluding(
            $this->phpcsFile,
            \array_merge($alwaysExclude, $excludes ?? []),
            $currentPosition + 1
        )]['code'];
    }

    /**
     * Find previous token code.
     *
     * @param int $currentPosition
     * @param mixed[] $excludes
     *
     * @return int|string
     */
    private function findPreviousCode(int $currentPosition, ?array $excludes = null)
    {
        $alwaysExclude = [\T_WHITESPACE];

        return $this->tokens[TokenHelper::findPreviousExcluding(
            $this->phpcsFile,
            \array_merge($alwaysExclude, $excludes ?? []),
            $currentPosition - 1
        )]['code'];
    }

    /**
     * Check if array keys are sequential.
     *
     * @param mixed[] $array
     *
     * @return bool
     */
    private function isSequential(array $array): bool
    {
        return array_keys($array) === \range(0, \count($array) - 1);
    }

    /**
     * Cleanup.
     *
     * @param string $forEvaluation
     *
     * @return string
     */
    private function removeEmptyElements(string $forEvaluation): string
    {
        if (strpos($forEvaluation, '[,]') !== false) {
            return str_replace('[,]', '[]', $forEvaluation);
        }

        if (strpos($forEvaluation, ',,') === false) {
            return $forEvaluation;
        }

        return $this->removeEmptyElements(str_replace(',,', '', $forEvaluation));
    }

    /**
     * @param string $arrayString
     * @param string $content
     * @param string|int $code
     *
     * @return bool
     */
    private function removeValue(string &$arrayString, string $content, $code): bool
    {
        if ($this->currentParseContext === null && $code === \T_DOUBLE_ARROW) {
            $this->currentParseContext = self::PARSE_CONTEXT_VALUE;

            // Set value to null.
            $arrayString .= $content . 'null';

            return true;
        }

        if ($this->currentParseContext === self::PARSE_CONTEXT_VALUE && $code !== \T_COMMA && $code !== \T_CLOSE_SHORT_ARRAY) {
            return true;
        }

        if ($this->currentParseContext === self::PARSE_CONTEXT_VALUE && ($code === \T_COMMA || $code === \T_CLOSE_SHORT_ARRAY)) {
            $arrayString .= $content;

            $this->currentParseContext = null;

            return true;
        }

        return false;
    }

    /**
     * Should skip next levels.
     *
     * @param int $level
     * @param $code
     *
     * @return bool should continue or not
     */
    private function skipNextLevel(int &$level, $code): bool
    {
        if ($code === \T_OPEN_SHORT_ARRAY) {
            $level++;
        }

        if ($level >= 2 && $code === \T_CLOSE_SHORT_ARRAY) {
            $level--;

            if ($level !== 1) {
                return true;
            }

            return true;
        }

        if ($level >= 2) {
            return true;
        }

        return false;
    }
}
