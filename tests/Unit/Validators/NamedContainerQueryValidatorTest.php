<?php

declare(strict_types=1);

namespace TalesFromADev\TailwindMerge\Tests\Unit\Validators;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TalesFromADev\TailwindMerge\Validators\NamedContainerQueryValidator;

final class NamedContainerQueryValidatorTest extends TestCase
{
    /**
     * @return list<array{string, bool}>
     */
    public static function valueProvider(): array
    {
        return [
            ['@container/sidebar', true],
            ['@container/main', true],
            ['@container/a', true],
            ['@container-size/sidebar', true],
            ['@container-size/main', true],
            ['@container-normal/sidebar', true],
            ['@container-normal/main', true],

            ['@container', false],
            ['@container-size', false],
            ['@container-normal', false],
            ['@container/', false],
            ['@container-size/', false],
            ['@container-normal/', false],
            ['container/sidebar', false],
            ['@containers/sidebar', false],
        ];
    }

    #[DataProvider('valueProvider')]
    public function testIsNamedContainerQuery(string $value, bool $expected): void
    {
        $this->assertSame($expected, NamedContainerQueryValidator::validate($value));
    }
}
