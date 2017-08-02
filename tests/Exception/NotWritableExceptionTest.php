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

use Idoheo\Stream\Exception\NotWritableException;
use Idoheo\Stream\StreamInterface;
use Idoheo\Tests\Stream\TestCase;

/**
 * @coversDefaultClass \Idoheo\Stream\Exception\NotWritableException
 */
class NotWritableExceptionTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testConstructWithClosedStream()
    {
        $streamMock = $this->getMockForAbstractClass(StreamInterface::class);
        $streamMock->expects(static::atLeastOnce())->method('isOpen')->willReturn(true);

        $this->expectException(NotWritableException::class);
        $this->expectExceptionMessage('Closed stream is not writable.');

        throw new NotWritableException($streamMock);
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

        $this->expectException(NotWritableException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Stream of "%s" type with mode "%s" is not writable.',
                $type,
                $mode
            )
        );

        throw new NotWritableException($streamMock);
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

        $this->expectException(NotWritableException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Stream of "%s" type with mode "%s" is not writable (URI: %s).',
                $type,
                $mode,
                $uri
            )
        );

        throw new NotWritableException($streamMock);
    }
}
