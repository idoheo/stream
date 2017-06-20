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

use DomainException;
use InvalidArgumentException;
use LengthException;
use LogicException;
use RuntimeException;

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
        if (!(\is_resource($stream) && \get_resource_type($stream) === 'stream')) {
            throw new InvalidArgumentException(
                'Expecting stream resource in constructor.'
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
        return \is_resource($this->stream) && \get_resource_type($this->stream) === 'stream';
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
    public function lock(int $lock): void
    {
        if (!$this->isLockable()) {
            throw new LogicException(
                'Stream is not lockable.'
            );
        }

        if (!\flock($this->stream, $lock, $wouldBlock)) {
            throw new RuntimeException(
                \sprintf(
                    'Failed performing lock operation (%d).',
                    $lock
                ),
                $wouldBlock ? static::LOCKING_WOULD_BLOCK : static::LOCKING_WOULD_NOT_BLOCK
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
            throw new LogicException(
                'Stream is not seekable.'
            );
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
            throw new LogicException(
                'Stream is not writable.'
            );
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
            throw new LogicException(
                'Stream is not readable.'
            );
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
            throw new LogicException(
                'Stream is not readable.'
            );
        }

        if (false === $read = $length === 0 ? @\fgets($this->stream) : @\stream_get_line($this->stream, $length)) {
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
            throw new LogicException(
                'Stream is not writable.'
            );
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
            throw new LogicException(
                'Stream is not writable.'
            );
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
            throw new LogicException(
                'Stream is not readable.'
            );
        }

        if (false === $result = \fgetcsv($this->stream, $length, $delimiter, $enclosure, $escapeChar)) {
            throw new RuntimeException(
                'Failed reading CSV from stream.'
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents(): string
    {
        if (!$this->isReadable()) {
            throw new LogicException(
                'Stream is not readable.'
            );
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
    public function output(): int
    {
        if (!$this->isReadable()) {
            throw new LogicException(
                'Stream is not readable.'
            );
        }

        if (false === $result = @\fpassthru($this->stream)) {
            throw new RuntimeException(
                'Failed reading from stream.'
            );
        }

        return $result;
    }
}
