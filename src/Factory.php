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

use DomainException;
use InvalidArgumentException;
use RuntimeException;

/**
 * Factory to easily crate Stream instances.
 */
class Factory
{
    /**
     * Creates stream factory.
     *
     * @return Factory
     */
    public static function createFactory(): Factory
    {
        return new static();
    }

    /**
     * Creates stream object from valid stream resource.
     *
     * @param string $resource Stream resource
     *
     * @throws InvalidArgumentException when not a stream resource provided
     *
     * @return StreamInterface
     */
    public function forResource($resource): StreamInterface
    {
        if (!\is_resource($resource) || \get_resource_type($resource) !== 'stream') {
            throw new InvalidArgumentException(
                'Expecting stream resource to build Stream object.'
            );
        }

        return new Stream($resource);
    }

    /**
     * Crates stream object for temp file
     * This file will be deleted once resource is closed,
     * or PHP script ends.
     *
     * @return StreamInterface
     */
    public function forTempFile(): StreamInterface
    {
        return $this->forResource(\tmpfile());
    }

    /**
     * Creates stream object by providing file name
     * and mode.
     *
     * @see http://php.net/manual/en/function.fopen.php
     *
     * @param string $fileName Filename
     * @param string $mode     Mode
     *
     * @throws RuntimeException on failure
     *
     * @return StreamInterface
     */
    public function forFileName(string $fileName, string $mode): StreamInterface
    {
        $resource = @\fopen($fileName, $mode);
        if (!\is_resource($resource)) {
            throw new RuntimeException(
                \sprintf(
                    'Failed opening stream resource for %s in %s mode.',
                    \var_export($fileName, true),
                    \var_export($mode, true)
                )
            );
        }

        return $this->forResource($resource);
    }

    /**
     * Creates stream object for STDIN.
     *
     * @return StreamInterface
     */
    public function forStdin()
    {
        return $this->forFileName('php://stdin', 'rb');
    }

    /**
     * Creates stream object for STDOUT.
     *
     * @return StreamInterface
     */
    public function forStdout()
    {
        return $this->forFileName('php://stdout', 'wb');
    }

    /**
     * Creates stream object for STDERR.
     *
     * @return StreamInterface
     */
    public function forStderr()
    {
        return $this->forFileName('php://stderr', 'wb');
    }

    /**
     * Creates stream object for PHP input.
     *
     * @return StreamInterface
     */
    public function forInput()
    {
        return $this->forFileName('php://input', 'rb');
    }

    /**
     * Creates stream object for PHP output.
     *
     * @return StreamInterface
     */
    public function forOutput()
    {
        return $this->forFileName('php://output', 'wb');
    }

    /**
     * Creates stream object for temporary storage in
     * memory. If limit has been specified, and stream
     * size exceeds it, data will be moved to disk.
     *
     * This stream contents get deleted when stream gets
     * closed or PHP script ends
     *
     * @param int $limit limit before moving from memory to disk
     *
     * @return StreamInterface
     */
    public function forMemory(int $limit = null)
    {
        if ($limit < 0) {
            throw new DomainException(
                \sprintf(
                    'Can not set stream memory limit to less then 0 (got %d).',
                    $limit
                )
            );
        }

        return $this->forFileName(
            $limit === null
                ? 'php://memory'
                : \sprintf(
                    'php://temp%s',
                    $limit > 0
                        ? \sprintf('/maxmemory:%d', $limit)
                        : null
                ),
            'w+b'
        );
    }
}
