<?php

namespace Maryo\NeonUpdater\Tests;

use Maryo\NeonUpdater\NeonUpdater;
use Override;
use PHPUnit\Framework\TestCase;

class NeonUpdaterTest extends TestCase
{
    #[Override]
    public static function setUpBeforeClass(): void
    {
        NeonUpdater::$defaultIndentation = '    ';
    }

    public function testReplacingEmptyNeon(): void
    {
        self::assertSame('value', NeonUpdater::update('', [], 'value'));
        self::assertSame(
            <<<'NEON'
                '''
                    foo
                    bar
                '''
                NEON,
            NeonUpdater::update('', [], "foo\nbar"),
        );
        self::assertSame('- value', NeonUpdater::update('', [], ['value']));
        self::assertSame(
            <<<'NEON'
                - '''
                    foo
                    bar
                '''
                NEON,
            NeonUpdater::update('', [], ["foo\nbar"]),
        );
        self::assertSame(
            <<<'NEON'
                - foo
                - bar
                NEON,
            NeonUpdater::update('', [], ['foo', 'bar']),
        );
        self::assertSame('foo: foo', NeonUpdater::update('', [], ['foo' => 'foo']));
        self::assertSame(
            <<<'NEON'
                foo: foo
                bar: bar
                NEON,
            NeonUpdater::update('', [], ['foo' => 'foo', 'bar' => 'bar']),
        );
    }

    public function testReplacingFirstFirstLevelKey(): void
    {
        $neon = <<<'NEON'
            foo: foo
            bar: bar
            NEON;
        self::assertSame(
            <<<'NEON'
                foo: value
                bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo'], 'value'),
        );
        self::assertSame(
            <<<'NEON'
                foo: '''
                    foo
                    bar
                '''
                bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo'], "foo\nbar"),
        );
        self::assertSame(
            <<<'NEON'
                foo:
                    - value
                bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo'], ['value']),
        );
        self::assertSame(
            <<<'NEON'
                foo:
                    - foo
                    - bar
                bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo'], ['foo', 'bar']),
        );
        self::assertSame(
            <<<'NEON'
                foo:
                    foo: foo
                    bar: bar
                bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo'], ['foo' => 'foo', 'bar' => 'bar']),
        );
    }

    public function testReplacingMiddleFirstLevelKey(): void
    {
        $neon = <<<'NEON'
            foo: foo
            bar: bar
            baz: baz
            NEON;
        self::assertSame(
            <<<'NEON'
                foo: foo
                bar: value
                baz: baz
                NEON,
            NeonUpdater::update($neon, ['bar'], 'value'),
        );
        self::assertSame(
            <<<'NEON'
                foo: foo
                bar: '''
                    foo
                    bar
                '''
                baz: baz
                NEON,
            NeonUpdater::update($neon, ['bar'], "foo\nbar"),
        );
        self::assertSame(
            <<<'NEON'
                foo: foo
                bar:
                    - value
                baz: baz
                NEON,
            NeonUpdater::update($neon, ['bar'], ['value']),
        );
        self::assertSame(
            <<<'NEON'
                foo: foo
                bar:
                    - foo
                    - bar
                baz: baz
                NEON,
            NeonUpdater::update($neon, ['bar'], ['foo', 'bar']),
        );
        self::assertSame(
            <<<'NEON'
                foo: foo
                bar:
                    foo: foo
                    bar: bar
                baz: baz
                NEON,
            NeonUpdater::update($neon, ['bar'], ['foo' => 'foo', 'bar' => 'bar']),
        );
    }

    public function testReplacingLastFirstLevelKey(): void
    {
        $neon = <<<'NEON'
            foo: foo
            bar: bar
            NEON;
        self::assertSame(
            <<<'NEON'
                foo: foo
                bar: value
                NEON,
            NeonUpdater::update($neon, ['bar'], 'value'),
        );
        self::assertSame(
            <<<'NEON'
                foo: foo
                bar: '''
                    foo
                    bar
                '''
                NEON,
            NeonUpdater::update($neon, ['bar'], "foo\nbar"),
        );
        self::assertSame(
            <<<'NEON'
                foo: foo
                bar:
                    - value
                NEON,
            NeonUpdater::update($neon, ['bar'], ['value']),
        );
        self::assertSame(
            <<<'NEON'
                foo: foo
                bar:
                    - foo
                    - bar
                NEON,
            NeonUpdater::update($neon, ['bar'], ['foo', 'bar']),
        );
        self::assertSame(
            <<<'NEON'
                foo: foo
                bar:
                    foo: foo
                    bar: bar
                NEON,
            NeonUpdater::update($neon, ['bar'], ['foo' => 'foo', 'bar' => 'bar']),
        );
    }

    public function testAppendingToFirstFirstLevelKey(): void
    {
        $neon = <<<'NEON'
            foo:
                foo: foo
            bar: bar
            NEON;
        self::assertSame(
            <<<'NEON'
                foo:
                    foo: foo
                    bar: value
                bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo', 'bar'], 'value'),
        );
        self::assertSame(
            <<<'NEON'
                foo:
                    foo: foo
                    bar: '''
                        foo
                        bar
                    '''
                bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo', 'bar'], "foo\nbar"),
        );
        self::assertSame(
            <<<'NEON'
                foo:
                    foo: foo
                    bar:
                        - value
                bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo', 'bar'], ['value']),
        );
        self::assertSame(
            <<<'NEON'
                foo:
                    foo: foo
                    bar:
                        - foo
                        - bar
                bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo', 'bar'], ['foo', 'bar']),
        );
        self::assertSame(
            <<<'NEON'
                foo:
                    foo: foo
                    bar:
                        baz:
                            foo: foo
                            bar: bar
                bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo', 'bar', 'baz'], ['foo' => 'foo', 'bar' => 'bar']),
        );
    }

    public function testAppendingToMiddleFirstLevelKey(): void
    {
        $neon = <<<'NEON'
            foo: foo
            bar: bar
            baz: baz
            NEON;
        self::assertSame(
            <<<'NEON'
                foo: foo
                bar:
                    foo: value
                baz: baz
                NEON,
            NeonUpdater::update($neon, ['bar', 'foo'], 'value'),
        );
        self::assertSame(
            <<<'NEON'
                foo: foo
                bar:
                    foo: '''
                        foo
                        bar
                    '''
                baz: baz
                NEON,
            NeonUpdater::update($neon, ['bar', 'foo'], "foo\nbar"),
        );
        self::assertSame(
            <<<'NEON'
                foo: foo
                bar:
                    foo:
                        - value
                baz: baz
                NEON,
            NeonUpdater::update($neon, ['bar', 'foo'], ['value']),
        );
        self::assertSame(
            <<<'NEON'
                foo: foo
                bar:
                    foo:
                        - foo
                        - bar
                baz: baz
                NEON,
            NeonUpdater::update($neon, ['bar', 'foo'], ['foo', 'bar']),
        );
        self::assertSame(
            <<<'NEON'
                foo: foo
                bar:
                    foo:
                        bar:
                            foo: foo
                            bar: bar
                baz: baz
                NEON,
            NeonUpdater::update($neon, ['bar', 'foo', 'bar'], ['foo' => 'foo', 'bar' => 'bar']),
        );
    }

    public function testAppendingToLastFirstLevelKey(): void
    {
        $neon = <<<'NEON'
            foo: foo
            bar: bar
            NEON;
        self::assertSame(
            <<<'NEON'
                foo: foo
                bar:
                    foo: value
                NEON,
            NeonUpdater::update($neon, ['bar', 'foo'], 'value'),
        );
        self::assertSame(
            <<<'NEON'
                foo: foo
                bar:
                    foo: '''
                        foo
                        bar
                    '''
                NEON,
            NeonUpdater::update($neon, ['bar', 'foo'], "foo\nbar"),
        );
        self::assertSame(
            <<<'NEON'
                foo: foo
                bar:
                    foo:
                        - value
                NEON,
            NeonUpdater::update($neon, ['bar', 'foo'], ['value']),
        );
        self::assertSame(
            <<<'NEON'
                foo: foo
                bar:
                    foo:
                        - foo
                        - bar
                NEON,
            NeonUpdater::update($neon, ['bar', 'foo'], ['foo', 'bar']),
        );
        self::assertSame(
            <<<'NEON'
                foo: foo
                bar:
                    foo:
                        bar:
                            foo: foo
                            bar: bar
                NEON,
            NeonUpdater::update($neon, ['bar', 'foo', 'bar'], ['foo' => 'foo', 'bar' => 'bar']),
        );
    }

    public function testReplacingFirstSecondLevelKey(): void
    {
        $neon = <<<'NEON'
            foo:
                foo: foo
                bar: bar
            NEON;
        self::assertSame(
            <<<'NEON'
                foo:
                    foo: value
                    bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo', 'foo'], 'value'),
        );
        self::assertSame(
            <<<'NEON'
                foo:
                    foo: '''
                        foo
                        bar
                    '''
                    bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo', 'foo'], "foo\nbar"),
        );
        self::assertSame(
            <<<'NEON'
                foo:
                    foo:
                        - value
                    bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo', 'foo'], ['value']),
        );
        self::assertSame(
            <<<'NEON'
                foo:
                    foo:
                        - foo
                        - bar
                    bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo', 'foo'], ['foo', 'bar']),
        );
        self::assertSame(
            <<<'NEON'
                foo:
                    foo:
                        foo: foo
                        bar: bar
                    bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo', 'foo'], ['foo' => 'foo', 'bar' => 'bar']),
        );
    }

    public function testReplacingMiddleSecondLevelKey(): void
    {
        $neon = <<<'NEON'
            foo:
                foo: foo
                bar: bar
                baz: baz
            NEON;
        self::assertSame(
            <<<'NEON'
                foo:
                    foo: foo
                    bar: value
                    baz: baz
                NEON,
            NeonUpdater::update($neon, ['foo', 'bar'], 'value'),
        );
        self::assertSame(
            <<<'NEON'
                foo:
                    foo: foo
                    bar: '''
                        foo
                        bar
                    '''
                    baz: baz
                NEON,
            NeonUpdater::update($neon, ['foo', 'bar'], "foo\nbar"),
        );
        self::assertSame(
            <<<'NEON'
                foo:
                    foo: foo
                    bar:
                        - value
                    baz: baz
                NEON,
            NeonUpdater::update($neon, ['foo', 'bar'], ['value']),
        );
        self::assertSame(
            <<<'NEON'
                foo:
                    foo: foo
                    bar:
                        - foo
                        - bar
                    baz: baz
                NEON,
            NeonUpdater::update($neon, ['foo', 'bar'], ['foo', 'bar']),
        );
        self::assertSame(
            <<<'NEON'
                foo:
                    foo: foo
                    bar:
                        foo: foo
                        bar: bar
                    baz: baz
                NEON,
            NeonUpdater::update($neon, ['foo', 'bar'], ['foo' => 'foo', 'bar' => 'bar']),
        );
    }

    public function testReplacingLastSecondLevelKey(): void
    {
        $neon = <<<'NEON'
            foo:
                foo: foo
                bar: bar
            NEON;
        self::assertSame(
            <<<'NEON'
                foo:
                    foo: foo
                    bar: value
                NEON,
            NeonUpdater::update($neon, ['foo', 'bar'], 'value'),
        );
        self::assertSame(
            <<<'NEON'
                foo:
                    foo: foo
                    bar: '''
                        foo
                        bar
                    '''
                NEON,
            NeonUpdater::update($neon, ['foo', 'bar'], "foo\nbar"),
        );
        self::assertSame(
            <<<'NEON'
                foo:
                    foo: foo
                    bar:
                        - value
                NEON,
            NeonUpdater::update($neon, ['foo', 'bar'], ['value']),
        );
        self::assertSame(
            <<<'NEON'
                foo:
                    foo: foo
                    bar:
                        - foo
                        - bar
                NEON,
            NeonUpdater::update($neon, ['foo', 'bar'], ['foo', 'bar']),
        );
        self::assertSame(
            <<<'NEON'
                foo:
                    foo: foo
                    bar:
                        foo: foo
                        bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo', 'bar'], ['foo' => 'foo', 'bar' => 'bar']),
        );
    }

    public function testAppendingToSecondLevelKey(): void
    {
        $neon = <<<'NEON'
            foo:
                foo: foo
                bar: bar
            baz: baz
            NEON;
        self::assertSame(
            <<<'NEON'
                foo:
                    foo:
                        bar:
                            foo: foo
                            bar: bar
                    bar: bar
                baz: baz
                NEON,
            NeonUpdater::update($neon, ['foo', 'foo', 'bar'], ['foo' => 'foo', 'bar' => 'bar']),
        );
    }

    public function testReplacingFlatValueWithDeepValue(): void
    {
        $neon = 'foo: foo';
        self::assertSame(
            <<<'NEON'
                foo:
                    bar: value
                NEON,
            NeonUpdater::update($neon, ['foo', 'bar'], 'value'),
        );
        self::assertSame(
            <<<'NEON'
                foo:
                    bar: '''
                        foo
                        bar
                    '''
                NEON,
            NeonUpdater::update($neon, ['foo', 'bar'], "foo\nbar"),
        );
        self::assertSame(
            <<<'NEON'
                foo:
                    bar:
                        - value
                NEON,
            NeonUpdater::update($neon, ['foo', 'bar'], ['value']),
        );
        self::assertSame(
            <<<'NEON'
                foo:
                    bar:
                        - foo
                        - bar
                NEON,
            NeonUpdater::update($neon, ['foo', 'bar'], ['foo', 'bar']),
        );
        self::assertSame(
            <<<'NEON'
                foo:
                    bar:
                        foo: foo
                        bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo', 'bar'], ['foo' => 'foo', 'bar' => 'bar']),
        );
    }

    public function testReplacingImplicitNumericKey(): void
    {
        $neon = <<<'NEON'
            - foo
            - bar
            NEON;
        self::assertSame(
            <<<'NEON'
                - value
                - bar
                NEON,
            NeonUpdater::update($neon, ['0'], 'value'),
        );
        self::assertSame(
            <<<'NEON'
                - foo
                - value
                NEON,
            NeonUpdater::update($neon, ['1'], 'value'),
        );
    }

    public function testReplacingExplicitNumericKey(): void
    {
        $neon = <<<'NEON'
            0: foo
            1: bar
            NEON;
        self::assertSame(
            <<<'NEON'
                0: value
                1: bar
                NEON,
            NeonUpdater::update($neon, ['0'], 'value'),
        );
        self::assertSame(
            <<<'NEON'
                0: foo
                1: value
                NEON,
            NeonUpdater::update($neon, ['1'], 'value'),
        );
    }

    public function testAppendingToEmptyNeonWithComments(): void
    {
        self::assertSame(
            <<<'NEON'
                # lorem ipsum
                value
                NEON,
            NeonUpdater::update('# lorem ipsum', [], 'value'),
        );

        $neon = <<<'NEON'
            # lorem ipsum

            NEON;
        self::assertSame(
            <<<'NEON'
                # lorem ipsum
                value

                NEON,
            NeonUpdater::update($neon, [], 'value'),
        );

        $neon = <<<'NEON'
            # lorem ipsum
            # dolor sit amet
            NEON;
        self::assertSame(
            <<<'NEON'
                # lorem ipsum
                # dolor sit amet
                value
                NEON,
            NeonUpdater::update($neon, [], 'value'),
        );
    }

    public function testReplacingFirstLevelScalarPreservingComments(): void
    {
        $neon = <<<'NEON'
            # lorem ipsum
            foo: foo # foo
            # bar
            bar: bar
            NEON;
        self::assertSame(
            <<<'NEON'
                # lorem ipsum
                foo: value # foo
                # bar
                bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo'], 'value'),
        );

        $neon = <<<'NEON'
            # lorem ipsum
            foo: # dolor sit amet
                # consectetur adipisici elit
                foo # foo
            # bar
            bar: bar
            NEON;
        self::assertSame(
            <<<'NEON'
                # lorem ipsum
                foo: # dolor sit amet
                    # consectetur adipisici elit
                    value # foo
                # bar
                bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo'], 'value'),
        );

        $neon = <<<'NEON'
            # lorem ipsum
            foo: '''
                foo
                bar
            ''' # foo
            # bar
            bar: bar
            NEON;
        self::assertSame(
            <<<'NEON'
                # lorem ipsum
                foo: value # foo
                # bar
                bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo'], 'value'),
        );
    }

    public function testReplacingSecondLevelScalarPreservingComments(): void
    {
        $neon = <<<'NEON'
            foo:
                # lorem ipsum
                foo: foo # foo
                # bar
                bar: bar
            # baz
            baz: baz
            NEON;
        self::assertSame(
            <<<'NEON'
                foo:
                    # lorem ipsum
                    foo: value # foo
                    # bar
                    bar: bar
                # baz
                baz: baz
                NEON,
            NeonUpdater::update($neon, ['foo', 'foo'], 'value'),
        );

        $neon = <<<'NEON'
            foo:
                # lorem ipsum
                foo: # dolor sit amet
                    # consectetur adipisici elit
                    foo # foo
                # bar
                bar: bar
            # baz
            baz: baz
            NEON;
        self::assertSame(
            <<<'NEON'
                foo:
                    # lorem ipsum
                    foo: # dolor sit amet
                        # consectetur adipisici elit
                        value # foo
                    # bar
                    bar: bar
                # baz
                baz: baz
                NEON,
            NeonUpdater::update($neon, ['foo', 'foo'], 'value'),
        );

        $neon = <<<'NEON'
            foo:
                # lorem ipsum
                foo: '''
                    foo
                    bar
                ''' # foo
                # bar
                bar: bar
            # baz
            baz: baz
            NEON;
        self::assertSame(
            <<<'NEON'
                foo:
                    # lorem ipsum
                    foo: value # foo
                    # bar
                    bar: bar
                # baz
                baz: baz
                NEON,
            NeonUpdater::update($neon, ['foo', 'foo'], 'value'),
        );
    }

    public function testReplacingFirstLevelScalarOverwritingComments(): void
    {
        $neon = <<<'NEON'
            # lorem ipsum
            foo: # dolor sit amet
                # consectetur adipisici elit
                foo # foo
            # bar
            bar: bar
            NEON;
        self::assertSame(
            <<<'NEON'
                # lorem ipsum
                foo:
                    - value
                # bar
                bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo'], ['value']),
        );
        self::assertSame(
            <<<'NEON'
                # lorem ipsum
                foo:
                    - foo
                    - bar
                # bar
                bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo'], ['foo', 'bar']),
        );

        $neon = <<<'NEON'
            # lorem ipsum
            foo: '''
                foo
                bar
            ''' # foo
            # bar
            bar: bar
            NEON;
        self::assertSame(
            <<<'NEON'
                # lorem ipsum
                foo:
                    - value
                # bar
                bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo'], ['value']),
        );
    }

    public function testReplacingSecondLevelScalarOverwritingComments(): void
    {
        $neon = <<<'NEON'
            foo:
                # lorem ipsum
                foo: foo # foo
                # bar
                bar: bar
            # baz
            baz: baz
            NEON;
        self::assertSame(
            <<<'NEON'
                foo:
                    # lorem ipsum
                    foo:
                        - value
                    # bar
                    bar: bar
                # baz
                baz: baz
                NEON,
            NeonUpdater::update($neon, ['foo', 'foo'], ['value']),
        );

        $neon = <<<'NEON'
            foo:
                # lorem ipsum
                foo: # dolor sit amet
                    # consectetur adipisici elit
                    foo # foo
                # bar
                bar: bar
            # baz
            baz: baz
            NEON;
        self::assertSame(
            <<<'NEON'
                foo:
                    # lorem ipsum
                    foo:
                        - foo
                        - bar
                    # bar
                    bar: bar
                # baz
                baz: baz
                NEON,
            NeonUpdater::update($neon, ['foo', 'foo'], ['foo', 'bar']),
        );

        $neon = <<<'NEON'
            foo:
                # lorem ipsum
                foo: '''
                    foo
                    bar
                ''' # foo
                # bar
                bar: bar
            # baz
            baz: baz
            NEON;
        self::assertSame(
            <<<'NEON'
                foo:
                    # lorem ipsum
                    foo:
                        - value
                    # bar
                    bar: bar
                # baz
                baz: baz
                NEON,
            NeonUpdater::update($neon, ['foo', 'foo'], ['value']),
        );
    }

    public function testReplacingFirstLevelArrayOverwritingComments(): void
    {
        $neon = <<<'NEON'
            # lorem ipsum
            foo: # dolor sit amet
                - foo # foo
                - bar # bar
            # baz
            baz: baz
            NEON;
        self::assertSame(
            <<<'NEON'
                # lorem ipsum
                foo: value
                # baz
                baz: baz
                NEON,
            NeonUpdater::update($neon, ['foo'], 'value'),
        );
    }

    public function testReplacingSecondLevelArrayOverwritingComments(): void
    {
        $neon = <<<'NEON'
            foo:
                # lorem ipsum
                foo: # dolor sit amet
                    - foo # foo
            # bar
            bar: bar
            NEON;
        self::assertSame(
            <<<'NEON'
                foo:
                    # lorem ipsum
                    foo: value
                # bar
                bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo', 'foo'], 'value'),
        );
    }

    public function testReplacingImplicitNull(): void
    {
        self::assertSame('foo: null', NeonUpdater::update('foo:', ['foo'], null));

        $neon = <<<'NEON'
            foo:
            bar: bar
            NEON;
        self::assertSame(
            <<<'NEON'
                foo: value
                bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo'], 'value'),
        );

        $neon = <<<'NEON'
            foo:
                foo:
                bar: bar
            NEON;
        self::assertSame(
            <<<'NEON'
                foo:
                    foo: value
                    bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo', 'foo'], 'value'),
        );

        $neon = <<<'NEON'
            # lorem ipsum
            foo: # foo
            # bar
            bar: bar
            NEON;
        self::assertSame(
            <<<'NEON'
                # lorem ipsum
                foo: value # foo
                # bar
                bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo'], 'value'),
        );

        $neon = <<<'NEON'
            foo:
                # lorem ipsum
                foo: # foo
                # bar
                bar: bar
            NEON;
        self::assertSame(
            <<<'NEON'
                foo:
                    # lorem ipsum
                    foo: value # foo
                    # bar
                    bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo', 'foo'], 'value'),
        );

        $neon = <<<'NEON'
            foo:
                # lorem ipsum
                foo:
                    # foo
                # bar
                bar: bar
            NEON;
        self::assertSame(
            <<<'NEON'
                foo:
                    # lorem ipsum
                    foo: value
                        # foo
                    # bar
                    bar: bar
                NEON,
            NeonUpdater::update($neon, ['foo', 'foo'], 'value'),
        );
    }

    public function testAppendingToArray(): void
    {
        self::assertSame(
            <<<'NEON'
            - foo
            - bar
            NEON,
            NeonUpdater::update("- foo", [null], 'bar'),
        );
        self::assertSame(
            <<<'NEON'
            foo: foo
            - bar
            NEON,
            NeonUpdater::update("foo: foo", [null], 'bar'),
        );

        $neon = <<<'NEON'
            foo: foo # foo
            bar: bar # bar
            # lorem ipsum
            NEON;
        self::assertSame(
            <<<'NEON'
            foo: foo # foo
            bar: bar # bar
            -
                baz: baz
            # lorem ipsum
            NEON,
            NeonUpdater::update($neon, [null, 'baz'], 'baz'),
        );

        $neon = <<<'NEON'
            foo:
                foo: foo
                bar: bar
            NEON;
        self::assertSame(
            <<<'NEON'
                foo:
                    foo: foo
                    bar: bar
                    - baz
                NEON,
            NeonUpdater::update($neon, ['foo', null], 'baz'),
        );
    }

    public function testReplacingInlineArray(): void
    {
        self::assertSame('foo: value', NeonUpdater::update('foo: []', ['foo'], 'value'));

        $neon = <<<'NEON'
            foo: [
                foo,
                bar
            ]
            NEON;
        self::assertSame('foo: value', NeonUpdater::update($neon, ['foo'], 'value'));

        $neon = <<<'NEON'
            foo: {
                foo: [foo, bar],
                bar: bar,
            }
            NEON;
        self::assertSame('foo: value', NeonUpdater::update($neon, ['foo'], 'value'));
    }

    public function testUpdatingInlineArraysIsNotSupported(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Updating inline arrays is not supported.');
        NeonUpdater::update('foo: [foo, bar]', ['foo', '0'], 'value');
    }

    public function testDetectedIndentation(): void
    {
        $neon = <<<'NEON'
            foo:
              foo: foo
              bar: bar
            NEON;
        self::assertSame(
            <<<'NEON'
            foo:
              foo: foo
              bar: bar
            bar:
              foo: foo
              bar: '''
                foo
                bar
              '''
            NEON,
            NeonUpdater::update($neon, ['bar'], ['foo' => 'foo', 'bar' => "foo\nbar"]),
        );

        $neon = <<<NEON
            foo:
            \tfoo: foo
            NEON;
        self::assertSame(
            <<<NEON
            foo:
            \tfoo: foo
            bar:
            \tbar: bar
            NEON,
            NeonUpdater::update($neon, ['bar'], ['bar' => 'bar']),
        );

        $neon = <<<'NEON'
            foo: '''
              foo
              bar
            '''
            NEON;
        self::assertSame(
            <<<'NEON'
            foo:
              foo: foo
              bar: bar
            NEON,
            NeonUpdater::update($neon, ['foo'], ['foo' => 'foo', 'bar' => 'bar']),
        );

        $neon = <<<'NEON'
            foo:
              foo
            NEON;
        self::assertSame(
            <<<'NEON'
            foo:
              foo: foo
              bar: bar
            NEON,
            NeonUpdater::update($neon, ['foo'], ['foo' => 'foo', 'bar' => 'bar']),
        );
    }

    public function testIndentedRoot(): void
    {
        self::assertSame('  bar', NeonUpdater::update('  foo: foo', [], 'bar'));
        self::assertSame(
            <<<'NEON'
              foo:
                bar: bar
            NEON,
            NeonUpdater::update('  foo: foo', ['foo', 'bar'], 'bar'),
        );
        self::assertSame(
            <<<'NEON'
              foo: foo
              - bar
            NEON,
            NeonUpdater::update('  foo: foo', [null], 'bar'),
        );
    }

    public function testTrailingNewline(): void
    {
        self::assertSame("value\n", NeonUpdater::update("\n", [], 'value'));
        self::assertSame("value\n\n", NeonUpdater::update("\n\n", [], 'value'));
        self::assertSame("value\n", NeonUpdater::update("- foo\n", [], 'value'));
        self::assertSame("# lorem ipsum\nvalue", NeonUpdater::update("# lorem ipsum", [], 'value'));
    }
}
