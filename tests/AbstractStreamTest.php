<?php

declare(strict_types=1);

/*
 * This file is part of Idoheo Stream package.
 * (c) 2017 Repository contributors
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
     * @covers ::getType
     * @depends testGetMetadataKey
     */
    public function testGetType()
    {
        $stream_type = 'stream_type-'.\time();

        $this->abstractStream
            ->expects(static::at(0))
            ->method('getMetadata')
            ->willReturn(['stream_type' => $stream_type]);

        static::assertSame(
            $stream_type,
            $this->abstractStream->getType(),
            \sprintf(
                '%s::%s() failed to return value provided in metadata key %s.',
                $this->abstractStreamClass,
                'getType',
                'uri'
            )
        );

        foreach ([[]] as $metaData) {
            $this->abstractStream
                ->expects(static::at(0))
                ->method('getMetadata')
                ->willReturn($metaData);

            static::assertNull(
                $this->abstractStream->getType(),
                \sprintf(
                    '%s::%s() failed to return NULL when value is not provided in metadata.',
                    $this->abstractStreamClass,
                    'getType'
                )
            );
        }
    }

    /**
     * @covers ::isBlocking
     * @depends testGetMetadataKey
     */
    public function testIsBlocking__success()
    {
        foreach ([true, false] as $blocking) {
            $metaData = [
                'blocked' => $blocking,
            ];

            $this->abstractStream
                ->expects(static::at(0))
                ->method('getMetadata')
                ->willReturn($metaData);

            static::assertSame(
                $blocking,
                $this->abstractStream->isBlocking(),
                \sprintf(
                    '%s::%s() failed returning expected result.',
                    $this->abstractStreamClass,
                    'isBlocked'
                )
            );
        }
    }

    /**
     * @covers ::isRemote
     */
    public function testIsRemote__success()
    {
        foreach ([true, false] as $open) {
            foreach ([true, false] as $local) {
                $this->abstractStream = $this
                    ->getMockBuilder($this->abstractStreamClass)
                    ->disableOriginalConstructor()
                    ->getMockForAbstractClass();

                $remote = $open && !$local;

                $this->abstractStream->expects(static::any())->method('isOpen')->wilLReturn($open);
                $this->abstractStream->expects(static::any())->method('isLocal')->wilLReturn($local);

                static::assertSame(
                    $remote,
                    $this->abstractStream->isRemote(),
                    \sprintf(
                        '%s::%s() expected to return %s for %s %s stream.',
                        $this->abstractStreamClass,
                        'isRemote',
                        \var_export($remote, true),
                        $open ? 'open' : 'closed',
                        $local ? 'local' : 'non-local'
                    )
                );
            }
        }
    }

    /**
     * @covers ::lockShared
     */
    public function testLockShared()
    {
        foreach ([true, false] as $nonBlocking) {
            foreach ([true, false] as $wouldBlock) {
                $wbResult = null;

                $this->abstractStream
                    ->expects(static::at(0))
                    ->method('lock')
                    ->with(LOCK_SH | ($nonBlocking ? LOCK_NB : 0), $wbResult)
                    ->willReturnCallback(
                        function ($lock, &$wb) use ($wouldBlock) {
                            $wb = $wouldBlock;
                        }
                    );

                $this->abstractStream->lockShared($nonBlocking, $wbResult);

                static::assertSame(
                    $wouldBlock,
                    $wbResult,
                    \sprintf(
                        '%s::%s() failed to update $wouldBlock passed as second argument.',
                        AbstractStream::class,
                        'lockShared'
                    )
                );
            }
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
            foreach ([true, false] as $wouldBlock) {
                $wbResult = null;

                $this->abstractStream
                    ->expects(static::at(0))
                    ->method('lock')
                    ->with(LOCK_EX | ($nonBlocking ? LOCK_NB : 0), $wbResult)
                    ->willReturnCallback(
                        function ($lock, &$wb) use ($wouldBlock) {
                            $wb = $wouldBlock;
                        }
                    );

                $this->abstractStream->lockExclusive($nonBlocking, $wbResult);

                static::assertSame(
                    $wouldBlock,
                    $wbResult,
                    \sprintf(
                        '%s::%s() failed to update $wouldBlock passed as second argument.',
                        AbstractStream::class,
                        'lockShared'
                    )
                );
            }
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
            foreach ([true, false] as $wouldBlock) {
                $wbResult = null;

                $this->abstractStream
                    ->expects(static::at(0))
                    ->method('lock')
                    ->with(LOCK_UN | ($nonBlocking ? LOCK_NB : 0), $wbResult)
                    ->willReturnCallback(
                        function ($lock, &$wb) use ($wouldBlock) {
                            $wb = $wouldBlock;
                        }
                    );

                $this->abstractStream->unlock($nonBlocking, $wbResult);

                static::assertSame(
                    $wouldBlock,
                    $wbResult,
                    \sprintf(
                        '%s::%s() failed to update $wouldBlock passed as second argument.',
                        AbstractStream::class,
                        'lockShared'
                    )
                );
            }
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
     * @covers ::writeLine
     */
    public function testWriteLine()
    {
        foreach (["\n", "\r\n", PHP_EOL] as $newLine) {
            $string = 'some - string -'.\implode('', \array_fill(\random_int(10, 20), \random_int(25, 35), 'x'));
            $len    = \mb_strlen($string.$newLine);

            $this->abstractStream
                ->expects(static::at(0))
                ->method('write')
                ->with(\sprintf('%s%s', $string, $newLine))
                ->willReturn($len);

            static::assertSame(
                $len,
                $this->abstractStream->writeLine($string, $newLine),
                \sprintf(
                    '%s::%s() should have returned number of characters written.',
                    $this->abstractStreamClass,
                    'writeLine'
                )
            );
        }

        $string = 'some - string -'.\implode('', \array_fill(\random_int(10, 20), \random_int(25, 35), 'x'));
        $len    = \mb_strlen($string.PHP_EOL);

        $this->abstractStream
            ->expects(static::at(0))
            ->method('write')
            ->with(\sprintf('%s%s', $string, PHP_EOL))
            ->willReturn($len);

        static::assertSame(
            $len,
            $this->abstractStream->writeLine($string),
            \sprintf(
                '%s::%s() should have returned number of characters written.',
                $this->abstractStreamClass,
                'writeLine'
            )
        );
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
