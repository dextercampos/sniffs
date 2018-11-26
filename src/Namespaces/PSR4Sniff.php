<?php
declare(strict_types=1);

namespace NatePage\Sniffs\Namespaces;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\ClassHelper;
use SlevomatCodingStandard\Helpers\NamespaceHelper;

/**
 * @SuppressWarnings(PHPMD.StaticAccess) Inherited SlevomatCodingStandards
 */
class PSR4Sniff implements Sniff
{
    /**
     * @var string
     */
    public const CODE_NAMESPACE_VIOLATION = 'PSR4Namespace';

    /**
     * @var string
     */
    public const CODE_NO_COMPOSER_AUTOLOAD_DEFINED = 'NoComposerAutoloadDefined';

    /**
     * @var string
     */
    public $composerJsonPath = 'composer.json';

    /**
     * @var mixed[]
     */
    private static $composerContents = [];

    /**
     * @var string
     */
    private $code = '';

    /**
     * @var string
     */
    private $expectedNamespace = '';

    /**
     * @var \PHP_CodeSniffer\Files\File
     */
    private $phpcsFile;

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $openPointer
     *
     * @return void
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
     * @phpcsSuppress EoneoPay.Commenting.FunctionComment.ScalarTypeHintMissing
     */
    public function process(File $phpcsFile, $openPointer): void
    {
        $this->phpcsFile = $phpcsFile;

        $classFqn = ClassHelper::getFullyQualifiedName($phpcsFile, $openPointer);

        if ($this->isPsr4Compliant($classFqn) === true || $this->isPsr4Compliant($classFqn, true) === true) {
            return;
        }

        $this->addError($openPointer);
    }

    /**
     * @return mixed[]
     */
    public function register(): array
    {
        return [
            T_CLASS,
            T_INTERFACE,
            T_TRAIT
        ];
    }

    /**
     * Add error for namespace violation.
     *
     * @param int $openPointer
     *
     * @return void
     */
    private function addError(int $openPointer): void
    {
        if ($this->code === self::CODE_NO_COMPOSER_AUTOLOAD_DEFINED) {
            $message = \sprintf('No autoload entries found in %s.', $this->composerJsonPath);
            $this->phpcsFile->addError($message, $this->phpcsFile->findNext(\T_NAMESPACE, 0), $this->code);

            return;
        }
        $message = \sprintf(
            'Namespace name does not match PSR-4 project structure. It should be `%s` instead of `%s`.',
            $this->expectedNamespace,
            NamespaceHelper::findCurrentNamespaceName($this->phpcsFile, $openPointer)
        );

        $this->phpcsFile->addError($message, $this->phpcsFile->findNext(\T_NAMESPACE, 0), $this->code);
    }

    /**
     * Get composer contents.
     *
     * @return mixed[]
     */
    private function getComposerContents(): array
    {
        if (\count(self::$composerContents) > 0) {
            return self::$composerContents;
        }

        $basePath = $this->phpcsFile->config !== null ? $this->phpcsFile->config->getSettings()['basepath'] : '';

        $composerFile = $basePath . $this->composerJsonPath;

        return self::$composerContents = \json_decode(\file_get_contents($composerFile), true);
    }

    /**
     * Check if class namespace is psr-4 compliant
     *
     * @param string $classFqn
     * @param bool|null $isDev
     *
     * @return bool
     */
    private function isPsr4Compliant(string $classFqn, ?bool $isDev = null): bool
    {
        $psr4s = $this->getComposerContents()[\sprintf('autoload%s', ($isDev === true) ? '-dev' : '')]['psr-4'] ?? [];

        if (empty($psr4s) === true) {
            $this->code = self::CODE_NO_COMPOSER_AUTOLOAD_DEFINED;

            return false;
        }

        $classFilename = $this->phpcsFile->getFilename();

        foreach ($psr4s as $baseNamespace => $basePath) {
            $testPath = \ltrim(\str_replace([$baseNamespace, '\\'], [$basePath, '/'], $classFqn), '/');

            if (\strpos($classFilename, $testPath) !== false) {
                return true;
            }

            $basePathPosition = \strpos($classFilename, $basePath);

            if ($basePathPosition !== false) {

                $relativePath = \substr(\dirname($classFilename), $basePathPosition, \strlen($classFilename));

                $this->expectedNamespace = \str_replace(
                    [$basePath, '/'],
                    [$baseNamespace, '\\'],
                    $relativePath
                );
            }
        }

        $this->code = self::CODE_NAMESPACE_VIOLATION;

        return false;
    }
}