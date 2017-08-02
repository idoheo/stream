<?php

declare(strict_types=1);

/*
 * This file is part of Idoheo Stream package.
 * (c) 2017 Repository contributors
 *
 * This source file is subject to the MIT license.
 * Copy of license is located with this source code in the file LICENSE.
 */

namespace Idoheo\Stream\Exception;

use Exception;
use Idoheo\Stream\StreamInterface;

class NotSeekableException extends LogicException
{
    public function __construct(StreamInterface $stream, int $code = 0, Exception $previous = null)
    {
        $message = $stream->isOpen() ? 'Closed stream is not seekable.' : \sprintf(
            'Stream of "%s" type with mode "%s" is not seekable%s.',
            $stream->getType(),
            $stream->getMode(),
            null === $stream->getUri() ? '' : \sprintf(' (URI: %s)', $stream->getUri())
        );

        parent::__construct($message, $code, $previous);
    }
}
