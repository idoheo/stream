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

use Idoheo\Stream\Exception\DomainException;
use Idoheo\Stream\Exception\InvalidArgumentException;
use Idoheo\Stream\Exception\LengthException;
use Idoheo\Stream\Exception\LogicException;
use Idoheo\Stream\Exception\NotLockableException;
use Idoheo\Stream\Exception\NotReadableException;
use Idoheo\Stream\Exception\NotSeekableException;
use Idoheo\Stream\Exception\NotWritableException;
use Idoheo\Stream\Exception\RuntimeException;
use Idoheo\Stream\Stream;
use Idoheo\Stream\StreamInterface;
use Symfony\Component\Process\Process;

/**
 * @coversDefaultClass \Idoheo\Stream\Stream
 */
class StreamTest extends TestCase
{
    /**
     * @var Stream
     */
    private $stream;

    /**
     * @var resource
     */
    private $resource;

    private $tearDownCallback = null;

    protected function setUp()
    {
        parent::setUp();
        $this->resource         = \tmpfile();
        $this->stream           = new Stream($this->resource);
        $this->tearDownCallback =null;
    }

    protected function tearDown()
    {
        $this->stream = null;
        if (\is_resource($this->resource) && \get_resource_type($this->resource) === 'stream') {
            \fclose($this->resource);
        }
        $this->resource = null;
        if (\is_callable($this->tearDownCallback)) {
            \call_user_func_array($this->tearDownCallback, []);
        }
        $this->tearDownCallback = null;
        parent::tearDown();
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        $expected   = ['stream' => $this->resource];
        $actual     = [];
        $reflection = new \ReflectionClass($this->stream);
        foreach ($reflection->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }
            $property->setAccessible(true);
            $actual[$property->getName()] = $property->getValue($this->stream);
        }

        \ksort($expected);
        \ksort($actual);

        static::assertEquals(
            $expected,
            $actual,
            \sprintf(
                '%s::%s() failed to build object as expected.',
                \get_class($this->stream),
                '__construct'
            )
        );

        $this->expectException(InvalidArgumentException::class);
        $class = \get_class($this->stream);
        new $class('string');
    }

    /**
     * @covers ::isOpen
     * @depends testConstruct
     */
    public function testIsOpen()
    {
        for ($i = 1; $i <= 2; ++$i) {
            static::assertTrue(
                $this->stream->isOpen(),
                \sprintf(
                    '%s::%s() failed to return expected result for non closed / non detached stream.',
                    \get_class($this->stream),
                    'isOpen'
                )
            );
        }

        \fclose($this->resource);

        for ($i = 1; $i <= 2; ++$i) {
            static::assertFalse(
                $this->stream->isOpen(),
                \sprintf(
                    '%s::%s() failed to return expected result for closed stream.',
                    \get_class($this->stream),
                    'isOpen'
                )
            );
        }
    }

    /**
     * @covers ::close
     * @depends testConstruct
     * @depends testIsOpen
     */
    public function testClose()
    {
        // Switch with new stream
        $meta        = \stream_get_meta_data($this->resource);
        $class       = \get_class($this->stream);
        $newResource = \fopen($meta['uri'], 'r+');
        $this->stream->detach();
        $this->stream = new $class($newResource);

        // Lock new resource in stream
        static::assertTrue(\flock($newResource, LOCK_EX | LOCK_NB, $wouldBlock));

        // Try see that it is locked
        static::assertFalse(\flock($this->resource, LOCK_EX | LOCK_NB, $wouldBlock));
        static::assertTrue((bool) $wouldBlock);

        $this->stream->close();

        // Try see that it is no longer
        static::assertTrue(\flock($this->resource, LOCK_EX | LOCK_NB, $wouldBlock));
        \flock($this->resource, LOCK_UN | LOCK_NB);

        for ($i = 1; $i <= 2; ++$i) {
            static::assertFalse(
                \is_resource($newResource) && \get_resource_type($newResource) === 'stream',
                \sprintf(
                    '%s::%s() failed to close stream.',
                    \get_class($this->stream),
                    'close'
                )
            );

            static::assertFalse(
                $this->stream->isOpen(),
                \sprintf(
                    '%s::%s() claims stream is open, after ::close() has been invoked.',
                    \get_class($this->stream),
                    'isOpen'
                )
            );
        }
    }

    /**
     * @covers ::__destruct
     * @depends testConstruct
     * @depends testClose
     */
    public function testDestruct()
    {
        $this->stream =  null;

        static::assertFalse(
            \is_resource($this->resource) && \get_resource_type($this->resource) === 'stream',
            \sprintf(
                '%s::%s() failed to close stream.',
                \get_class($this->stream),
                '__destruct'
            )
        );
    }

    /**
     * @covers ::getHandle
     * @depends testConstruct
     * @depends testIsOpen
     */
    public function testGetHandle()
    {
        for ($i = 1; $i <= 2; $i++) {
            static::assertSame(
                $this->resource,
                $this->stream->getHandle(),
                \sprintf(
                    '%s::%s() failed to return expected result.',
                    \get_class($this->stream),
                    'getHandle'
                )
            );
        }

        fclose($this->resource);

        static::assertNull(
            $this->stream->getHandle(),
            \sprintf(
                '%s::%s() failed to return expected result.',
                \get_class($this->stream),
                'getHandle'
            )
        );
    }

    /**
     * @covers ::detach
     * @depends testConstruct
     * @depends testIsOpen
     */
    public function testDetach()
    {
        static::assertSame(
            $this->stream->detach(),
            $this->resource,
            \sprintf(
                '%s::%s() failed to return underlying resource.',
                \get_class($this->stream),
                'detach'
            )
        );

        static::assertTrue(
            \is_resource($this->resource) && \get_resource_type($this->resource) === 'stream',
            \sprintf(
                '%s::%s() failed to keep stream open after detachment.',
                \get_class($this->stream),
                'detach'
            )
        );

        static::assertFalse(
            $this->stream->isOpen(),
            \sprintf(
                '%s::%s() claims stream is open, after ::detach() has been invoked.',
                \get_class($this->stream),
                'isOpen'
            )
        );

        static::assertNull(
            $this->stream->detach(),
            \sprintf(
                '%s::%s() failed to return NULL after resource has already been detached.',
                \get_class($this->stream),
                'detach'
            )
        );
    }

    /**
     * @covers ::getMetadata
     * @depends testConstruct
     * @depends testClose
     */
    public function testGetMetadata()
    {
        static::assertSame(
            \stream_get_meta_data($this->resource),
            $this->stream->getMetadata(),
            \sprintf(
                '%s::%s() failed getting metadata of open stream.',
                \get_class($this->stream),
                'getMetadata'
            )
        );

        $this->stream->close();

        static::assertSame(
            [],
            $this->stream->getMetadata(),
            \sprintf(
                '%s::%s() failed returning expected data for metadata of closed stream.',
                \get_class($this->stream),
                'getMetadata'
            )
        );
    }

    /**
     * @covers ::getStat
     * @depends testConstruct
     * @depends testClose
     */
    public function testGetStat()
    {
        static::assertSame(
            \array_filter(\fstat($this->resource), function ($key) {
                return \is_string($key);
            }, ARRAY_FILTER_USE_KEY),
            $this->stream->getStat(),
            \sprintf(
                '%s::%s() failed getting stat of open stream.',
                \get_class($this->stream),
                'getStat'
            )
        );

        $this->stream->close();

        static::assertSame(
            [],
            $this->stream->getStat(),
            \sprintf(
                '%s::%s() failed returning expected data for stat of closed stream.',
                \get_class($this->stream),
                'getStat'
            )
        );
    }

    /**
     * @covers ::isLockable
     * @depends testConstruct
     */
    public function testIsLockable()
    {
        $class = $this->stream;
        \fclose($this->resource);
        static::assertFalse(
            $this->stream->isLockable(),
            \sprintf(
                '%s::%s() failed to return expected result for closed stream.',
                \get_class($this->stream),
                'isLockable'
            )
        );

        $this->stream = null;

        foreach ([[\tmpfile(), true], [\fopen('php://memory', 'r+'), false]] as $set) {
            list($resource, $lockable) = $set;
            $this->resource            = $resource;
            $this->stream              = new $class($this->resource);
            static::assertSame(
                $lockable,
                $this->stream->isLockable(),
                \sprintf(
                    '%s::%s() failed to return expected result while testing if given stream is lockable.',
                    \get_class($this->stream),
                    'isLockable'
                )
            );
        }
    }

    /**
     * @covers ::lock
     * @depends testConstruct
     * @depends testIsLockable
     */
    public function testLock()
    {
        \set_time_limit(30);

        $meta        = \stream_get_meta_data($this->resource);
        $newResource = \fopen($meta['uri'], 'w+');

        // Test locking
        $this->stream->lock(LOCK_EX | LOCK_NB);

        $res = \flock($newResource, LOCK_EX | LOCK_NB, $wouldBlock);
        static::assertSame(
            [
                'another_flock' => false,
                'would_block'   => true,
            ],
            [
                'another_flock' => $res,
                'would_block'   => (bool) $wouldBlock,
            ],
            \sprintf(
                '%s::%s() failed locking resource.',
                \get_class($this->stream),
                'lock'
            )
        );

        // Test lock failure
        \flock($this->resource, LOCK_UN | LOCK_NB);
        \flock($newResource, LOCK_EX | LOCK_NB);

        try {
            $wouldBlock = null;
            $this->stream->lock(LOCK_EX | LOCK_NB, $wouldBlock);
            $this->fail('Expected locking exception.');
        } catch (RuntimeException $e) {
            static::assertSame(
                [
                    'message' => \sprintf(
                        'Failed performing lock operation (%d).',
                        LOCK_EX | LOCK_NB
                    ),
                    'would_block' => true,
                ],
                [
                    'message'     => $e->getMessage(),
                    'would_block' => $wouldBlock,
                ],
                \sprintf(
                    '%s::%s() failed to produce expected error on failed locking.',
                    \get_class($this->stream),
                    'lock'
                )
            );
        }

        \fclose($this->resource);
        $this->expectException(NotLockableException::class);
        $this->stream->lock(LOCK_UN | LOCK_NB);
    }

    /**
     * @covers ::eof
     * @depends testConstruct
     * @depends testClose
     */
    public function testEof()
    {
        $someStringToWrite = \implode('', \array_fill(0, \random_int(30, 40), 'x')).\microtime();
        \fwrite($this->resource, $someStringToWrite);
        \fseek($this->resource, 0, SEEK_SET);

        do {
            static::assertSame(
                \feof($this->resource),
                $this->stream->eof(),
                \sprintf(
                    '%s::%s() failed to return expected result.',
                    \get_class($this->stream),
                    'eof'
                )
            );
            \fread($this->resource, 1);
        } while (!\feof($this->resource));

        $this->stream->close();

        static::assertTrue(
            $this->stream->eof(),
            \sprintf(
                '%s::%s() failed to return expected value when stream is closed.',
                \get_class($this->stream),
                'eof'
            )
        );
    }

    /**
     * @covers ::seek
     * @depends testConstruct
     */
    public function testSeek__success()
    {
        $someStringToWrite = \implode('', \array_fill(0, \random_int(30, 40), 'x')).\microtime();
        $strLen            = \mb_strlen($someStringToWrite);
        \fwrite($this->resource, $someStringToWrite);
        \fseek($this->resource, 0, SEEK_SET);

        // Seek end
        $endOffset = \random_int((int) \ceil($strLen / 4), (int) $strLen - (int) \ceil($strLen / 4));
        $this->stream->seek(-$endOffset, SEEK_END);
        static::assertSame(
            $strLen - $endOffset,
            \ftell($this->resource),
            \sprintf(
                '%s::%s() failed adjusting pointer position relative to end (using SEEK_END whence).',
                \get_class($this->stream),
                'seek'
            )
        );

        // Seek set
        $startOffset = \random_int((int) \ceil($strLen / 4), (int) $strLen - (int) \ceil($strLen / 4));
        $this->stream->seek($startOffset, SEEK_SET);
        static::assertSame(
            $startOffset,
            \ftell($this->resource),
            \sprintf(
                '%s::%s() failed adjusting pointer position relative to start (using SEEK_SET whence).',
                \get_class($this->stream),
                'seek'
            )
        );

        // Seek cur
        $startOffset = \random_int((int) \ceil($strLen / 4), (int) $strLen - (int) \ceil($strLen / 4));
        $currOffset  = \random_int((int) \ceil($strLen / 4), (int) $strLen - (int) \ceil($strLen / 4));
        $this->stream->seek($startOffset, SEEK_SET);
        $this->stream->seek($currOffset, SEEK_CUR);
        static::assertSame(
            $startOffset + $currOffset,
            \ftell($this->resource),
            \sprintf(
                '%s::%s() failed adjusting pointer position relative to current position (using SEEK_CUR whence).',
                \get_class($this->stream),
                'seek'
            )
        );

        // Seek default
        $startOffset = \random_int((int) \ceil($strLen / 4), (int) $strLen - (int) \ceil($strLen / 4));
        $this->stream->seek($startOffset);
        static::assertSame(
            $startOffset,
            \ftell($this->resource),
            \sprintf(
                '%s::%s() failed adjusting pointer position relative to start (using no specific whence).',
                \get_class($this->stream),
                'seek'
            )
        );
    }

    /**
     * @covers ::seek
     * @depends testConstruct
     * @depends testSeek__success
     */
    public function testSeek__failure__invalidWhence()
    {
        $whence = \random_int(-10, -1);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            \sprintf(
                '$whence for seeking should be one of %s (%d), %s (%d) or %s (%d), but got %d.',
                'SEEK_SET',
                SEEK_SET,
                'SEEK_CUR',
                SEEK_CUR,
                'SEEK_END',
                SEEK_END,
                $whence
            )
        );

        $this->stream->seek(0, $whence);
    }

    /**
     * @covers ::seek
     * @depends testConstruct
     * @depends testSeek__success
     */
    public function testSeek__failure__notSeekable()
    {
        $class = \get_class($this->stream);
        $this->stream->close();
        $this->resource = \fopen('php://stdin', 'r');
        $this->stream   = new $class($this->resource);

        $this->expectException(NotSeekableException::class);

        $this->stream->seek(0, 0);
    }

    /**
     * @covers ::seek
     * @depends testConstruct
     * @depends testSeek__success
     */
    public function testSeek__failure__failSeek()
    {
        $offset = -\random_int(100, 200);
        $whence = SEEK_CUR;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Failed seeking stream (Offset: %d, Whence: %d).',
                $offset,
                $whence
            )
        );

        $this->stream->seek($offset, $whence);
    }

    /**
     * @covers ::write
     * @depends testConstruct
     */
    public function testWrite__success()
    {
        $someStringToWrite = \implode('', \array_fill(0, \random_int(30, 40), 'x')).\microtime();
        $strLen            = \mb_strlen($someStringToWrite);

        static::assertSame(
            $strLen,
            $this->stream->write($someStringToWrite),
            \sprintf(
                '%s::%s() failed to return number of bytes written.',
                \get_class($this->stream),
                'write'
            )
        );
        $this->stream->write('');

        \fseek($this->resource, 0, SEEK_SET);

        static::assertSame(
            $someStringToWrite,
            \stream_get_contents($this->resource),
            \sprintf(
                '%s::%s() failed writing to stream.',
                \get_class($this->stream),
                'write'
            )
        );
    }

    /**
     * @covers ::write
     * @depends testConstruct
     * @depends testWrite__success
     */
    public function testWrite__failure__notWritable()
    {
        $class = \get_class($this->stream);
        $this->stream->close();
        $this->resource = \fopen('php://stdin', 'r');
        $this->stream   = new $class($this->resource);

        $this->expectException(NotWritableException::class);

        $this->stream->write('');
    }

    /**
     * @covers ::write
     * @depends testConstruct
     * @depends testWrite__success
     */
    public function testWrite__failure__failWrite()
    {
        $class = \get_class($this->stream);
        $mock  = $this
            ->getMockBuilder($class)
            ->setConstructorArgs([$this->resource])
            ->setMethods(['isWritable'])
            ->getMock();
        \fclose($this->resource);
        $mock->expects(static::any())->method('isWritable')->willReturn(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed writing string to stream.');

        $mock->write('something to write');
    }

    /**
     * @covers ::read
     * @depends testConstruct
     */
    public function testRead__success()
    {
        $strings = [
            \implode('', \array_fill(0, \random_int(30, 40), 'x')).\microtime(),
            \implode('', \array_fill(0, \random_int(50, 60), 'x')).\microtime(),
        ];
        foreach ($strings as $string) {
            \fwrite($this->resource, $string);
        }
        \fseek($this->resource, 0, SEEK_SET);

        foreach ($strings as $string) {
            static::assertSame(
                $string,
                $this->stream->read(\mb_strlen($string)),
                \sprintf(
                    '%s::%s() failed to return expected value.',
                    \get_class($this->stream),
                    'read'
                )
            );
        }
        static::assertSame(
            '',
            $this->stream->read(1),
            \sprintf(
                '%s::%s() failed to return expected value from empty stream.',
                \get_class($this->stream),
                'read'
            )
        );
    }

    /**
     * @covers ::read
     * @depends testConstruct
     * @depends testRead__success
     */
    public function testRead__failure__notReadable()
    {
        $class = \get_class($this->stream);
        $this->stream->close();
        $this->resource = \fopen('php://stdout', 'w');
        $this->stream   = new $class($this->resource);

        $this->expectException(NotReadableException::class);

        $this->stream->read(1);
    }

    /**
     * @covers ::read
     * @depends testConstruct
     * @depends testRead__success
     */
    public function testRead__failure__negativeLength()
    {
        $len = -\random_int(10, 20);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Reading length can not be less then 0. Got %d.',
                $len
            )
        );

        $this->stream->read($len);
    }

    /**
     * @covers ::read
     * @depends testConstruct
     * @depends testRead__success
     */
    public function testRead__failure__failRead()
    {
        $class = \get_class($this->stream);
        $mock  = $this
            ->getMockBuilder($class)
            ->setConstructorArgs([$this->resource])
            ->setMethods(['isReadable'])
            ->getMock();
        \fclose($this->resource);
        $mock->expects(static::any())->method('isReadable')->willReturn(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed reading from stream.');

        $mock->read(1);
    }

    /**
     * @covers ::readLine
     * @depends testConstruct
     */
    public function testReadLine__success()
    {
        $strings = [
            \implode('', \array_fill(0, \random_int(30, 40), 'x')).\microtime(),
            \implode('', \array_fill(0, \random_int(50, 60), 'y')).\microtime(),
        ];
        $newLineStrings = [
            \implode('', \array_fill(0, \random_int(70, 80), 'z')).\microtime(),
            \implode('', \array_fill(0, \random_int(10, 20), 'q')).\microtime(),
        ];

        foreach ($strings as $string) {
            \fwrite($this->resource, $string);
        }
        \fwrite($this->resource, PHP_EOL);
        foreach ($newLineStrings as $string) {
            \fwrite($this->resource, $string);
        }

        \fseek($this->resource, 0, SEEK_SET);

        foreach ($strings as $string) {
            static::assertSame(
                $string,
                $this->stream->readLine(\mb_strlen($string)),
                \sprintf(
                    '%s::%s() failed to return expected value.',
                    \get_class($this->stream),
                    'readLine'
                )
            );
        }

        \rewind($this->resource);

        static::assertSame(
            \implode('', $strings).PHP_EOL,
            $this->stream->readLine(),
            \sprintf(
                '%s::%s() failed to return expected value.',
                \get_class($this->stream),
                'readLine'
            )
        );

        static::assertSame(
            \implode('', $newLineStrings),
            $this->stream->readLine(),
            \sprintf(
                '%s::%s() failed to return expected value.',
                \get_class($this->stream),
                'readLine'
            )
        );

        $read = [];
        $this->stream->rewind();
        while (!$this->stream->eof()) {
            $read[] = $this->stream->readLine();
        }

        static::assertSame(
            [
                \implode('', $strings)."\n",
                \implode('', $newLineStrings),
            ],
            $read,
            \sprintf(
                '%s::%s() failed to return expected results when last line has content.',
                \get_class($this->stream),
                'readLine'
            )
        );

        $read = [];
        $this->stream->fastForward();
        $this->stream->write(PHP_EOL);
        $this->stream->rewind();
        while (!$this->stream->eof()) {
            $read[] = $this->stream->readLine();
        }

        static::assertSame(
            [
                \implode('', $strings).PHP_EOL,
                \implode('', $newLineStrings).PHP_EOL,
                '',
            ],
            $read,
            \sprintf(
                '%s::%s() failed to return expected results when last line has no content.',
                \get_class($this->stream),
                'readLine'
            )
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Failed reading line from stream.'
        );

        $this->stream->readLine();
    }

    /**
     * @covers ::readLine
     * @depends testConstruct
     * @depends testReadLine__success
     */
    public function testReadLine__failure__negativeLength()
    {
        $len = -\random_int(10, 20);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Line reading length can not be less then 0. Got %d.',
                $len
            )
        );

        $this->stream->readLine($len);
    }

    /**
     * @covers ::readLine
     * @depends testConstruct
     * @depends testReadLine__success
     */
    public function testReadLine__failure__notReadable()
    {
        $class = \get_class($this->stream);
        $this->stream->close();
        $this->resource = \fopen('php://stdout', 'w');
        $this->stream   = new $class($this->resource);

        $this->expectException(NotReadableException::class);

        $this->stream->readLine();
    }

    /**
     * @covers ::setBlocking
     * @depends testConstruct
     */
    public function testSetBlocking__success()
    {
        foreach ([true, false] as $blocking) {
            $this->stream->setBlocking($blocking);
            $metadata = \stream_get_meta_data($this->resource);

            static::assertSame(
                $metadata['blocked'],
                $blocking,
                \sprintf(
                    '%s::%s() failed to set blocking mode.',
                    \get_class($this->stream),
                    'setBlocking'
                )
            );
        }
    }

    /**
     * @covers ::setBlocking
     * @depends testConstruct
     * @depends testSetBlocking__success
     */
    public function testSetBlocking__failure()
    {
        $class = \get_class($this->stream);
        $mock  = $this
            ->getMockBuilder($class)
            ->setConstructorArgs([$this->resource])
            ->setMethods(['isOpen'])
            ->getMock();
        \fclose($this->resource);

        $mock->expects(static::any())->method('isOpen')->willReturn(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Failed setting blocking mode on a stream.'
        );

        $mock->setBlocking(true);
    }

    /**
     * @covers ::isLocal
     * @depends testConstruct
     * @runInSeparateProcess
     */
    public function testIsLocal__success()
    {
        // Local
        static::assertTrue(
            $this->stream->isLocal(),
            \sprintf(
                '%s::%s() should return true for a local resource.',
                \get_class($this->stream),
                'isLocal'
            )
        );

        // Close local
        $this->stream->close();
        $this->stream   = null;
        $this->resource = null;

        // Open Remote
        $port     = \random_int(45000, 45999);
        $lifetime = 3;
        $process  = new Process(
            \sprintf(
                'timeout %d php -S 127.0.0.1:%d -t %s',
                $lifetime,
                $port,
                \realpath(__DIR__.'/../test_server')
            )
        );

        $start = \time();
        $process->start();
        \sleep(1);

        if (!$process->isRunning()) {
            $this->fail('Failed to start local PHP server: '.$process->getErrorOutput());
        }

        $path           = \sprintf('http://127.0.0.1:%d/file.txt', $port);
        $this->resource = \fopen($path, 'r');
        $this->stream   = new Stream($this->resource);
        $local          = $this->stream->isLocal();

        $timeout = 10;
        $process->stop($timeout);

        do {
            \usleep(1);
        } while ($process->isRunning() && $start + $lifetime >= \time());

        // Remote
        static::assertFalse(
            $local,
            \sprintf(
                '%s::%s() should return false for a non local resource.',
                \get_class($this->stream),
                'isLocal'
            )
        );
    }

    /**
     * @covers ::tell
     * @depends testConstruct
     */
    public function testTell__success()
    {
        $someStringToWrite = \implode('', \array_fill(0, \random_int(30, 40), 'x')).\microtime();
        $strLen            = \mb_strlen($someStringToWrite);
        \fwrite($this->resource, $someStringToWrite);
        \fseek($this->resource, 0, SEEK_SET);

        do {
            static::assertSame(
                \ftell($this->resource),
                $this->stream->tell(),
                \sprintf(
                    '%s::%s() failed returning expected result.',
                    \get_class($this->stream),
                    'tell'
                )
            );
            \fseek($this->resource, 1, SEEK_CUR);
        } while (\ftell($this->resource) < $strLen);

        do {
            static::assertSame(
                \ftell($this->resource),
                $this->stream->tell(),
                \sprintf(
                    '%s::%s() failed returning expected result.',
                    \get_class($this->stream),
                    'tell'
                )
            );
            \fseek($this->resource, -1, SEEK_CUR);
        } while (\ftell($this->resource) > 0);

        $this->stream->close();

        static::assertNull(
            $this->stream->tell(),
            \sprintf(
                '%s::%s() failed to return expected value for closed stream.',
                \get_class($this->stream),
                'tell'
            )
        );
    }

    /**
     * @covers ::tell
     * @depends testConstruct
     * @depends testTell__success
     */
    public function testTell__failure__failedTelling()
    {
        $class = \get_class($this->stream);
        $mock  = $this
            ->getMockBuilder($class)
            ->setConstructorArgs([$this->resource])
            ->setMethods(['isOpen'])
            ->getMock();
        \fclose($this->resource);

        $mock->expects(static::any())->method('isOpen')->willReturn(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Failed returning stream pointer position.'
        );

        $mock->tell();
    }

    /**
     * @covers ::truncate
     * @depends testConstruct
     */
    public function testTruncate__success()
    {
        $string1 = 'some string -'.\microtime(true).' -  to write';
        $string2 = 'another string -'.\time();
        \fwrite($this->resource, $string1);
        \fwrite($this->resource, $string2);

        $this->stream->truncate(\mb_strlen($string1));
        \rewind($this->resource);

        static::assertSame(
            $string1,
            \stream_get_contents($this->resource),
            \sprintf(
                '%s::%s() failed to truncate stream to smaller size.',
                \get_class($this->stream),
                'truncate'
            )
        );

        $len        = \random_int(10, 20);
        $stringNull = \implode('', \array_fill(0, $len, "\0"));
        \rewind($this->resource);
        $this->stream->truncate(\mb_strlen($string1) + $len);
        static::assertSame(
            $string1.$stringNull,
            \stream_get_contents($this->resource),
            \sprintf(
                '%s::%s() failed to truncate stream to greater size.',
                \get_class($this->stream),
                'truncate'
            )
        );

        $this->stream->truncate(0);
        \rewind($this->resource);
        static::assertSame(
            '',
            \stream_get_contents($this->resource),
            \sprintf(
                '%s::%s() failed to truncate stream to 0 size.',
                \get_class($this->stream),
                'truncate'
            )
        );
    }

    /**
     * @covers ::truncate
     * @depends testConstruct
     */
    public function testTruncate__failure__negativeLength()
    {
        $len = -\random_int(10, 20);
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Can not truncate stream to less then 0. Got %d.',
                $len
            )
        );
        $this->stream->truncate($len);
    }

    /**
     * @covers ::truncate
     * @depends testConstruct
     */
    public function testTruncate__failure__notWritable()
    {
        $class          = \get_class($this->stream);
        $this->resource = \fopen('php://stdin', 'r');
        $this->stream   = new $class($this->resource);

        $len = \random_int(10, 20);
        $this->expectException(NotWritableException::class);
        $this->stream->truncate($len);
    }

    /**
     * @covers ::truncate
     * @depends testConstruct
     */
    public function testTruncate__failure__failed()
    {
        $len = \random_int(10, 20);

        $class = \get_class($this->stream);
        $mock  = $this
            ->getMockBuilder($class)
            ->setConstructorArgs([$this->resource])
            ->setMethods(['isWritable'])
            ->getMock();
        \fclose($this->resource);
        $mock->expects(static::any())->method('isWritable')->willReturn(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Failed truncating stream to %d length.',
                $len
            )
        );
        $mock->truncate($len);
    }

    /**
     * @covers ::getContents
     * @depends testConstruct
     */
    public function testGetContents__success()
    {
        $strings = [
            \implode('', \array_fill(0, \random_int(30, 40), 'x')).\microtime(),
            \implode('', \array_fill(0, \random_int(50, 60), 'y')).\microtime(),
            \implode('', \array_fill(0, \random_int(10, 20), 'z')).\microtime(),
        ];

        foreach ($strings as $string) {
            \fwrite($this->resource, $string);
        }

        $firstString = \array_shift($strings);
        \fseek($this->resource, \mb_strlen($firstString), SEEK_SET);

        static::assertSame(
            \implode('', $strings),
            $this->stream->getContents(),
            \sprintf(
                '%s::%s() failed to return remainder of contents.',
                \get_class($this->stream),
                'getContents'
            )
        );

        static::assertSame(
            '',
            $this->stream->getContents(),
            \sprintf(
                '%s::%s() failed to return expected value for stream with no more contents.',
                \get_class($this->stream),
                'getContents'
            )
        );
    }

    /**
     * @covers ::getContents
     * @depends testConstruct
     * @depends testGetContents__success
     */
    public function testGetContents__failure__notReadable()
    {
        \fclose($this->resource);
        $this->expectException(NotReadableException::class);
        $this->stream->getContents();
    }

    /**
     * @covers ::getContents
     * @depends testConstruct
     * @depends testGetContents__success
     */
    public function testGetContents__failure__failRead()
    {
        $class = \get_class($this->stream);
        $mock  = $this
            ->getMockBuilder($class)
            ->setConstructorArgs([$this->resource])
            ->setMethods(['isReadable'])
            ->getMock();
        \fclose($this->resource);
        $mock->expects(static::any())->method('isReadable')->willReturn(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed reading from stream.');

        $mock->getContents();
    }

    /**
     * @covers ::writeCsv
     * @depends testConstruct
     */
    public function testWriteCsv__success()
    {
        $tmpFile = \tmpfile();

        $delimiter = ';';
        $enclosure = '#';
        $escape    = '/';
        $data      = ['some', 'data', \microtime(true), '-'.$enclosure.'-', '-'.$escape.'-', '-'.$delimiter.'-'];

        $res = \fputcsv($tmpFile, $data, $delimiter, $enclosure, $escape);

        static::assertSame(
            $res,
            $this->stream->writeCsv($data, $delimiter, $enclosure, $escape),
            \sprintf(
                '%s::%s() failed to return expected result.',
                \get_class($this->stream),
                'writeCsv'
            )
        );

        \rewind($this->resource);
        \rewind($tmpFile);

        static::assertSame(
            \stream_get_contents($tmpFile),
            \stream_get_contents($this->resource),
            \sprintf(
                '%s::%s() failed to write expected string to stream.',
                \get_class($this->stream),
                'writeCsv'
            )
        );

        \ftruncate($this->resource, 0);
        \ftruncate($tmpFile, 0);

        $data = ['some', 'data', \microtime(true), '-'.$enclosure.'-', '-'.$escape.'-', '-'.$delimiter.'-'];

        $res = \fputcsv($tmpFile, $data);

        static::assertSame(
            $res,
            $this->stream->writeCsv($data),
            \sprintf(
                '%s::%s() failed to return expected result.',
                \get_class($this->stream),
                'writeCsv'
            )
        );

        \rewind($this->resource);
        \rewind($tmpFile);

        static::assertSame(
            \stream_get_contents($tmpFile),
            \stream_get_contents($this->resource),
            \sprintf(
                '%s::%s() failed to write expected string to stream.',
                \get_class($this->stream),
                'writeCsv'
            )
        );
    }

    /**
     * @covers ::writeCsv
     * @depends testConstruct
     * @depends testWriteCsv__success
     */
    public function testWriteCsv__failure__notWritable()
    {
        $class = \get_class($this->stream);
        $this->stream->close();
        $this->resource = \fopen('php://stdin', 'r');
        $this->stream   = new $class($this->resource);

        $this->expectException(NotWritableException::class);

        $this->stream->writeCsv([]);
    }

    /**
     * @covers ::writeCsv
     * @depends testConstruct
     * @depends testWriteCsv__success
     */
    public function testWriteCsv__failure__delimiterLength()
    {
        $delimiter = \chr(\random_int(\ord('A'), \ord('Z'))).\chr(\random_int(\ord('A'), \ord('Z')));

        $this->expectException(LengthException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Delimiter character should be 1 character string. Got \'%s\'.',
                $delimiter
            )
        );

        $this->stream->writeCsv([], $delimiter);
    }

    /**
     * @covers ::writeCsv
     * @depends testConstruct
     * @depends testWriteCsv__success
     */
    public function testWriteCsv__failure__enclosureLength()
    {
        $enclosure = \chr(\random_int(\ord('A'), \ord('Z'))).\chr(\random_int(\ord('A'), \ord('Z')));

        $this->expectException(LengthException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Enclosure character should be 1 character string. Got \'%s\'.',
                $enclosure
            )
        );

        $this->stream->writeCsv([], ',', $enclosure);
    }

    /**
     * @covers ::writeCsv
     * @depends testConstruct
     * @depends testWriteCsv__success
     */
    public function testWriteCsv__failure__escapeCharLength()
    {
        $escapeChar = \chr(\random_int(\ord('A'), \ord('Z'))).\chr(\random_int(\ord('A'), \ord('Z')));

        $this->expectException(LengthException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Escape character should be 1 character string. Got \'%s\'.',
                $escapeChar
            )
        );

        $this->stream->writeCsv([], ',', '"', $escapeChar);
    }

    public function dataProvider__testWriteCsv__failure__uniqueChars()
    {
        $return = [];
        foreach ([true, false] as $sameDelimiter) {
            foreach ([true, false] as $sameEnclosure) {
                foreach ([true, false] as $sameEscape) {
                    $same = 0 + ($sameDelimiter ? 1 : 0) + ($sameEnclosure ? 1 : 0) + ($sameEscape ? 1 : 0);
                    if ($same >= 2) {
                        $return[] = [
                            $sameDelimiter,
                            $sameEnclosure,
                            $sameEscape,
                        ];
                    }
                }
            }
        }

        return $return;
    }

    /**
     * @covers ::writeCsv
     * @depends testConstruct
     * @depends testWriteCsv__success
     * @dataProvider dataProvider__testWriteCsv__failure__uniqueChars
     *
     * @param bool $sameDelimiter
     * @param bool $sameEnclosure
     * @param bool $sameEscape
     */
    public function testWriteCsv__failure__uniqueChars(bool $sameDelimiter, bool $sameEnclosure, bool $sameEscape)
    {
        $sameChar      = \chr(\random_int(\ord('A'), \ord('Z')));
        $delimiterChar = $sameDelimiter ? $sameChar : ',';
        $enclosureChar = $sameEnclosure ? $sameChar : '"';
        $escapeChar    = $sameEscape ? $sameChar : '\\';

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'CSV delimiter (%s), enclosure (%s) and escape character (%s) should all be unique.',
                $delimiterChar,
                $enclosureChar,
                $escapeChar
            )
        );

        $this->stream->writeCsv([], $delimiterChar, $enclosureChar, $escapeChar);
    }

    /**
     * @covers ::writeCsv
     * @depends testConstruct
     * @depends testWriteCsv__success
     */
    public function testWriteCsv__failure__failWrite()
    {
        $class = \get_class($this->stream);
        $mock  = $this
            ->getMockBuilder($class)
            ->setConstructorArgs([$this->resource])
            ->setMethods(['isWritable'])
            ->getMock();
        \fclose($this->resource);
        $mock->expects(static::any())->method('isWritable')->willReturn(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed writing CSV to stream.');

        $mock->writeCsv([]);
    }

    /**
     * @covers ::readCsv
     * @depends testConstruct
     */
    public function testReadCsv__success()
    {
        $delimiter = ';';
        $enclosure = '#';
        $escape    = '/';
        $data      = ['some', 'data', \microtime(false), '-'.$enclosure.'-', '-'.$escape.'-', '-'.$delimiter.'-'];

        $res = \fputcsv($this->resource, $data, $delimiter, $enclosure, $escape);
        \rewind($this->resource);

        $res = $this->stream->readCsv(0, $delimiter, $enclosure, $escape);

        static::assertSame(
            $data,
            $res,
            \sprintf(
                '%s::%s() failed reading CSV.',
                \get_class($this->stream),
                'readCsv'
            )
        );

        \rewind($this->resource);
        $line  = \fgets($this->resource);
        $limit = (int) (\mb_strlen($line) / 6);

        \rewind($this->resource);
        $expected = \fgetcsv($this->resource, $limit, $delimiter, $enclosure, $escape);
        \rewind($this->resource);

        static::assertSame(
            $expected,
            $this->stream->readCsv($limit, $delimiter, $enclosure, $escape),
            \sprintf(
                '%s::%s() failed to return expected result.',
                \get_class($this->stream),
                'readCsv'
            )
        );

        \rewind($this->resource);
        \ftruncate($this->resource, 0);
        \fputcsv($this->resource, $data);
        \fputcsv($this->resource, $data);
        \rewind($this->resource);

        for ($i = 1; $i <= 2; ++$i) {
            static::assertSame(
                $data,
                $this->stream->readCsv(),
                \sprintf(
                    '%s::%s() failed to return expected result.',
                    \get_class($this->stream),
                    'readCsv'
                )
            );
        }

        $resource = \tmpfile();
        $class    = \get_class($this->stream);
        $stream   = new $class($resource);
        \fwrite($resource, PHP_EOL, \mb_strlen(PHP_EOL));
        \fwrite($resource, PHP_EOL, \mb_strlen(PHP_EOL));
        \rewind($resource);

        for ($i=1; $i <= 2; ++$i) {
            static::assertSame(
                [],
                $stream->readCsv(),
                \sprintf(
                    '%s::%s() failed to return empty array as result for empty (and last) line.',
                    \get_class($stream),
                    'readCsv'
                )
            );
        }

        static::assertSame(
            [],
            $this->stream->readCsv(),
            \sprintf(
                '%s::%s() failed to return empty array as result for empty and last line.',
                \get_class($stream),
                'readCsv'
            )
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed reading CSV from stream.');
        $this->stream->readCsv();
    }

    /**
     * @covers ::readCsv
     * @depends testConstruct
     * @depends testReadCsv__success
     */
    public function testReadCsv__failure__failRead()
    {
        while (!$this->stream->eof()) {
            $this->stream->readCsv();
        }
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed reading CSV from stream.');
        $this->stream->readCsv();
    }

    /**
     * @covers ::readCsv
     * @depends testConstruct
     * @depends testReadCsv__success
     */
    public function testReadCsv__failure__notReadable()
    {
        $class = \get_class($this->stream);
        $this->stream->close();
        $this->resource = \fopen('php://stdout', 'w');
        $this->stream   = new $class($this->resource);

        $this->expectException(NotReadableException::class);

        $this->stream->readCsv();
    }

    /**
     * @covers ::readCsv
     * @depends testConstruct
     * @depends testReadCsv__success
     */
    public function testReadCsv__failure__negativeLength()
    {
        $len = -\random_int(10, 20);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'CSV reading length can not be less then 0. Got %d.',
                $len
            )
        );

        $this->stream->readCsv($len);
    }

    public function dataProvider__testReadCsv__failure__uniqueChars()
    {
        $return = [];
        foreach ([true, false] as $sameDelimiter) {
            foreach ([true, false] as $sameEnclosure) {
                foreach ([true, false] as $sameEscape) {
                    $same = 0 + ($sameDelimiter ? 1 : 0) + ($sameEnclosure ? 1 : 0) + ($sameEscape ? 1 : 0);
                    if ($same >= 2) {
                        $return[] = [
                            $sameDelimiter,
                            $sameEnclosure,
                            $sameEscape,
                        ];
                    }
                }
            }
        }

        return $return;
    }

    /**
     * @covers ::readCsv
     * @depends testConstruct
     * @depends testReadCsv__success
     */
    public function testReadCsv__failure__delimiterLength()
    {
        $delimiter = \chr(\random_int(\ord('A'), \ord('Z'))).\chr(\random_int(\ord('A'), \ord('Z')));

        $this->expectException(LengthException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Delimiter character should be 1 character string. Got \'%s\'.',
                $delimiter
            )
        );

        $this->stream->readCsv(0, $delimiter);
    }

    /**
     * @covers ::readCsv
     * @depends testConstruct
     * @depends testReadCsv__success
     */
    public function testReadCsv__failure__enclosureLength()
    {
        $enclosure = \chr(\random_int(\ord('A'), \ord('Z'))).\chr(\random_int(\ord('A'), \ord('Z')));

        $this->expectException(LengthException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Enclosure character should be 1 character string. Got \'%s\'.',
                $enclosure
            )
        );

        $this->stream->readCsv(0, ',', $enclosure);
    }

    /**
     * @covers ::readCsv
     * @depends testConstruct
     * @depends testReadCsv__success
     */
    public function testReadCsv__failure__escapeCharLength()
    {
        $escapeChar = \chr(\random_int(\ord('A'), \ord('Z'))).\chr(\random_int(\ord('A'), \ord('Z')));

        $this->expectException(LengthException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Escape character should be 1 character string. Got \'%s\'.',
                $escapeChar
            )
        );

        $this->stream->readCsv(0, ',', '"', $escapeChar);
    }

    /**
     * @covers ::readCsv
     * @depends testConstruct
     * @depends testReadCsv__success
     * @dataProvider dataProvider__testReadCsv__failure__uniqueChars
     *
     * @param bool $sameDelimiter
     * @param bool $sameEnclosure
     * @param bool $sameEscape
     */
    public function testReadCsv__failure__uniqueChars(bool $sameDelimiter, bool $sameEnclosure, bool $sameEscape)
    {
        $sameChar      = \chr(\random_int(\ord('A'), \ord('Z')));
        $delimiterChar = $sameDelimiter ? $sameChar : ',';
        $enclosureChar = $sameEnclosure ? $sameChar : '"';
        $escapeChar    = $sameEscape ? $sameChar : '\\';

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'CSV delimiter (%s), enclosure (%s) and escape character (%s) should all be unique.',
                $delimiterChar,
                $enclosureChar,
                $escapeChar
            )
        );

        $this->stream->readCsv(0, $delimiterChar, $enclosureChar, $escapeChar);
    }

    /**
     * @covers ::copyToStream
     * @depends testConstruct
     */
    public function testCopyToStream__success__directStreamAccess__default()
    {
        $class     = \get_class($this->stream);
        $newTmp    = \tmpfile();
        $newStream = new $class($newTmp);

        \fwrite($this->resource, \file_get_contents(__DIR__.'/lorem_ipsum.txt'));
        \fseek($this->resource, 461);
        $expected = \stream_get_contents($this->resource);
        \fseek($this->resource, 461);

        //stream_copy_to_stream($this->resource, $newTmp);
        static::assertSame(
            \mb_strlen($expected),
            $this->stream->copyToStream($newStream),
            'Expected number of bytes copied to be returned.'
        );

        \rewind($newTmp);

        static::assertSame(
            $expected,
            \stream_get_contents($newTmp),
            'Failed copying stream data (using support to direct stream access).'
        );
    }

    /**
     * @covers ::copyToStream
     * @depends testConstruct
     */
    public function testCopyToStream__success__directStreamAccess__withMaxLength()
    {
        $class     = \get_class($this->stream);
        $newTmp    = \tmpfile();
        $newStream = new $class($newTmp);

        \fwrite($this->resource, \file_get_contents(__DIR__.'/lorem_ipsum.txt'));
        \fseek($this->resource, 393);
        $expected = \fread($this->resource, 67);
        \fseek($this->resource, 393);

        //stream_copy_to_stream($this->resource, $newTmp);
        static::assertSame(
            \mb_strlen($expected),
            $this->stream->copyToStream($newStream, 67),
            'Expected number of bytes copied to be returned.'
        );

        \rewind($newTmp);

        static::assertSame(
            $expected,
            \stream_get_contents($newTmp),
            'Failed copying stream data (using support to direct stream access).'
        );
    }

    /**
     * @covers ::copyToStream
     * @depends testConstruct
     */
    public function testCopyToStream__failure__directStreamAccess__failOperation()
    {
        $class = \get_class($this->stream);
        $res   = \tmpfile();
        $mock  = $this
            ->getMockBuilder($class)
            ->setConstructorArgs([$res])
            ->setMethods(['isWritable'])
            ->getMock();
        \fclose($res);
        $mock->expects(static::any())->method('isWritable')->willReturn(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed copying stream content to another stream.');

        $this->stream->copyToStream($mock);
    }

    /**
     * @covers ::copyToStream
     * @depends testConstruct
     */
    public function testCopyToStream__success__streamMethodsAccess__default()
    {
        $newTmp    = \tmpfile();
        $newStream = $this->getMockForAbstractClass(StreamInterface::class);

        $newStream
            ->expects(static::any())
            ->method('isWritable')
            ->willReturn(true);

        $newStream
            ->expects(static::any())
            ->method('eof')
            ->willReturnCallback(
                function () use (&$newTmp) {
                    return \feof($newTmp);
                }
            );

        $newStream
            ->expects(static::any())
            ->method('write')
            ->with(static::isType('string'))
            ->willReturnCallback(
                function (string $string) use (&$newTmp) {
                    return \fwrite($newTmp, $string);
                }
            );
        \fwrite($this->resource, \file_get_contents(__DIR__.'/lorem_ipsum.txt'));
        \fseek($this->resource, 461);
        $expected = \stream_get_contents($this->resource);
        \fseek($this->resource, 461);

        //stream_copy_to_stream($this->resource, $newTmp);
        static::assertSame(
            \mb_strlen($expected),
            $this->stream->copyToStream($newStream),
            'Expected number of bytes copied to be returned.'
        );

        \rewind($newTmp);

        static::assertSame(
            $expected,
            \stream_get_contents($newTmp),
            'Failed copying stream data (using support to direct stream access).'
        );
    }

    /**
     * @covers ::copyToStream
     * @depends testConstruct
     */
    public function testCopyToStream__success__streamMethodsAccess__withMaxLength()
    {
        $newTmp    = \tmpfile();
        $newStream = $this->getMockForAbstractClass(StreamInterface::class);

        $newStream
            ->expects(static::any())
            ->method('isWritable')
            ->willReturn(true);

        $newStream
            ->expects(static::any())
            ->method('eof')
            ->willReturnCallback(
                function () use (&$newTmp) {
                    return \feof($newTmp);
                }
            );

        $newStream
            ->expects(static::any())
            ->method('write')
            ->with(static::isType('string'))
            ->willReturnCallback(
                function (string $string) use (&$newTmp) {
                    return \fwrite($newTmp, $string);
                }
            );

        \fwrite($this->resource, \file_get_contents(__DIR__.'/lorem_ipsum.txt'));
        \fseek($this->resource, 393);
        $expected = \fread($this->resource, 67);
        \fseek($this->resource, 393);

        //stream_copy_to_stream($this->resource, $newTmp);
        static::assertSame(
            \mb_strlen($expected),
            $this->stream->copyToStream($newStream, 67, 30),
            'Expected number of bytes copied to be returned.'
        );

        \rewind($newTmp);

        static::assertSame(
            $expected,
            \stream_get_contents($newTmp),
            'Failed copying stream data (using support to direct stream access).'
        );
    }

    /**
     * @covers ::copyToStream
     * @depends testConstruct
     */
    public function testCopyToStream__failure__streamMethodsAccess()
    {
        $exception = new \Exception('Exception message - '.\microtime(true), \random_int(100, 999));

        $newTmp    = \tmpfile();
        $newStream = $this->getMockForAbstractClass(StreamInterface::class);

        $newStream
            ->expects(static::any())
            ->method('isWritable')
            ->willReturn(true);

        $newStream
            ->expects(static::any())
            ->method('eof')
            ->willReturnCallback(
                function () use (&$newTmp) {
                    return \feof($newTmp);
                }
            );

        $newStream
            ->expects(static::any())
            ->method('write')
            ->with(static::isType('string'))
            ->willThrowException($exception);

        try {
            $this->stream->copyToStream($newStream);
            $this->fail('Expected exception to be caught.');
        } catch (\Exception $e) {
            static::assertSame(
                [
                    'message'  => \sprintf('Failed copying stream content to another stream: %s', $exception->getMessage()),
                    'code'     => $exception->getCode(),
                    'previous' => $exception,
                ],
                [
                    'message'  => $e->getMessage(),
                    'code'     => $e->getCode(),
                    'previous' => $e->getPrevious(),
                ],
                'Unexpected exception thrown'
            );
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(\sprintf('Failed copying stream content to another stream: %s', $exception->getMessage()));
        $this->expectExceptionCode($exception->getCode());

        throw $e;
    }

    /**
     * @covers ::copyToStream
     * @depends testConstruct
     */
    public function testCopyToStream__failure__sourceNotReadable()
    {
        $class = \get_class($this->stream);
        $mock  = $this
            ->getMockBuilder($class)
            ->setConstructorArgs([$this->resource])
            ->setMethods(['isReadable'])
            ->getMock();
        \fclose($this->resource);
        $mock->expects(static::any())->method('isReadable')->willReturn(false);

        $this->expectException(NotReadableException::class);

        $mock->copyToStream($this->stream);
    }

    /**
     * @covers ::copyToStream
     * @depends testConstruct
     */
    public function testCopyToStream__failure__targetNotWritable()
    {
        $class = \get_class($this->stream);
        $mock  = $this
            ->getMockBuilder($class)
            ->setConstructorArgs([\tmpfile()])
            ->setMethods(['isWritable'])
            ->getMock();
        $mock->expects(static::any())->method('isWritable')->willReturn(false);

        $this->expectException(NotWritableException::class);

        $this->stream->copyToStream($mock);
    }

    /**
     * @covers ::copyToStream
     * @depends testConstruct
     */
    public function testCopyToStream__failure__chunkSizeToSmall()
    {
        $target = new Stream(\tmpfile());

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Chunk size for stream copy can not be less then 1.');

        $this->stream->copyToStream($target, -1, 0);
    }

    /**
     * @covers ::output
     * @depends testConstruct
     */
    public function testOutput__success()
    {
        $strings = [
            \implode('', \array_fill(0, \random_int(30, 40), 'x')).\microtime(),
            \implode('', \array_fill(0, \random_int(50, 60), 'y')).\microtime(),
            \implode('', \array_fill(0, \random_int(10, 20), 'z')).\microtime(),
        ];

        foreach ($strings as $string) {
            \fwrite($this->resource, $string);
        }
        $firstString = \array_shift($strings);
        \fseek($this->resource, \mb_strlen($firstString), SEEK_SET);

        \ob_start();
        $len = 0;
        foreach ($strings as $string) {
            $len += \mb_strlen($string);
        }

        static::assertSame(
            $len,
            $this->stream->output(),
            \sprintf(
                '%s::%s() failed to return expected result.',
                \get_class($this->stream),
                'output'
            )
        );
        $contents = \ob_get_contents();
        \ob_end_clean();

        static::assertSame(
            \implode('', $strings),
            $contents,
            \sprintf(
                '%s::%s() failed to output remeinder of stream to output.',
                \get_class($this->stream),
                'output'
            )
        );
    }

    /**
     * @covers ::output
     * @depends testConstruct
     * @depends testOutput__success
     */
    public function testOutput__failure__notReadable()
    {
        $class = \get_class($this->stream);
        $this->stream->close();
        $this->resource = \fopen('php://stdout', 'w');
        $this->stream   = new $class($this->resource);

        $this->expectException(NotReadableException::class);

        try {
            \ob_start();
            $this->stream->output();
        } catch (\Exception $e) {
            throw $e;
        } finally {
            \ob_end_clean();
        }
    }

    /**
     * @covers ::output
     * @depends testConstruct
     * @depends testOutput__success
     */
    public function testOutput__failure__failRead()
    {
        $class = \get_class($this->stream);
        $mock  = $this
            ->getMockBuilder($class)
            ->setConstructorArgs([$this->resource])
            ->setMethods(['isReadable'])
            ->getMock();
        \fclose($this->resource);
        $mock->expects(static::any())->method('isReadable')->willReturn(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed reading from stream.');

        try {
            \ob_start();
            $mock->output();
        } catch (\Exception $e) {
            throw $e;
        } finally {
            \ob_end_clean();
        }
    }
}
