<?php
declare(strict_types=1);

namespace NatePage\Sniffs;

use Closure;
use Mockery;
use Mockery\MockInterface;
use SlevomatCodingStandard\Sniffs\TestCase as SlevomatCodingStandardTestCase;

class TestCase extends SlevomatCodingStandardTestCase
{
    /**
     * Create mock with configuration in a closure.
     *
     * @param string $class
     * @param null|\Closure $closure
     *
     * @return \Mockery\MockInterface
     *
     * @SuppressWarnings(PHPMD.StaticAccess) Inherited from Mockery
     */
    public function mock(string $class, ?Closure $closure = null): MockInterface
    {
        $mock = Mockery::mock($class);

        if ($closure !== null) {
            $closure($mock);
        }

        return $mock;
    }
}
