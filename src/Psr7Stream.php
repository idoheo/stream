<?php

declare(strict_types=1);

/*
 * This file is part of Idoheo Uri Query package.
 * (c) Repository contributors
 *
 * This source file is subject to the MIT license.
 * Copy of license is located with this source code in the file LICENSE.
 */

namespace Idoheo\Stream;

use Error;
use Exception;
use Psr\Http\Message\StreamInterface as PsrStream;
use RuntimeException;

/**
 * PSR 7 stream wrapper, wrapping this package defined stream
 * into PSR 7 Stream interface.
 */
class Psr7Stream implements PsrStream
{
    /**
     * @var StreamInterface
     */
    protected $stream;

    /**
     * Psr7Stream constructor.
     *
     * @param StreamInterface $stream
     */
    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->stream->close();
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        return $this->stream->detach();
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        return null === $key ? $this->stream->getMetadata() : $this->stream->getMetadataKey((string) $key);
    }

    /**
     * {@inheritdoc}
     */
    public function eof()
    {
        return $this->stream->eof();
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable()
    {
        return $this->stream->isSeekable();
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        $_offset = \filter_var($offset, FILTER_VALIDATE_INT);
        $_whence = \filter_var($whence, FILTER_VALIDATE_INT);

        try {
            $this->stream->seek(
                \is_int($_offset) ? $_offset : $offset,
                \is_int($_whence) ? $_whence : $whence
            );
        } catch (Exception | Error $e) {
            throw new RuntimeException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        try {
            $this->stream->rewind();
        } catch (Exception | Error $e) {
            throw new RuntimeException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable()
    {
        return (bool) $this->stream->isWritable();
    }

    /**
     * {@inheritdoc}
     */
    public function write($string)
    {
        try {
            $result =  $this->stream->write(\is_scalar($string) ? (string) $string : $string);
        } catch (Exception | Error $e) {
            throw new RuntimeException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable()
    {
        return $this->stream->isReadable();
    }

    /**
     * {@inheritdoc}
     */
    public function read($length)
    {
        $_length = \filter_var($length, FILTER_VALIDATE_INT);

        try {
            $result = $this->stream->read(\is_int($_length) ? $_length : $length);
        } catch (Exception | Error $e) {
            throw new RuntimeException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function tell()
    {
        try {
            $result = $this->stream->tell();
        } catch (Exception | Error $e) {
            throw new RuntimeException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize()
    {
        return $this->stream->getSize();
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        try {
            $result = $this->stream->getContents();
        } catch (Exception | Error $e) {
            throw new RuntimeException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->stream->__toString();
    }
}
