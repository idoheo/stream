<?php

declare(strict_types=1);

/*
 * This file is part of Idoheo Stream package.
 * (c) 2017 Repository contributors
 *
 * This source file is subject to the MIT license.
 * Copy of license is located with this source code in the file LICENSE.
 */

namespace Idoheo\Stream;

use Idoheo\Stream\Exception\DomainException;
use Idoheo\Stream\Exception\InvalidArgumentException;
use Idoheo\Stream\Exception\LengthException;
use Idoheo\Stream\Exception\LogicException;
use Idoheo\Stream\Exception\NotLockableException;
use Idoheo\Stream\Exception\NotReadableException;
use Idoheo\Stream\Exception\NotSeekableException;
use Idoheo\Stream\Exception\NotWritableException;
use Idoheo\Stream\Exception\RuntimeException;

/**
 * Class Stream.
 */
class Stream extends AbstractStream
{
    /**
     * @var resource|null
     */
    protected $stream;

    /**
     * Stream constructor.
     *
     * @param resource $stream Stream resource to manage
     */
    public function __construct($stream)
    {
        $metadata = \is_resource($stream) ? @\stream_get_meta_data($stream) : null;

        if (!(\is_array($metadata) && \array_key_exists('stream_type', $metadata))) {
            throw new InvalidArgumentException(
                'Expecting a stream resource.'
            );
        }

        $this->stream = $stream;
    }

    /**
     * Stream destructor.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * {@inheritdoc}
     */
    public function isOpen(): bool
    {
        return \is_resource($this->stream) && (\strcasecmp(\get_resource_type($this->stream), 'Unknown') !== 0);
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        if ($this->isOpen()) {
            // If stream is getting closed, unlock locked resource
            // otherwise other parts of a script (or other scripts)
            // might fail,

            if ($this->isLockable()) {
                $this->unlock(true);
            }

            @\fclose($this->stream);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        list($return, $this->stream) = [$this->isOpen() ? $this->stream : null, null];

        return $return;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata(): array
    {
        return $this->isOpen() ? \stream_get_meta_data($this->stream) : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getStat(): array
    {
        if ($this->getMetadataKey('wrapper_type') === 'plainfile') {
            \clearstatcache(true, $this->getMetadataKey('uri'));
        }

        return $this->isOpen()
            ? \array_filter(\fstat($this->stream), function ($key) {
                return \is_string($key);
            }, ARRAY_FILTER_USE_KEY)
            : [];
    }

    /**
     * {@inheritdoc}
     */
    public function isLockable(): bool
    {
        return $this->isOpen() ? \stream_supports_lock($this->stream) : false;
    }

    /**
     * {@inheritdoc}
     */
    public function lock(int $lock, &$wouldBlock = null): void
    {
        if (!$this->isLockable()) {
            throw new NotLockableException($this);
        }

        $success = \flock($this->stream, $lock, $wouldBlock);

        $wouldBlock = \filter_var($wouldBlock, FILTER_VALIDATE_BOOLEAN, ['flags' => FILTER_NULL_ON_FAILURE]);

        if (!$success) {
            throw new RuntimeException(
                \sprintf(
                    'Failed performing lock operation (%d).',
                    $lock
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function eof(): bool
    {
        return $this->isOpen() ? \feof($this->stream) : true;
    }

    /**
     * {@inheritdoc}
     */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!\in_array($whence, [SEEK_SET, SEEK_CUR, SEEK_END])) {
            throw new DomainException(
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
        }

        if ($this->isSeekable() !== true) {
            throw new NotSeekableException($this);
        }

        if (0 !== @\fseek($this->stream, $offset, $whence)) {
            throw new RuntimeException(
                \sprintf(
                    'Failed seeking stream (Offset: %d, Whence: %d).', $offset, $whence
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $string): int
    {
        if (!$this->isWritable()) {
            throw new NotWritableException($this);
        }

        if (false === $written = @\fwrite($this->stream, $string)) {
            throw new RuntimeException(
                'Failed writing string to stream.'
            );
        }

        return $written;
    }

    /**
     * {@inheritdoc}
     */
    public function read(int $length): string
    {
        if ($length < 0) {
            throw new DomainException(
                \sprintf(
                    'Reading length can not be less then 0. Got %d.',
                    $length
                )
            );
        }

        if (!$this->isReadable()) {
            throw new NotReadableException($this);
        }

        if (false === $read = @\fread($this->stream, $length)) {
            throw new RuntimeException(
                \sprintf(
                    'Failed reading from stream. Requested %d bytes.',
                    $length
                )
            );
        }

        return $read;
    }

    /**
     * {@inheritdoc}
     */
    public function readLine(int $length = 0): string
    {
        if ($length < 0) {
            throw new DomainException(
                \sprintf(
                    'Line reading length can not be less then 0. Got %d.',
                    $length
                )
            );
        }

        if (!$this->isReadable()) {
            throw new NotReadableException($this);
        }

        $wasEof = $this->eof();
        if (false === $read = $length === 0 ? @\fgets($this->stream) : @\fgets($this->stream, $length + 1)) {
            // If was not at EOF but failed to read, there was an empty line before end.
            if (!$wasEof && $this->eof()) {
                return '';
            }
            throw new RuntimeException(
                \sprintf(
                    'Failed reading line from stream. Requested length: %d.',
                    $length
                )
            );
        }

        return $read;
    }

    /**
     * {@inheritdoc}
     */
    public function setBlocking(bool $blocking): void
    {
        if (false === ($this->isOpen() ? @\stream_set_blocking($this->stream, $blocking) : false)) {
            throw new RuntimeException(
                'Failed setting blocking mode on a stream.'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isLocal(): bool
    {
        return $this->isOpen() && @\stream_is_local($this->stream);
    }

    /**
     * {@inheritdoc}
     */
    public function tell(): ?int
    {
        if (false === $result = ($this->isOpen() ? @\ftell($this->stream) : null)) {
            throw new RuntimeException(
                'Failed returning stream pointer position.'
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function truncate(int $length): void
    {
        if ($length < 0) {
            throw new DomainException(
                \sprintf(
                    'Can not truncate stream to less then 0. Got %d.',
                    $length
                )
            );
        }

        if (!$this->isWritable()) {
            throw new NotWritableException($this);
        }

        if (false === @\ftruncate($this->stream, $length)) {
            throw new RuntimeException(
                \sprintf(
                    'Failed truncating stream to %d length.',
                    $length
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function writeCsv(array $fields, string $delimiter = ',', string $enclosure = '"', string $escapeChar = '\\'): int
    {
        if (\mb_strlen($delimiter) !== 1) {
            throw new LengthException(
                \sprintf(
                    'Delimiter character should be 1 character string. Got %s.',
                    \var_export($delimiter, true)
                )
            );
        }

        if (\mb_strlen($enclosure) !== 1) {
            throw new LengthException(
                \sprintf(
                    'Enclosure character should be 1 character string. Got %s.',
                    \var_export($enclosure, true)
                )
            );
        }

        if (\mb_strlen($escapeChar) !== 1) {
            throw new LengthException(
                \sprintf(
                    'Escape character should be 1 character string. Got %s.',
                    \var_export($escapeChar, true)
                )
            );
        }

        if (\count(\array_unique([$delimiter, $enclosure, $escapeChar])) !== 3) {
            throw new LogicException(
                \sprintf(
                    'CSV delimiter (%s), enclosure (%s) and escape character (%s) should all be unique.',
                    $delimiter, $enclosure, $escapeChar
                )
            );
        }

        if (!$this->isWritable()) {
            throw new NotWritableException($this);
        }

        if (false === $result = @\fputcsv($this->stream, $fields, $delimiter, $enclosure, $escapeChar)) {
            throw new RuntimeException(
                'Failed writing CSV to stream.'
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function readCsv(int $length = 0, string $delimiter = ',', string $enclosure = '"', string $escapeChar = '\\'): array
    {
        if ($length < 0) {
            throw new DomainException(
                \sprintf(
                    'CSV reading length can not be less then 0. Got %d.',
                    $length
                )
            );
        }

        if (\mb_strlen($delimiter) !== 1) {
            throw new LengthException(
                \sprintf(
                    'Delimiter character should be 1 character string. Got %s.',
                    \var_export($delimiter, true)
                )
            );
        }

        if (\mb_strlen($enclosure) !== 1) {
            throw new LengthException(
                \sprintf(
                    'Enclosure character should be 1 character string. Got %s.',
                    \var_export($enclosure, true)
                )
            );
        }

        if (\mb_strlen($escapeChar) !== 1) {
            throw new LengthException(
                \sprintf(
                    'Escape character should be 1 character string. Got %s.',
                    \var_export($escapeChar, true)
                )
            );
        }

        if (\count(\array_unique([$delimiter, $enclosure, $escapeChar])) !== 3) {
            throw new LogicException(
                \sprintf(
                    'CSV delimiter (%s), enclosure (%s) and escape character (%s) should all be unique.',
                    $delimiter, $enclosure, $escapeChar
                )
            );
        }

        if (!$this->isReadable()) {
            throw new NotReadableException($this);
        }

        $wasEof = $this->eof();
        if (false === $result = \fgetcsv($this->stream, $length, $delimiter, $enclosure, $escapeChar)) {
            if (!$wasEof && $this->eof()) {
                return [];
            }
            throw new RuntimeException(
                'Failed reading CSV from stream.'
            );
        }

        return $result === [null] ? [] : $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents(): string
    {
        if (!$this->isReadable()) {
            throw new NotReadableException($this);
        }

        if (false === $read = @\stream_get_contents($this->stream)) {
            throw new RuntimeException(
                'Failed reading from stream.'
            );
        }

        return $read;
    }

    /**
     * {@inheritdoc}
     */
    public function copyToStream(StreamInterface $targetStream, int $maxLength = null, int $chunkSize = 1024): int
    {
        if (!$this->isReadable()) {
            throw new NotReadableException($this);
        }

        if (!$targetStream->isWritable()) {
            throw new NotWritableException($targetStream);
        }

        if ($chunkSize < 1) {
            throw new DomainException(
                'Chunk size for stream copy can not be less then 1.'
            );
        }

        // If stream is of a same class, utilize direct access to stream property
        if ($targetStream instanceof self) {
            if (false === $res = @\stream_copy_to_stream($this->stream, $targetStream->stream, null === $maxLength ? -1 : $maxLength)) {
                throw new RuntimeException('Failed copying stream content to another stream.');
            }

            return $res;
        }

        $copied = 0;

        try {
            while (!$this->eof() && (null === $maxLength || $copied < $maxLength)) {
                $readSize = null === $maxLength ? $chunkSize : \min($chunkSize, $maxLength - $copied);
                $copied += $targetStream->write($this->read($readSize));
            }
        } catch (\Throwable $e) {
            throw new RuntimeException(
                \sprintf(
                    'Failed copying stream content to another stream: %s',
                    $e->getMessage()
                ),
                $e->getCode(),
                $e
            );
        }

        return $copied;
    }

    /**
     * {@inheritdoc}
     */
    public function output(): int
    {
        if (!$this->isReadable()) {
            throw new NotReadableException($this);
        }

        if (false === $result = @\fpassthru($this->stream)) {
            throw new RuntimeException(
                'Failed reading from stream.'
            );
        }

        return $result;
    }
}
