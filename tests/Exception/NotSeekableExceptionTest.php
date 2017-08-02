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

use Idoheo\Stream\Exception\NotSeekableException;
use Idoheo\Stream\StreamInterface;
use Idoheo\Tests\Stream\TestCase;

/**
 * @coversDefaultClass \Idoheo\Stream\Exception\NotSeekableException
 */
class NotSeekableExceptionTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstructWithClosedStream()
    {
        $streamMock = $this->getMockForAbstractClass(StreamInterface::class);
        $streamMock->expects(static::atLeastOnce())->method('isOpen')->willReturn(true);

        $this->expectException(NotSeekableException::class);
        $this->expectExceptionMessage('Closed stream is not seekable.');

        throw new NotSeekableException($streamMock);
    }

    /**
     * @covers ::__construct
     */
    public function testConstructWithStreamNotContainingUri()
    {
        $type = 'some-stream-type-'.\microtime(true);
        $mode = 'some-mode-'.\microtime(true);

        $streamMock = $this->getMockForAbstractClass(StreamInterface::class);
        $streamMock->expects(static::atLeastOnce())->method('isOpen')->willReturn(false);
        $streamMock->expects(static::atLeastOnce())->method('getType')->willReturn($type);
        $streamMock->expects(static::atLeastOnce())->method('getMode')->willReturn($mode);
        $streamMock->expects(static::atLeastOnce())->method('getUri')->willReturn(null);

        $this->expectException(NotSeekableException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Stream of "%s" type with mode "%s" is not seekable.',
                $type,
                $mode
            )
        );

        throw new NotSeekableException($streamMock);
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
        $streamMock->expects(static::atLeastOnce())->method('isOpen')->willReturn(false);
        $streamMock->expects(static::atLeastOnce())->method('getType')->willReturn($type);
        $streamMock->expects(static::atLeastOnce())->method('getMode')->willReturn($mode);
        $streamMock->expects(static::atLeastOnce())->method('getUri')->willReturn($uri);

        $this->expectException(NotSeekableException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Stream of "%s" type with mode "%s" is not seekable (URI: %s).',
                $type,
                $mode,
                $uri
            )
        );

        throw new NotSeekableException($streamMock);
    }
}