<?php
declare(strict_types=1);

namespace NatePage\Sniffs\Commenting;

use Mockery\MockInterface;
use NatePage\Sniffs\TestCase;
use SlevomatCodingStandard\Helpers\AnnotationHelper;
use SlevomatCodingStandard\Helpers\DocCommentHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;

/**
 * @runTestsInSeparateProcesses
 */
class AnnotationSortingSniffTest extends TestCase
{
    /**
     * @var string
     */
    protected $stubsPath = __DIR__ . '/data/';

    /**
     * Test empty annotation.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testEmptyAnnotations(): void
    {
        $this->mock('alias:' . AnnotationHelper::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getAnnotations')->andReturn([])->once();
        });

        $report = self::checkFile($this->stubsPath . 'FooPassing.php.inc');

        self::assertNoSniffErrorInFile($report);
    }

    /**
     * Test failed.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testFailedDefault(): void
    {
        $report = self::checkFile($this->stubsPath . 'FooFailed.php.inc');

        self::assertSame(1, $report->getErrorCount());
    }

    /**
     * Test failed always top.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testFailedWithAlwaysTop(): void
    {
        $report = self::checkFile($this->stubsPath . 'FooFailedWithAlwaysTop.php.inc', [
            'alwaysTopAnnotations' => [
                '@covers',
                '@param',
                '@return',
                '@throws'
            ]
        ]);

        self::assertSame(2, $report->getErrorCount());
    }

    /**
     * Test isInline doc.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testFcStarterPointerIsNull(): void
    {
        $this->mock('alias:' . DocCommentHelper::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isInline')->andReturnFalse()->once();
        });

        $this->mock('alias:' . TokenHelper::class, function (MockInterface $mock): void {
            $mock->shouldReceive('findNextExcluding')->andReturnNull()->once();
        });

        $report = self::checkFile($this->stubsPath . 'FooPassing.php.inc');

        self::assertNoSniffErrorInFile($report);
    }

    /**
     * Test isInline doc.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testIsInline(): void
    {
        $this->mock('alias:' . DocCommentHelper::class, function (MockInterface $mock): void {
            $mock->shouldReceive('isInline')->andReturnTrue()->once();
        });
        $report = self::checkFile($this->stubsPath . 'FooPassing.php.inc');

        self::assertNoSniffErrorInFile($report);
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
        $report = self::checkFile($this->stubsPath . 'FooPassing.php.inc');

        self::assertNoSniffErrorInFile($report);
    }
}
