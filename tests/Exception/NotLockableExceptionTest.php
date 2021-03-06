<?php

declare(strict_types=1);

/*
 * This file is part of Idoheo Stream package.
 * (c) 2017 Repository contributors
 *
 * This source file is subject to the MIT license.
 * Copy of license is located with this source code in the file LICENSE.
 */

namespace Idoheo\Tests\Stream\Exception;

use Idoheo\Stream\Exception\NotLockableException;
use Idoheo\Stream\StreamInterface;
use Idoheo\Tests\Stream\TestCase;

/**
 * @coversDefaultClass \Idoheo\Stream\Exception\NotLockableException
 */
class NotLockableExceptionTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstructWithClosedStream()
    {
        $streamMock = $this->getMockForAbstractClass(StreamInterface::class);
        $streamMock->expects(static::atLeastOnce())->method('isOpen')->willReturn(false);

        $this->expectException(NotLockableException::class);
        $this->expectExceptionMessage('Closed stream is not lockable.');

        throw new NotLockableException($streamMock);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructWithStreamNotContainingUri()
    {
        $type = 'some-stream-type-'.\microtime(true);
        $mode = 'some-mode-'.\microtime(true);

        $streamMock = $this->getMockForAbstractClass(StreamInterface::class);
        $streamMock->expects(static::atLeastOnce())->method('isOpen')->willReturn(true);
        $streamMock->expects(static::atLeastOnce())->method('getType')->willReturn($type);
        $streamMock->expects(static::atLeastOnce())->method('getMode')->willReturn($mode);
        $streamMock->expects(static::atLeastOnce())->method('getUri')->willReturn(null);

        $this->expectException(NotLockableException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Stream is not lockable (type: %s; mode: %s)',
                $type,
                $mode
            )
        );

        throw new NotLockableException($streamMock);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructWithStreamContainingUri()
    {
        $type = 'some-stream-type-'.\microtime(true);
        $mode = 'some-mode-'.\microtime(true);
        $uri  = 'some-uri-'.\microtime(true);

        $streamMock = $this->getMockForAbstractClass(StreamInterface::class);
        $streamMock->expects(static::atLeastOnce())->method('isOpen')->willReturn(true);
        $streamMock->expects(static::atLeastOnce())->method('getType')->willReturn($type);
        $streamMock->expects(static::atLeastOnce())->method('getMode')->willReturn($mode);
        $streamMock->expects(static::atLeastOnce())->method('getUri')->willReturn($uri);

        $this->expectException(NotLockableException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Stream "%s" is not lockable (type: %s; mode: %s)',
                $uri,
                $type,
                $mode
            )
        );

        throw new NotLockableException($streamMock);
    }
}
