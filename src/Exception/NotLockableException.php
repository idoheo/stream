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

class NotLockableException extends LogicException
{
    public function __construct(StreamInterface $stream, int $code = 0, Exception $previous = null)
    {
        $message = !$stream->isOpen() ? 'Closed stream is not lockable.' : \sprintf(
            '%s is not lockable (type: %s; mode: %s).',
            null === $stream->getUri() ? 'Stream' : \sprintf('Stream "%s"', $stream->getUri()),
            $stream->getType(),
            $stream->getMode()
        );

        parent::__construct($message, $code, $previous);
    }
}
