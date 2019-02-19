<?php
declare(strict_types=1);

namespace NatePage\Sniffs\Arrays;

use NatePage\Sniffs\TestCase;

/**
 * @covers \NatePage\Sniffs\Arrays\ArrayKeySortSniff
 */
class ArrayKeySortSniffTest extends TestCase
{

    /**
     * @var string
     */
    protected $stubsPath = __DIR__ . '/data/';

    /**
     * @var \PHP_CodeSniffer\Files\File
     */
    private $report;

    /**
     * Test failed.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testFailed(): void
    {
        $this->report = self::checkFile($this->stubsPath . 'FooFailed.php.inc');

        self::assertSame(10, $this->report->getErrorCount());

        $this->assertError(32, '`a_1st`, `d_1st`, `c_1st`, `b_1st`', '`a_1st`, `b_1st`, `c_1st`, `d_1st`');
        $this->assertError(48, '`d_2nd`, `b_2nd`, `c_2nd`, `a_2nd`', '`a_2nd`, `b_2nd`, `c_2nd`, `d_2nd`');
        $this->assertError(67, '`b_1st`, `a_1st`, `d_1st`, `c_1st`', '`a_1st`, `b_1st`, `c_1st`, `d_1st`');
        $this->assertError(87, '`Mockery`, `AnnotationHelper`, `Assert`', '`AnnotationHelper`, `Assert`, `Mockery`');
        $this->assertError(101, '`SniffSettingsHelper`, `Mock`, `Fixer`', '`Fixer`, `Mock`, `SniffSettingsHelper`');
        $this->assertError(115, '`c_1st`, `b_1st`, `a_1st`', '`a_1st`, `b_1st`, `c_1st`');
        $this->assertError(129, '`c_1st`, `b_1st`, `a_1st`, `d_1st`', '`a_1st`, `b_1st`, `c_1st`, `d_1st`');
        $this->assertError(130, '`c_2nd`, `b_2nd`, `a_2nd`', '`a_2nd`, `b_2nd`, `c_2nd`');
        $this->assertError(148, '`b_1st`, `a_1st`', '`a_1st`, `b_1st`');
        $this->assertError(162, '`3d_2nd`, `3b_2nd`, `3c_2nd`, `3a_2nd`', '`3a_2nd`, `3b_2nd`, `3c_2nd`, `3d_2nd`');
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

    /**
     * Assert error.
     *
     * @param int $line
     * @param string $actual
     * @param string $expected
     * @param string|null $code
     *
     * @return void
     */
    private function assertError(int $line, string $actual, string $expected, ?string $code = null): void
    {
        $message = \sprintf('Expected array should be alphabetically sorted. '
            . 'Found %s, '
            . 'should be %s', $actual, $expected);

        self::assertSniffError($this->report, $line, $code ?? ArrayKeySortSniff::CODE_ARRAY_KEY_SORT, $message);
    }
}
