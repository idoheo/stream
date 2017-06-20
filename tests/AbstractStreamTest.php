<?php

declare(strict_types=1);

/*
 * This file is part of Idoheo Uri Query package.
 * (c) Repository contributors
 *
 * This source file is subject to the MIT license.
 * Copy of license is located with this source code in the file LICENSE.
 */

namespace Idoheo\Tests\Stream;

use Idoheo\Stream\AbstractStream;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * @coversDefaultClass \Idoheo\Stream\AbstractStream
 */
class AbstractStreamTest extends TestCase
{
    /**
     * @var string
     */
    private $abstractStreamClass = AbstractStream::class;

    /**
     * @var AbstractStream|MockObject
     */
    private $abstractStream;

    protected function setUp()
    {
        parent::setUp();
        $this->abstractStream = $this->getMockBuilder($this->abstractStreamClass)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    protected function tearDown()
    {
        $this->abstractStream = null;
        parent::tearDown();
    }

    /**
     * @covers ::getMetadataKey
     */
    public function testGetMetadataKey()
    {
        $parameterName = 'parameter-name-'.\time();

        // Try asking with wrong key from metadata
        $this->abstractStream->expects(static::at(0))->method('getMetadata')->willReturn([]);
        static::assertNull(
            $this->abstractStream->getMetadataKey($parameterName),
            \sprintf(
                '%s::%s() should be returning NULL if ::%s is returning array without requested key.',
                $this->abstractStreamClass,
                'getMetadataKey',
                'getMetadata'
            )
        );

        // Try asking with correct key from metadata
        $parameterValue = 'parameter-value-'.\time();
        $this->abstractStream->expects(static::at(0))->method('getMetadata')->willReturn([$parameterName => $parameterValue]);
        static::assertSame(
            $parameterValue,
            $this->abstractStream->getMetadataKey($parameterName),
            \sprintf(
                '%s::%s() should be returning parameter value if ::%s is returning array with requested key.',
                $this->abstractStreamClass,
                'getMetadataKey',
                'getMetadata'
            )
        );
    }

    /**
     * @covers ::getStatKey
     * @depends testGetMetadataKey
     */
    public function testGetStatKey()
    {
        $parameterName = 'parameter-name-'.\time();

        // Try asking with wrong key from stat
        $this->abstractStream->expects(static::at(0))->method('getStat')->willReturn([]);
        static::assertNull(
            $this->abstractStream->getStatKey($parameterName),
            \sprintf(
                '%s::%s() should be returning NULL if ::%s is returning array without requested key.',
                $this->abstractStreamClass,
                'getStatKey',
                'getStat'
            )
        );

        // Try asking with correct key from stat
        $parameterValue = 'parameter-value-'.\time();
        $this->abstractStream->expects(static::at(0))->method('getStat')->willReturn([$parameterName => $parameterValue]);
        static::assertSame(
            $parameterValue,
            $this->abstractStream->getStatKey($parameterName),
            \sprintf(
                '%s::%s() should be returning parameter value if ::%s is returning array with requested key.',
                $this->abstractStreamClass,
                'getStatKey',
                'getStat'
            )
        );
    }

    /**
     * @covers ::getMode
     * @depends testGetMetadataKey
     */
    public function testGetMode()
    {
        $mode = 'mode-'.\time();

        $this->abstractStream
            ->expects(static::at(0))
            ->method('getMetadata')
            ->willReturn(['mode' => $mode]);

        static::assertSame(
            $mode,
            $this->abstractStream->getMode(),
            \sprintf(
                '%s::%s() failed to return value provided in metadata key %s.',
                $this->abstractStreamClass,
                'getMode',
                'mode'
            )
        );

        foreach ([[]] as $metaData) {
            $this->abstractStream
                ->expects(static::at(0))
                ->method('getMetadata')
                ->willReturn($metaData);

            static::assertNull(
                $this->abstractStream->getMode(),
                \sprintf(
                    '%s::%s() failed to return NULL when value is not provided in metadata.',
                    $this->abstractStreamClass,
                    'getMode'
                )
            );
        }
    }

    /**
     * @covers ::getUri
     * @depends testGetMetadataKey
     */
    public function testGetUri()
    {
        $uri = 'uri-'.\time();

        $this->abstractStream
            ->expects(static::at(0))
            ->method('getMetadata')
            ->willReturn(['uri' => $uri]);

        static::assertSame(
            $uri,
            $this->abstractStream->getUri(),
            \sprintf(
                '%s::%s() failed to return value provided in metadata key %s.',
                $this->abstractStreamClass,
                'getUri',
                'uri'
            )
        );

        foreach ([[]] as $metaData) {
            $this->abstractStream
                ->expects(static::at(0))
                ->method('getMetadata')
                ->willReturn($metaData);

            static::assertNull(
                $this->abstractStream->getUri(),
                \sprintf(
                    '%s::%s() failed to return NULL when value is not provided in metadata.',
                    $this->abstractStreamClass,
                    'getUri'
                )
            );
        }
    }

    /**
     * @covers ::lockShared
     */
    public function testLockShared()
    {
        foreach ([true, false] as $nonBlocking) {
            $this->abstractStream
                ->expects(static::at(0))
                ->method('lock')
                ->with(LOCK_SH | ($nonBlocking ? LOCK_NB : 0));

            $this->abstractStream->lockShared($nonBlocking);
        }

        $this->abstractStream
            ->expects(static::at(0))
            ->method('lock')
            ->with(LOCK_SH);

        $this->abstractStream->lockShared();
    }

    /**
     * @covers ::lockExclusive
     */
    public function testLockExclusive()
    {
        foreach ([true, false] as $nonBlocking) {
            $this->abstractStream
                ->expects(static::at(0))
                ->method('lock')
                ->with(LOCK_EX | ($nonBlocking ? LOCK_NB : 0));

            $this->abstractStream->lockExclusive($nonBlocking);
        }

        $this->abstractStream
            ->expects(static::at(0))
            ->method('lock')
            ->with(LOCK_EX);

        $this->abstractStream->lockExclusive();
    }

    /**
     * @covers ::unlock
     */
    public function testUnlock()
    {
        foreach ([true, false] as $nonBlocking) {
            $this->abstractStream
                ->expects(static::at(0))
                ->method('lock')
                ->with(LOCK_UN | ($nonBlocking ? LOCK_NB : 0));

            $this->abstractStream->unlock($nonBlocking);
        }

        $this->abstractStream
            ->expects(static::at(0))
            ->method('lock')
            ->with(LOCK_UN);

        $this->abstractStream->unlock();
    }

    /**
     * @covers ::isSeekable
     * @depends testGetMetadataKey
     */
    public function testIsSeekable()
    {
        foreach ([true, false] as $isSeekable) {
            $this->abstractStream
                ->expects(static::at(0))
                ->method('getMetadata')
                ->willReturn(['seekable' => $isSeekable]);

            static::assertSame(
                $isSeekable,
                $this->abstractStream->isSeekable(),
                \sprintf(
                    '%s::%s() failed to return value provided in metadata key %s.',
                    $this->abstractStreamClass,
                    'isSeekable',
                    'seekable'
                )
            );
        }

        foreach ([[]] as $metaData) {
            $this->abstractStream
                ->expects(static::at(0))
                ->method('getMetadata')
                ->willReturn($metaData);

            static::assertFalse(
                $this->abstractStream->isSeekable(),
                \sprintf(
                    '%s::%s() failed to return false when value is not provided in metadata.',
                    $this->abstractStreamClass,
                    'isSeekable'
                )
            );
        }
    }

    /**
     * @covers ::rewind
     */
    public function testRewind()
    {
        $this->abstractStream
            ->expects(static::once())
            ->method('seek')
            ->with(0, SEEK_SET)
            ->willReturn($this->abstractStream);

        $this->abstractStream->rewind();
    }

    /**
     * @covers ::fastForward
     */
    public function testFastForward()
    {
        $this->abstractStream
            ->expects(static::once())
            ->method('seek')
            ->with(0, SEEK_END)
            ->willReturn($this->abstractStream);

        $this->abstractStream->fastForward();
    }

    /**
     * @covers ::isReadable
     * @depends testGetMode
     */
    public function testIsReadable()
    {
        $tests = [];
        foreach (['r'] as $readable) {
            $tests[$readable] = true;
        }
        foreach (['w', 'a', 'x', 'c'] as $writable) {
            $tests[$writable] = false;
        }
        foreach ($tests as $key => $val) {
            $tests[$key.'+'] = true;
        }

        foreach ($tests as $mode => $readable) {
            $this->abstractStream
                ->expects(static::at(0))
                ->method('getMetadata')
                ->willReturn(['mode' => $mode]);

            static::assertSame(
                $readable,
                $this->abstractStream->isReadable(),
                \sprintf(
                    '%s::%s() should have returned %s for mode %s.',
                    $this->abstractStreamClass,
                    'isReadable',
                    \var_export($readable, true),
                    \var_export($mode, true)
                )
            );
        }

        foreach ([[]] as $metadata) {
            $this->abstractStream
                ->expects(static::at(0))
                ->method('getMetadata')
                ->willReturn($metadata);

            static::assertFalse(
                $this->abstractStream->isReadable(),
                \sprintf(
                    '%s::%s() should have returned false when no mode provided in metadata.',
                    $this->abstractStreamClass,
                    'isReadable'
                )
            );
        }
    }

    /**
     * @covers ::isWritable
     * @depends testGetMode
     */
    public function testIsWritable()
    {
        $tests = [];
        foreach (['r'] as $writable) {
            $tests[$writable] = false;
        }
        foreach (['w', 'a', 'x', 'c'] as $writable) {
            $tests[$writable] = true;
        }
        foreach ($tests as $key => $val) {
            $tests[$key.'+'] = true;
        }

        foreach ($tests as $mode => $writable) {
            $this->abstractStream
                ->expects(static::at(0))
                ->method('getMetadata')
                ->willReturn(['mode' => $mode]);

            static::assertSame(
                $writable,
                $this->abstractStream->isWritable(),
                \sprintf(
                    '%s::%s() should have returned %s for mode %s.',
                    $this->abstractStreamClass,
                    'isWritable',
                    \var_export($writable, true),
                    \var_export($mode, true)
                )
            );
        }

        foreach ([[]] as $metadata) {
            $this->abstractStream
                ->expects(static::at(0))
                ->method('getMetadata')
                ->willReturn($metadata);

            static::assertFalse(
                $this->abstractStream->isWritable(),
                \sprintf(
                    '%s::%s() should have returned false when no mode provided in metadata.',
                    $this->abstractStreamClass,
                    'isWritable'
                )
            );
        }
    }

    /**
     * @covers ::getSize
     *
     * @depends testGetMetadataKey
     * @depends testGetStatKey
     */
    public function testGetSize()
    {
        $size = \random_int(10000, 20000);

        $this->abstractStream
            ->expects(static::at(0))
            ->method('getStat')
            ->willReturn(['size' => $size]);

        static::assertSame(
            $size,
            $this->abstractStream->getSize(),
            \sprintf(
                '%s::%s() failed to return value provided in stat key %s.',
                $this->abstractStreamClass,
                'getSize',
                'stream_type'
            )
        );

        foreach ([[]] as $stat) {
            $this->abstractStream
                ->expects(static::at(0))
                ->method('getStat')
                ->willReturn($stat);

            static::assertNull(
                $this->abstractStream->getSize(),
                \sprintf(
                    '%s::%s() failed to return expected value when value is not provided in stat.',
                    $this->abstractStreamClass,
                    'getSize'
                )
            );
        }
    }

    /**
     * @covers ::__toString
     */
    public function testToString()
    {
        $position = \random_int(10, 20);
        $string   = \implode(\array_fill(0, $position, 'x')).\microtime();

        $this->abstractStream->expects(static::at(0))->method('tell')->willReturn($position);
        $this->abstractStream->expects(static::at(1))->method('seek')->with(0, SEEK_SET)->willReturn($this->abstractStream);
        $this->abstractStream->expects(static::at(2))->method('getContents')->willReturn($string);
        $this->abstractStream->expects(static::at(3))->method('seek')->with($position, SEEK_SET)->willReturn($this->abstractStream);

        static::assertSame(
            $string,
            (string) $this->abstractStream,
            \sprintf(
                '%s::%s() failed to return expected value.',
                $this->abstractStreamClass,
                '__toString'
            )
        );

        $this->abstractStream->expects(static::at(0))->method('tell')->willReturn($position);
        $this->abstractStream->expects(static::at(1))->method('seek')->with(0, SEEK_SET)->willThrowException(new \Exception());
        static::assertSame(
            '',
            (string) $this->abstractStream,
            \sprintf(
                '%s::%s() failed to return expected value when failed to get string.',
                $this->abstractStreamClass,
                '__toString'
            )
        );
    }
}
