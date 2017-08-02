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

/**
 * Abstract implementation of stream.
 *
 * Implementation of methods that can fully be implemented using other
 * interface defined methods.
 */
abstract class AbstractStream implements StreamInterface
{
    /**
     * {@inheritdoc}
     */
    public function getMetadataKey(string $key)
    {
        $data = $this->getMetadata();

        return \is_array($data) && \array_key_exists($key, $data)
            ? $data[$key]
            : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatKey(string $key)
    {
        $data = $this->getStat();

        return \is_array($data) && \array_key_exists($key, $data)
            ? $data[$key]
            : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getMode(): ?string
    {
        return $this->getMetadataKey('mode');
    }

    /**
     * {@inheritdoc}
     */
    public function getUri(): ?string
    {
        return $this->getMetadataKey('uri');
    }

    /**
     * {@inheritdoc}
     */
    public function isBlocking(): bool
    {
        return \filter_var($this->getMetadataKey('blocked'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * {@inheritdoc}
     */
    public function isRemote(): bool
    {
        return $this->isOpen() && !$this->isLocal();
    }

    /**
     * {@inheritdoc}
     */
    public function lockExclusive(bool $nonBlocking = false, &$wouldBlock = null): void
    {
        $this->lock(LOCK_EX | ($nonBlocking ? LOCK_NB : 0), $wouldBlock);
    }

    /**
     * {@inheritdoc}
     */
    public function lockShared(bool $nonBlocking = false, &$wouldBlock = null): void
    {
        $this->lock(LOCK_SH | ($nonBlocking ? LOCK_NB : 0), $wouldBlock);
    }

    /**
     * {@inheritdoc}
     */
    public function unlock(bool $nonBlocking = false, &$wouldBlock = null): void
    {
        $this->lock(LOCK_UN | ($nonBlocking ? LOCK_NB : 0), $wouldBlock);
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable(): bool
    {
        return (bool) $this->getMetadataKey('seekable');
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->seek(0, SEEK_SET);
    }

    /**
     * {@inheritdoc}
     */
    public function fastForward(): void
    {
        $this->seek(0, SEEK_END);
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable(): bool
    {
        $mode = $this->getMode();

        return \is_string($mode) ? \preg_match('/[r|+]/', $mode) === 1 : false;
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable(): bool
    {
        $mode = $this->getMode();

        return \is_string($mode) ? \preg_match('/[w|a|x|c|+]/', $mode) === 1 : false;
    }

    /**
     * {@inheritdoc}
     */
    public function writeLine(string $string, string $newLine = PHP_EOL): int
    {
        return $this->write(\sprintf('%s%s', $string, $newLine));
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): ?int
    {
        return $this->getStatKey('size');
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        try {
            $position = $this->tell();
            $this->seek(0, SEEK_SET);
            $data = $this->getContents();
            $this->seek($position, SEEK_SET);
        } catch (\Exception $e) {
            return '';
        }

        return $data;
    }
}
