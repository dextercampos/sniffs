<?php
declare(strict_types=1);

namespace NatePage\Sniffs\Namespaces;

use NatePage\Sniffs\TestCase;

/**
 * @covers \NatePage\Sniffs\Namespaces\PSR4Sniff
 */
class PSR4SniffTest extends TestCase
{
    /**
     * @var string
     */
    protected $stubsPath = __DIR__ . '/data/';

    /**
     * Test empty composer json file.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testEmptyComposerJson(): void
    {
        $report = self::checkFile(
            $this->stubsPath . 'FooFailed.php.inc',
            ['composerJsonPath' => $this->stubsPath . 'composer-empty.json']
        );

        self::assertSniffError(
            $report,
            4,
            PSR4Sniff::CODE_NO_COMPOSER_AUTOLOAD_DEFINED,
            \sprintf('No autoload entries found in %s.', $this->stubsPath . 'composer-empty.json')
        );

        self::assertSame(1, $report->getErrorCount());
    }

    /**
     * Test failed.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testFailed(): void
    {
        $report = self::checkFile(
            $this->stubsPath . 'FooFailed.php.inc',
            ['composerJsonPath' => $this->stubsPath . 'composer.json']
        );

        self::assertSniffError(
            $report,
            4,
            PSR4Sniff::CODE_NAMESPACE_VIOLATION,
            \sprintf(
                'Namespace name does not match PSR-4 project structure. It should be `%s` instead of `%s`.',
                'NatePage\Sniffs\Namespaces\data',
                'NatePage\Sniffs\Namespaces'
            )
        );

        self::assertSame(1, $report->getErrorCount());
    }

    /**
     * Test passed.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testPassed(): void
    {
        $report = self::checkFile(
            $this->stubsPath . 'FooPassed.php.inc',
            ['composerJsonPath' => $this->stubsPath . 'composer.json']
        );

        self::assertNoSniffErrorInFile($report);
    }
}
