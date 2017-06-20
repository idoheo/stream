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

use Error;
use Exception;
use Idoheo\Stream\Psr7Stream;
use Idoheo\Stream\StreamInterface;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;

/**
 * @coversDefaultClass \Idoheo\Stream\Psr7Stream
 */
class Psr7StreamTest extends TestCase
{
    /**
     * @var StreamInterface|ObjectProphecy
     */
    private $streamProphecy;

    /**
     * @var Psr7Stream
     */
    private $psr7Stream;

    protected function setUp()
    {
        parent::setUp();
        $this->streamProphecy = $this->prophesize(StreamInterface::class);
        $this->psr7Stream     = new Psr7Stream($this->streamProphecy->reveal());
    }

    protected function tearDown()
    {
        $this->psr7Stream     = null;
        $this->streamProphecy = null;
        parent::tearDown();
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        $expected   = ['stream' => $this->streamProphecy->reveal()];
        $actual     = [];
        $reflection = new \ReflectionClass($this->psr7Stream);
        foreach ($reflection->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }
            $property->setAccessible(true);
            $actual[$property->getName()] = $property->getValue($this->psr7Stream);
        }

        \ksort($expected);
        \ksort($actual);

        static::assertEquals(
            $expected,
            $actual,
            \sprintf(
                '%s::%s() failed to build object as expected.',
                \get_class($this->psr7Stream),
                '__construct'
            )
        );
    }

    /**
     * @covers ::close
     * @depends testConstruct
     */
    public function testClose()
    {
        $this->streamProphecy->close()->shouldBeCalledTimes(1);
        $this->psr7Stream->close();
    }

    /**
     * @covers ::detach
     * @depends testConstruct
     */
    public function testDetach()
    {
        $result = \fopen('php://memory', 'w+');
        $this->streamProphecy->detach()->shouldBeCalledTimes(1)->willReturn($result);
        static::assertSame(
            $result,
            $this->psr7Stream->detach(),
            \sprintf(
                '%s::%s() failed to return expected result.',
                \get_class($this->psr7Stream),
                'detach'
            )
        );
    }

    /**
     * @covers ::getMetadata
     * @depends testConstruct
     */
    public function testGetMetadata()
    {
        $result = ['metadata' => \microtime(true)];
        $this->streamProphecy->getMetadata()->shouldBeCalledTimes(1)->willReturn($result);
        static::assertSame(
            $result,
            $this->psr7Stream->getMetadata(),
            \sprintf(
                '%s::%s() failed to return expected result when no key specified.',
                \get_class($this->psr7Stream),
                'getMetadata'
            )
        );

        $result = 'some-value-'.\microtime();
        $key    = 3.14;
        $this->streamProphecy->getMetadataKey((string) $key)->shouldBeCalledTimes(1)->willReturn($result);
        static::assertSame(
            $result,
            $this->psr7Stream->getMetadata($key),
            \sprintf(
                '%s::%s() failed to return expected result when key specified.',
                \get_class($this->psr7Stream),
                'getMetadata'
            )
        );
    }

    /**
     * @covers ::eof
     * @depends testConstruct
     */
    public function testEof()
    {
        $count = 0;
        foreach ([true, false] as $result) {
            ++$count;
            $this->streamProphecy->eof()->shouldBeCalledTimes($count)->willReturn($result);
            static::assertSame(
                $result,
                $this->psr7Stream->eof(),
                \sprintf(
                    '%s::%s() failed to return expected result.',
                    \get_class($this->psr7Stream),
                    'eof'
                )
            );
        }
    }

    /**
     * @covers ::isSeekable
     * @depends testConstruct
     */
    public function testIsSeekable()
    {
        $count = 0;
        foreach ([true, false] as $result) {
            ++$count;
            $this->streamProphecy->isSeekable()->shouldBeCalledTimes($count)->willReturn($result);
            static::assertSame(
                $result,
                $this->psr7Stream->isSeekable(),
                \sprintf(
                    '%s::%s() failed to return expected result.',
                    \get_class($this->psr7Stream),
                    'isSeekable'
                )
            );
        }
    }

    /**
     * @covers ::seek
     * @depends testConstruct
     */
    public function testSeek__success()
    {
        $offset = 721;
        $whence = 1;

        $this->streamProphecy->seek((string) $offset, (float) $whence)->shouldBeCalled();
        $this->psr7Stream->seek($offset, $whence);
    }

    /**
     * @covers ::seek
     * @depends testConstruct
     */
    public function testSeek__exception()
    {
        $offset = 721;
        $whence = 1;

        $originalException = new Exception(
            'expception-message-'.\time(),
            \random_int(1000, 2000)
        );

        $this->streamProphecy->seek($offset, $whence)->shouldBeCalled()->willThrow($originalException);

        try {
            $this->psr7Stream->seek($offset, $whence);
            $this->fail('Exception expected');
        } catch (RuntimeException $e) {
            static::assertEquals(
                [
                    'message'  => $originalException->getMessage(),
                    'code'     => $originalException->getCode(),
                    'previous' => $originalException,
                ],
                [
                    'message'  => $e->getMessage(),
                    'code'     => $e->getCode(),
                    'previous' => $e->getPrevious(),
                ],
                \sprintf(
                    '%s::%s() failed to throw Runtime exception based on previous exception.',
                    \get_class($this->psr7Stream),
                    'seek'
                )
            );
        }
    }

    /**
     * @covers ::seek
     * @depends testConstruct
     */
    public function testSeek__error()
    {
        try {
            $this->psr7Stream->seek('x', 'y');
            $this->fail('Error expected');
        } catch (RuntimeException $e) {
            static::assertInstanceOf(
                Error::class,
                $e->getPrevious(),
                \sprintf(
                    '%s::%s() failed to return error for invalid type.',
                    \get_class($this->psr7Stream),
                    'seek'
                )
            );

            static::assertEquals(
                [
                    'message' => $e->getPrevious()->getMessage(),
                    'code'    => $e->getPrevious()->getCode(),
                ],
                [
                    'message' => $e->getMessage(),
                    'code'    => $e->getCode(),
                ],
                \sprintf(
                    '%s::%s() failed to throw Runtime exception based on previous error.',
                    \get_class($this->psr7Stream),
                    'seek'
                )
            );
        }
    }

    /**
     * @covers ::rewind
     * @depends testConstruct
     */
    public function testRewind__success()
    {
        $this->streamProphecy->rewind()->shouldBeCalled();
        $this->psr7Stream->rewind();
    }

    /**
     * @covers ::rewind
     * @depends testConstruct
     */
    public function testRewind__exception()
    {
        $originalException = new Exception(
            'expception-message-'.\time(),
            \random_int(1000, 2000)
        );

        $this->streamProphecy->rewind()->shouldBeCalled()->willThrow($originalException);

        try {
            $this->psr7Stream->rewind();
            $this->fail('Exception expected');
        } catch (RuntimeException $e) {
            static::assertEquals(
                [
                    'message'  => $originalException->getMessage(),
                    'code'     => $originalException->getCode(),
                    'previous' => $originalException,
                ],
                [
                    'message'  => $e->getMessage(),
                    'code'     => $e->getCode(),
                    'previous' => $e->getPrevious(),
                ],
                \sprintf(
                    '%s::%s() failed to throw Runtime exception based on previous exception.',
                    \get_class($this->psr7Stream),
                    'rewind'
                )
            );
        }
    }

    /**
     * @covers ::isWritable
     * @depends testConstruct
     */
    public function testIsWritable()
    {
        $count = 0;
        foreach ([true, false] as $result) {
            ++$count;
            $this->streamProphecy->isWritable()->shouldBeCalledTimes($count)->willReturn($result);
            static::assertSame(
                $result,
                $this->psr7Stream->isWritable(),
                \sprintf(
                    '%s::%s() failed to return expected result.',
                    \get_class($this->psr7Stream),
                    'isWritable'
                )
            );
        }
    }

    /**
     * @covers ::write
     * @depends testConstruct
     */
    public function testWrite__success()
    {
        $string = \random_int(1000, 9999);
        $return = \random_int(1, 4);

        $this->streamProphecy->write((string) $string)->shouldBeCalled()->willReturn($return);
        static::assertSame(
            $return,
            $this->psr7Stream->write($string),
            \sprintf(
                '%s::%s() failed to return expected result.',
                \get_class($this->psr7Stream),
                'write'
            )
        );

        $string = 'some -'.\random_int(1000, 9999).'  - string to write -'.\microtime();
        $return = \random_int(10, 20);

        $this->streamProphecy->write($string)->shouldBeCalled()->willReturn($return);
        static::assertSame(
            $return,
            $this->psr7Stream->write($string),
            \sprintf(
                '%s::%s() failed to return expected result.',
                \get_class($this->psr7Stream),
                'write'
            )
        );
    }

    /**
     * @covers ::seek
     * @depends testConstruct
     */
    public function testWrite__exception()
    {
        $string = 'some -'.\random_int(1000, 9999).'  - string to write -'.\microtime();

        $originalException = new Exception(
            'expception-message-'.\time(),
            \random_int(1000, 2000)
        );

        $this->streamProphecy->write($string)->shouldBeCalled()->willThrow($originalException);

        try {
            $this->psr7Stream->write($string);
            $this->fail('Exception expected');
        } catch (RuntimeException $e) {
            static::assertEquals(
                [
                    'message'  => $originalException->getMessage(),
                    'code'     => $originalException->getCode(),
                    'previous' => $originalException,
                ],
                [
                    'message'  => $e->getMessage(),
                    'code'     => $e->getCode(),
                    'previous' => $e->getPrevious(),
                ],
                \sprintf(
                    '%s::%s() failed to throw Runtime exception based on previous exception.',
                    \get_class($this->psr7Stream),
                    'write'
                )
            );
        }
    }

    /**
     * @covers ::write
     * @depends testConstruct
     */
    public function testWrite__error()
    {
        try {
            $this->psr7Stream->write([]);
            $this->fail('Error expected');
        } catch (RuntimeException $e) {
            static::assertInstanceOf(
                Error::class,
                $e->getPrevious(),
                \sprintf(
                    '%s::%s() failed to return error for invalid type.',
                    \get_class($this->psr7Stream),
                    'write'
                )
            );

            static::assertEquals(
                [
                    'message' => $e->getPrevious()->getMessage(),
                    'code'    => $e->getPrevious()->getCode(),
                ],
                [
                    'message' => $e->getMessage(),
                    'code'    => $e->getCode(),
                ],
                \sprintf(
                    '%s::%s() failed to throw Runtime exception based on previous error.',
                    \get_class($this->psr7Stream),
                    'write'
                )
            );
        }
    }

    /**
     * @covers ::isReadable
     * @depends testConstruct
     */
    public function testIsReadable()
    {
        $count = 0;
        foreach ([true, false] as $result) {
            ++$count;
            $this->streamProphecy->isReadable()->shouldBeCalledTimes($count)->willReturn($result);
            static::assertSame(
                $result,
                $this->psr7Stream->isReadable(),
                \sprintf(
                    '%s::%s() failed to return expected result.',
                    \get_class($this->psr7Stream),
                    'isReadable'
                )
            );
        }
    }

    /**
     * @covers ::read
     * @depends testConstruct
     */
    public function testRead__success()
    {
        $string = 'some-string-'.\random_int(1000, 9999);
        $len    = \mb_strlen($string) + \random_int(10, 20);

        $this->streamProphecy->read($len)->shouldBeCalled()->willReturn($string);
        $this->psr7Stream->read($len);

        static::assertSame(
            $string,
            $this->psr7Stream->read((string) $len),
            \sprintf(
                '%s::%s() failed to return expected result.',
                \get_class($this->psr7Stream),
                'read'
            )
        );
    }

    /**
     * @covers ::read
     * @depends testConstruct
     */
    public function testRead__exception()
    {
        $len = \random_int(10, 20);

        $originalException = new Exception(
            'expception-message-'.\time(),
            \random_int(1000, 2000)
        );

        $this->streamProphecy->read($len)->shouldBeCalled()->willThrow($originalException);

        try {
            $this->psr7Stream->read($len);
            $this->fail('Exception expected');
        } catch (RuntimeException $e) {
            static::assertEquals(
                [
                    'message'  => $originalException->getMessage(),
                    'code'     => $originalException->getCode(),
                    'previous' => $originalException,
                ],
                [
                    'message'  => $e->getMessage(),
                    'code'     => $e->getCode(),
                    'previous' => $e->getPrevious(),
                ],
                \sprintf(
                    '%s::%s() failed to throw Runtime exception based on previous exception.',
                    \get_class($this->psr7Stream),
                    'read'
                )
            );
        }
    }

    /**
     * @covers ::read
     * @depends testConstruct
     */
    public function testRead__error()
    {
        try {
            $this->psr7Stream->read(3.14);
            $this->fail('Error expected');
        } catch (RuntimeException $e) {
            static::assertInstanceOf(
                Error::class,
                $e->getPrevious(),
                \sprintf(
                    '%s::%s() failed to return error for invalid type.',
                    \get_class($this->psr7Stream),
                    'read'
                )
            );

            static::assertEquals(
                [
                    'message' => $e->getPrevious()->getMessage(),
                    'code'    => $e->getPrevious()->getCode(),
                ],
                [
                    'message' => $e->getMessage(),
                    'code'    => $e->getCode(),
                ],
                \sprintf(
                    '%s::%s() failed to throw Runtime exception based on previous error.',
                    \get_class($this->psr7Stream),
                    'read'
                )
            );
        }
    }

    /**
     * @covers ::tell
     * @depends testConstruct
     */
    public function testTell__success()
    {
        $count = 0;
        foreach ([\random_int(10, 20), null, \random_int(100, 200)] as $result) {
            ++$count;
            $this->streamProphecy->tell()->shouldBeCalled()->willReturn($result);
            static::assertSame(
                $result,
                $this->psr7Stream->tell(),
                \sprintf(
                    '%s::%s() failed to return expected result.',
                    \get_class($this->psr7Stream),
                    'tell'
                )
            );
        }
    }

    /**
     * @covers ::tell
     * @depends testConstruct
     */
    public function testTell__exception()
    {
        $originalException = new Exception(
            'expception-message-'.\time(),
            \random_int(1000, 2000)
        );

        $this->streamProphecy->tell()->shouldBeCalled()->willThrow($originalException);

        try {
            $this->psr7Stream->tell();
            $this->fail('Exception expected');
        } catch (RuntimeException $e) {
            static::assertEquals(
                [
                    'message'  => $originalException->getMessage(),
                    'code'     => $originalException->getCode(),
                    'previous' => $originalException,
                ],
                [
                    'message'  => $e->getMessage(),
                    'code'     => $e->getCode(),
                    'previous' => $e->getPrevious(),
                ],
                \sprintf(
                    '%s::%s() failed to throw Runtime exception based on previous exception.',
                    \get_class($this->psr7Stream),
                    'tell'
                )
            );
        }
    }

    /**
     * @covers ::getSize
     * @depends testConstruct
     */
    public function testGetSize()
    {
        $count = 0;
        foreach ([\random_int(100, 200), \random_int(1000, 2000)] as $result) {
            ++$count;
            $this->streamProphecy->getSize()->shouldBeCalledTimes($count)->willReturn($result);
            static::assertSame(
                $result,
                $this->psr7Stream->getSize(),
                \sprintf(
                    '%s::%s() failed to return expected result.',
                    \get_class($this->psr7Stream),
                    'getSize'
                )
            );
        }
    }

    /**
     * @covers ::getContents
     * @depends testConstruct
     */
    public function testGetContents__success()
    {
        $count = 0;
        foreach (['string-'.\microtime(true), 'string-'.\microtime(false)] as $result) {
            ++$count;
            $this->streamProphecy->getContents()->shouldBeCalled()->willReturn($result);
            static::assertSame(
                $result,
                $this->psr7Stream->getContents(),
                \sprintf(
                    '%s::%s() failed to return expected result.',
                    \get_class($this->psr7Stream),
                    'getContents'
                )
            );
        }
    }

    /**
     * @covers ::getContents
     * @depends testConstruct
     */
    public function testGetContents__exception()
    {
        $originalException = new Exception(
            'expception-message-'.\time(),
            \random_int(1000, 2000)
        );

        $this->streamProphecy->getContents()->shouldBeCalled()->willThrow($originalException);

        try {
            $this->psr7Stream->getContents();
            $this->fail('Exception expected');
        } catch (RuntimeException $e) {
            static::assertEquals(
                [
                    'message'  => $originalException->getMessage(),
                    'code'     => $originalException->getCode(),
                    'previous' => $originalException,
                ],
                [
                    'message'  => $e->getMessage(),
                    'code'     => $e->getCode(),
                    'previous' => $e->getPrevious(),
                ],
                \sprintf(
                    '%s::%s() failed to throw Runtime exception based on previous exception.',
                    \get_class($this->psr7Stream),
                    'getContents'
                )
            );
        }
    }

    /**
     * @covers ::__toString
     * @depends testConstruct
     */
    public function testToString()
    {
        foreach (['string-'.\microtime(true), 'string-'.\microtime(false)] as $result) {
            $this->streamProphecy->__toString()->shouldBeCalled()->willReturn($result);
            static::assertSame(
                $result,
                (string) $this->psr7Stream,
                \sprintf(
                    '%s::%s() failed to return expected result.',
                    \get_class($this->psr7Stream),
                    '__toString'
                )
            );
        }
    }
}
