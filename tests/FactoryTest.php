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

use DomainException;
use Idoheo\Stream\Factory;
use InvalidArgumentException;
use RuntimeException;

/**
 * @coversDefaultClass \Idoheo\Stream\Factory
 */
class FactoryTest extends TestCase
{
    /**
     * @var Factory
     */
    private $factory;

    protected function setUp()
    {
        parent::setUp();
        $this->factory = Factory::createFactory();
    }

    protected function tearDown()
    {
        $this->factory = null;
        parent::tearDown();
    }

    /**
     * @covers ::createFactory
     */
    public function testCreateFactory()
    {
        static::assertInstanceOf(
            Factory::class,
            $this->factory,
            \sprintf(
                '%s::%s() failed to create factory.',
                \get_class($this->factory),
                'createFactory'
            )
        );
    }

    /**
     * @covers ::forResource
     * @depends testCreateFactory
     */
    public function testForResource()
    {
        $resource = \tmpfile();

        static::assertSame(
            $resource,
            $this->factory->forResource($resource)->detach(),
            \sprintf(
                '%s::%s() failed creating stream for specified stream resource.',
                \get_class($this->factory),
                'forResource'
            )
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expecting stream resource to build Stream object.');
        $this->factory->forResource(null);
    }

    /**
     * @covers ::forTempFile
     * @depends testForResource
     */
    public function testForTempFile()
    {
        $stream = $this->factory->forTempFile();
        static::assertSame(
            [
                'readable' => true,
                'writable' => true,
                'seekable' => true,
            ],
            [
                'readable' => $stream->isReadable(),
                'writable' => $stream->isWritable(),
                'seekable' => $stream->isSeekable(),
            ],
            \sprintf(
                '%s::%s() failed to create stream with expected properties.',
                \get_class($this->factory),
                'forTempFile'
            )
        );

        $tmpFile  = $stream->detach();
        $metadata = \stream_get_meta_data($tmpFile);
        $filePath = $metadata['uri'];

        static::assertTrue(
            \file_exists($filePath),
            \sprintf(
                '%s::%s() failed to create temp file for stream.',
                \get_class($this->factory),
                'forTempFile'
            )
        );

        \fclose($tmpFile);

        static::assertFalse(
            \file_exists($filePath),
            \sprintf(
                '%s::%s() failed to create temp file for stream.',
                \get_class($this->factory),
                'forTempFile'
            )
        );
    }

    /**
     * @covers ::forFileName
     * @depends testForResource
     */
    public function testForFilename()
    {
        $tmp      = \tmpfile();
        $metadata = \stream_get_meta_data($tmp);
        $filePath = $metadata['uri'];

        foreach (['r', 'w'] as $mode) {
            $stream = $this->factory->forFileName($filePath, $mode);
            static::assertSame(
                [
                    'uri'  => $filePath,
                    'mode' => $mode,
                ],
                [
                    'uri'  => $stream->getUri(),
                    'mode' => $stream->getMode(),
                ],
                \sprintf(
                    '%s::%s() failed to create stream with expected properties.',
                    \get_class($this->factory),
                    'forFileName'
                )
            );
        }

        $fileName = '|Wrong|Name|'.\time().'|';
        $mode     = '|Wrong|Mode|'.\time().'|';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Failed opening stream resource for %s in %s mode.',
                \var_export($fileName, true),
                \var_export($mode, true)
            )
        );
        $this->factory->forFileName($fileName, $mode);
    }

    /**
     * @covers ::forStdin
     * @depends testForFilename
     */
    public function testForStdin()
    {
        $stream = $this->factory->forStdin();
        static::assertSame(
            [
                'uri'  => 'php://stdin',
                'mode' => 'rb',
            ],
            [
                'uri'  => $stream->getUri(),
                'mode' => $stream->getMode(),
            ],
            \sprintf(
                '%s::%s() failed to create stream with expected properties.',
                \get_class($this->factory),
                'forStdin'
            )
        );
    }

    /**
     * @covers ::forStdout
     * @depends testForFilename
     */
    public function testForStdout()
    {
        $stream = $this->factory->forStdout();
        static::assertSame(
            [
                'uri'  => 'php://stdout',
                'mode' => 'wb',
            ],
            [
                'uri'  => $stream->getUri(),
                'mode' => $stream->getMode(),
            ],
            \sprintf(
                '%s::%s() failed to create stream with expected properties.',
                \get_class($this->factory),
                'forStdout'
            )
        );
    }

    /**
     * @covers ::forStderr
     * @depends testForFilename
     */
    public function testForStderr()
    {
        $stream = $this->factory->forStderr();
        static::assertSame(
            [
                'uri'  => 'php://stderr',
                'mode' => 'wb',
            ],
            [
                'uri'  => $stream->getUri(),
                'mode' => $stream->getMode(),
            ],
            \sprintf(
                '%s::%s() failed to create stream with expected properties.',
                \get_class($this->factory),
                'forStderr'
            )
        );
    }

    /**
     * @covers ::forInput
     * @depends testForFilename
     */
    public function testForInput()
    {
        $stream = $this->factory->forInput();
        static::assertSame(
            [
                'uri'  => 'php://input',
                'mode' => 'rb',
            ],
            [
                'uri'  => $stream->getUri(),
                'mode' => $stream->getMode(),
            ],
            \sprintf(
                '%s::%s() failed to create stream with expected properties.',
                \get_class($this->factory),
                'forInput'
            )
        );
    }

    /**
     * @covers ::forOutput
     * @depends testForFilename
     */
    public function testForOutput()
    {
        $stream = $this->factory->forOutput();
        static::assertSame(
            [
                'uri'  => 'php://output',
                'mode' => 'wb',
            ],
            [
                'uri'  => $stream->getUri(),
                'mode' => $stream->getMode(),
            ],
            \sprintf(
                '%s::%s() failed to create stream with expected properties.',
                \get_class($this->factory),
                'forOutput'
            )
        );
    }

    /**
     * @covers ::forMemory
     * @depends testForFilename
     */
    public function testForMemory()
    {
        $stream = $this->factory->forMemory();

        static::assertSame(
            [
                'uri'  => 'php://memory',
                'mode' => 'w+b',
            ],
            [
                'uri'  => $stream->getUri(),
                'mode' => $stream->getMode(),
            ],
            \sprintf(
                '%s::%s() failed to create stream with expected properties (no limit was specified).',
                \get_class($this->factory),
                'forMemory'
            )
        );

        $stream = $this->factory->forMemory(0);

        static::assertSame(
            [
                'uri'  => 'php://temp',
                'mode' => 'w+b',
            ],
            [
                'uri'  => $stream->getUri(),
                'mode' => $stream->getMode(),
            ],
            \sprintf(
                '%s::%s() failed to create stream with expected properties (limit was specified to 0).',
                \get_class($this->factory),
                'forMemory'
            )
        );

        $limit  = \random_int(1024, 2048);
        $stream = $this->factory->forMemory($limit);

        static::assertSame(
            [
                'uri'  => \sprintf('php://temp/maxmemory:%d', $limit),
                'mode' => 'w+b',
            ],
            [
                'uri'  => $stream->getUri(),
                'mode' => $stream->getMode(),
            ],
            \sprintf(
                '%s::%s() failed to create stream with expected properties (limit was specified to greater than 0).',
                \get_class($this->factory),
                'forMemory'
            )
        );

        $limit = -1;
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'Can not set stream memory limit to less then 0 (got %d).',
                $limit
            )
        );
        $this->factory->forMemory($limit);
    }
}
