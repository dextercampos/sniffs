<?php
declare(strict_types=1);

namespace NatePage\Sniffs\Commenting\data;

class FooPassing
{
    /**
     * Test foo.
     *
     * @param string $test
     *
     * @return bool
     *
     * @yTestAnnotation
     *
     * @zTestAnnotation
     */
    public function testFoo(string $test): bool
    {
        return empty($test);
    }
}
