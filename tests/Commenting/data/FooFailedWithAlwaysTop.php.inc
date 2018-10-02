<?php
declare(strict_types=1);

namespace NatePage\Sniffs\Commenting\data;

class FooFailedWithAlwaysTop
{
    /**
     * Test foo.
     *
     * @annotation      Should be sorted below since this is not part of always top
     *
     * @param string $test Should be always top
     *
     * @return bool Should be always top
     *
     * @zTestAnnotation Should be sorted below
     *
     * @yTestAnnotation
     */
    public function testFoo(string $test): bool
    {
        return empty($test);
    }
}
