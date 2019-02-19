<?php
declare(strict_types=1);

namespace NatePage\Sniffs\Arrays\data;

use Mockery;
use Mockery\Mock;
use PHP_CodeSniffer\Fixer;
use PHP_CodeSniffer\Util\Tokens;
use PHPUnit\Framework\Assert;
use SlevomatCodingStandard\Helpers\AnnotationHelper;
use SlevomatCodingStandard\Helpers\SniffSettingsHelper;

class FooFailed
{
    /**
     * @var string
     */
    private const TEST_CONTSTANT = 'TEST_CONSTANT';

    /**
     * @var string
     */
    private static $testStatic = 'test static';

    /**
     * Simple array.
     *
     * @return mixed[]
     */
    public function testA(): array
    {
        return [
            'a_1st' => 1,
            'd_1st' => 4,
            'c_1st' => 3,
            'b_1st' => 2
        ];
    }

    /**
     * Multi-dimensional array sort 2nd level.
     *
     * @return mixed[]
     */
    public function testB(): array
    {
        return [
            'a_1st' => [
                'd_2nd' => 1,
                'b_2nd' => 2,
                'c_2nd' => 3,
                'a_2nd' => 4
            ],
            'b_1st' => 2,
            'c_1st' => 3,
            'd_1st' => 4
        ];
    }

    /**
     * Multi-dimensional array sort 1st level.
     *
     * @return mixed[]
     */
    public function testC(): array
    {
        return [
            'b_1st' => 2,
            'a_1st' => [
                'a_2nd' => 1,
                'b_2nd' => 2,
                'c_2nd' => 3,
                'd_2nd' => 4
            ],
            'd_1st' => 4,
            'c_1st' => 3
        ];
    }

    /**
     * Sequential array keys with class names as values.
     *
     * @return mixed[]
     */
    public function testD(): array
    {
        return [
            Mockery::class,
            AnnotationHelper::class,
            Assert::class
        ];
    }

    /**
     * String key value pairs with class names.
     *
     * @return mixed[]
     */
    public function testE(): array
    {
        return [
            SniffSettingsHelper::class => Mockery::class,
            Mock::class => Assert::class,
            Fixer::class => AnnotationHelper::class
        ];
    }

    /**
     * String key with function calls as values.
     *
     * @return mixed[]
     */
    public function testF(): array
    {
        return [
            'c_1st' => 'something3',
            'b_1st' => $this->getSomething2(),
            'a_1st' => $this->getSomething1()
        ];
    }

    /**
     * Multi-dimensional string key with object as values.
     *
     * @return mixed[]
     */
    public function testG(): array
    {
        return [
            'c_1st' => [
                'c_2nd' => 'something3',
                'b_2nd' => $this->getSomething2(),
                'a_2nd' => $this->getSomething1()
            ],
            'b_1st' => $this->getSomething2(),
            'a_1st' => $this->getSomething1(),
            'd_1st' => 'anything'
        ];
    }

    /**
     * Array with self references.
     *
     * @return mixed
     */
    public function testH(): array
    {
        return [
            'b_1st' => self::TEST_CONTSTANT,
            'a_1st' => self::$testStatic
        ];
    }

    /**
     * Array 3 levels deep.
     *
     * @return mixed
     */
    public function testI(): array
    {
        return [
            '3a_1st' => [
                '3d_2nd' => 1,
                '3b_2nd' => 2,
                '3c_2nd' => 3,
                '3a_2nd' => [
                    '3a_3rd' => 1,
                    '3b_3rd' => 2,
                    '3c_3rd' => 3,
                    '3d_3rd' => 4
                ]
            ],
            '3b_1st' => 2,
            '3c_1st' => 3,
            '3d_1st' => 4
        ];
    }

    /**
     * Self & static access.
     *
     * @return mixed[]
     */
    public function testJ(): array
    {
        return [
            self::$testStatic,
            self::TEST_CONTSTANT,
            Tokens::$stringTokens
        ];
    }

    /**
     * Sequential array with method calls as values.
     *
     * @return mixed[]
     */
    public function testK(): array
    {
        return [
            $this->getSomething2(),
            $this->getSomething1(),
            $this->getSelf()->getSomething1(),
            $this->getSomething3(1, 2, 3),
            $this->getSomething3(1, ['array' => 'params', 'test' => 'params'], 3),
            $this->getSomething3($this->getSomething1()),
            $this->getSelf($this)->getSomething3(1, $this->getSomething1())
        ];
    }

    public function testL(): array
    {
        $aee = '';
        $bee = '';
        $cee = '';

        return [$bee, $cee, $aee];
    }

    public function testM(): array
    {
        return [
            ['b_1st' => self::TEST_CONTSTANT, 'a_1st' => self::$testStatic],
            ['c_1st' => 'something3', 'b_1st' => $this->getSomething2(), 'a_1st' => $this->getSomething1()]
        ];
    }

    public function testN(): array
    {
        $array = $this->testA();

        return [
            $array['c_1st'],
            $array['b_1st'],
            $array['a_1st']
        ];
    }

    /**
     * Get self.
     *
     * @return \NatePage\Sniffs\Arrays\data\FooFailed
     */
    private function getSelf(...$params): self
    {
        return $this;
    }

    /**
     * Get something.
     *
     * @return string
     */
    private function getSomething1(): string
    {
        return 'something1';
    }

    /**
     * Get something.
     *
     * @return string
     */
    private function getSomething2(): string
    {
        return 'something2';
    }

    /**
     * Get something.
     *
     * @param mixed ...$params
     *
     * @return string
     */
    private function getSomething3(...$params): string
    {
        return 'something3';
    }
}
