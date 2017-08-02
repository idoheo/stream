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
use LengthException;
use LogicException;
use RuntimeException;

/**
 * Describes a data stream.
 *
 * Typically, an instance will wrap a PHP stream; this interface provides
 * a wrapper around the most common operations, including serialization of
 * the entire stream to a string.
 */
interface StreamInterface
{
    /**
     * Checks if stream is still open.
     *
     * If stream is not open, it is not usable.
     *
     * @return bool TRUE if stream is open, FALSE otherwise
     */
    public function isOpen(): bool;

    /**
     * Closes the stream and any underlying resources.
     * Also, if the stream was locked, it should get unlocked.
     */
    public function close(): void;

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach();

    /**
     * Get stream metadata as an associative array.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @see http://php.net/manual/en/function.stream-get-meta-data.php
     *
     * @return array returns an associative array
     */
    public function getMetadata(): array;

    /**
     * Return specific metadata key.
     *
     * @param string $key Key to return
     *
     * @return mixed|null Value from metadata key, or NULL if metadata key does not exist
     */
    public function getMetadataKey(string $key);

    /**
     * Get stream stat as an associative array.
     *
     * The keys returned are identical to the associative keys returned from PHP's
     * fstat() function.
     *
     * @see http://http://php.net/manual/en/function.fstat.php
     *
     * @return array returns an associative array
     */
    public function getStat(): array;

    /**
     * Return specific stat key.
     *
     * @param string $key Key to return
     *
     * @return mixed|null Value from stat key, or NULL if stat key does not exist
     */
    public function getStatKey(string $key);

    /**
     * Checks if stream is at the end of stream.
     *
     * @return bool|null TRUE if stream is at the end of stream, FALSE if not, NULL if stream is closed
     */
    public function eof(): bool;

    /**
     * Returns stream mode.
     *
     * @return string|null Stream mode, NULL if stream is closed
     */
    public function getMode(): ?string;

    /**
     * Returns stream URI.
     *
     * @return string|null Stream URI, NULL if stream is closed
     */
    public function getUri(): ?string;

    /**
     * Checks if stream supports locking.
     *
     * @return bool TRUE if stream supports locking, FALSE otherwise
     */
    public function isLockable(): bool;

    /**
     * Preform file locking operation.
     *
     * If Runtime exception is thrown, if operation would block then exception
     * code is set to static::LOCKING_WOULD_BLOCK, else it is set to
     * LOCKING_WOULD_NOT_BLOCK.
     *
     * @param int  $lock       Lock flag (LOCK_* constants)
     * @param bool $wouldBlock if passed, will be set to TRUE if operation would block, FALSE otherwise
     *
     * @throws LogicException   if called on non-open stream
     * @throws RuntimeException on failure
     */
    public function lock(int $lock, &$wouldBlock = null): void;

    /**
     * Preform file locking operation for reading.
     *
     * Locks using LOCK_SH flag
     *
     * @param bool $nonBlocking TRUE if lock operation should not block, FALSE otherwise (default: FALSE)
     * @param bool $wouldBlock  if passed, will be set to TRUE if operation would block, FALSE otherwise
     *
     * @throws LogicException   if called on non-open stream
     * @throws RuntimeException on failure
     */
    public function lockExclusive(bool $nonBlocking = false, &$wouldBlock = null): void;

    /**
     * Preform file locking operation for writing.
     *
     * Locks using LOCK_EX flag
     *
     * @param bool $nonBlocking TRUE if lock operation should not block, FALSE otherwise (default: FALSE)
     * @param bool $wouldBlock  if passed, will be set to TRUE if operation would block, FALSE otherwise
     *
     * @throws LogicException   if called on non-open stream
     * @throws RuntimeException on failure
     */
    public function lockShared(bool $nonBlocking = false, &$wouldBlock = null): void;

    /**
     * Preform file locking operation (unlocking).
     *
     * Locks using LOCK_UN flag
     *
     * @param bool $nonBlocking TRUE if lock operation should not block, FALSE otherwise (default: FALSE)
     * @param bool $wouldBlock  if passed, will be set to TRUE if operation would block, FALSE otherwise
     *
     * @throws LogicException   if called on non-open stream
     * @throws RuntimeException on failure
     */
    public function unlock(bool $nonBlocking = false, &$wouldBlock = null): void;

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool TRUE if stream is seekable, FALSE if not
     */
    public function isSeekable(): bool;

    /**
     * Seek to a position in the stream.
     *
     * @see http://www.php.net/manual/en/function.fseek.php
     *
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *                    based on the seek offset. Valid values are identical to the built-in
     *                    PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *                    offset bytes SEEK_CUR: Set position to current location plus offset
     *                    SEEK_END: Set position to end-of-stream plus offset.
     *
     * @throws DomainException  if $whence is not one of SEEK_* constants
     * @throws LogicException   if called on non-seekable stream
     * @throws RuntimeException on failure
     */
    public function seek(int $offset, int $whence = SEEK_SET): void;

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0, SEEK_SET).
     *
     * @see seek()
     * @see http://www.php.net/manual/en/function.fseek.php
     *
     * @throws LogicException   if called on non-seekable stream
     * @throws RuntimeException on failure
     */
    public function rewind(): void;

    /**
     * Seek to the end of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0, SEEK_END).
     *
     * @see seek()
     * @see http://www.php.net/manual/en/function.fseek.php
     *
     * @throws LogicException   if called on non-seekable stream
     * @throws RuntimeException on failure
     */
    public function fastForward(): void;

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool TRUE is stream is writable, FALSE if not
     */
    public function isWritable(): bool;

    /**
     * Write data to the stream.
     *
     * @param string $string the string that is to be written
     *
     * @throws RuntimeException on failure
     * @throws LogicException   if called on non-writable stream
     *
     * @return int returns the number of bytes written to the stream
     */
    public function write(string $string): int;

    /**
     * Write data to the stream, ending with new line character.
     *
     * @param string $string  the string that is to be written
     * @param string $newLine new line character to use
     *
     * @throws RuntimeException on failure
     * @throws LogicException   if called on non-writable stream
     *
     * @return int returns the number of bytes written to the stream
     */
    public function writeLine(string $string, string $newLine = PHP_EOL): int;

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool TRUE is stream is readable, FALSE if not
     */
    public function isReadable(): bool;

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *                    them. Fewer than $length bytes may be returned if underlying stream
     *                    call returns fewer bytes.
     *
     * @throws DomainException  if length is set to a negative value
     * @throws LogicException   if called on non-readable stream
     * @throws RuntimeException if an error occurs
     *
     * @return string returns the data read from the stream, or an empty string
     *                if no bytes are available
     */
    public function read(int $length): string;

    /**
     * Reads line from the stream.
     *
     * @param int $length Reading ends when length bytes have been read, or a newline (which is included in the
     *                    return value), or an EOF (whichever comes first). If specified length is 0 (default), it will
     *                    keep reading from the stream until it reaches the end of the line.
     *
     * @throws DomainException  if length is set to a negative value
     * @throws LogicException   if called on non-readable stream
     * @throws RuntimeException if an error occurs (including EOF)
     *
     * @return string returns the data read from the stream
     */
    public function readLine(int $length = 0): string;

    /**
     * Checks if stream is blocked (in blocking mode).
     *
     * @return bool TRUE if in blocking mode, FALSE otherwise
     */
    public function isBlocking(): bool;

    /**
     * Set blocking/non-blocking mode on a stream.
     *
     * @param bool $blocking If mode is FALSE, the given stream will be switched to non-blocking mode,
     *                       and if TRUE, it will be switched to blocking mode. This affects calls
     *                       that read from the stream. In non-blocking mode a read call will always
     *                       return right away while in blocking mode it will wait for data to become
     *                       available on the stream.
     *
     * @throws RuntimeException on failure
     */
    public function setBlocking(bool $blocking): void;

    /**
     * Checks if stream is local.
     *
     * Note that this returns false if stream is not open.
     * To check that stream is remote use ::isRemote() method.
     *
     * @return bool TRUE if stream is local, FALSE otherwise
     */
    public function isLocal(): bool;

    /**
     * Checks if stream is remote.
     *
     * Note that this returns false if stream is not open.
     * To check that stream is local use ::isLocal() method.
     *
     * @return bool TRUE if stream is remote, FALSE otherwise
     */
    public function isRemote(): bool;

    /**
     * Returns the current position of the file read/write pointer.
     *
     * @throws RuntimeException on error
     *
     * @return int Position of the file pointer, NULL for closed stream
     */
    public function tell(): ?int;

    /**
     * Get the size of the stream if known.
     *
     * @return int|null returns the size in bytes if known, or null if unknown
     */
    public function getSize(): ?int;

    /**
     * Truncates a stream to a given length.
     *
     * If size is larger than the file then the file is extended with null bytes.
     * If size is smaller than the file then the file is truncated to that size.
     *
     * @param int $length the size to truncate to
     *
     * @throws DomainException  if $length is less then 0
     * @throws LogicException   if stream is not writable
     * @throws RuntimeException on failure
     */
    public function truncate(int $length): void;

    /**
     * Format line as CSV and write to stream.
     *
     * @param array  $fields     an array of values
     * @param string $delimiter  the optional delimiter  parameter sets the field delimiter  (one character only)
     * @param string $enclosure  the optional enclosure  parameter sets the field enclosure  (one character only)
     * @param string $escapeChar the optional escapeChar parameter sets the escape character (one character only)
     *
     * @see http://php.net/manual/en/function.fputcsv.php
     *
     * @throws LogicException   if stream is not writable or all optional character parameters are note unique
     * @throws LengthException  if any of optional character parameters is not exactly one character
     * @throws RuntimeException on failure
     *
     * @return int The length of the written string
     */
    public function writeCsv(array $fields, string $delimiter = ',', string $enclosure = '"', string $escapeChar = '\\'): int;

    /**
     * Gets line from stream and parse for CSV fields.
     *
     * @param int    $length     Must be greater than the longest line (in characters) to be found in the CSV file
     *                           (allowing for trailing line-end characters). Otherwise the line is split in chunks
     *                           of length characters, unless the split would occur inside an enclosure.
     * @param string $delimiter  the optional delimiter  parameter sets the field delimiter  (one character only)
     * @param string $enclosure  the optional enclosure  parameter sets the field enclosure  (one character only)
     * @param string $escapeChar the optional escapeChar parameter sets the escape character (one character only)
     *
     * @see http://php.net/manual/en/function.fputcsv.php
     *
     * @throws DomainException  if $length is less then 0
     * @throws LogicException   if stream is not readable or all optional character parameters are note unique
     * @throws LengthException  if any of optional character parameters is not exactly one character
     * @throws RuntimeException on failure
     *
     * @return array an indexed array containing the fields read
     */
    public function readCsv(int $length = 0, string $delimiter = ',', string $enclosure = '"', string $escapeChar = '\\'): array;

    /**
     * Returns the remaining contents in a string.
     *
     * @throws RuntimeException if unable to read or an error occurs while
     *                          reading
     * @throws LogicException   if called on non-readable stream
     *
     * @return string
     */
    public function getContents(): string;

    /**
     * Copies contents of current stream to another stream.
     *
     * Note that copy will start from current streams pointer, and at target
     * streams pointer. Adjust (move) pointers if needed.
     *
     * <code>
     *   // Copying full content of source stream to the end of target stream
     *
     *   $sourceStream->rewind();                    // Position source
     *   $targetStream->fastForward();               // Position target
     *   $sourceStream->copyToStream($targetStream); // Copy
     * </code>
     *
     * @param StreamInterface $targetStream Target stream to copy to
     * @param int|null        $maxLength    Maximum bytes to copy
     * @param int             $chunkSize    Chunk size for read->write operations
     *
     * @throws LogicException   if source is not readable or target writable
     * @throws DomainException  if $chunkSize is 0 or less
     * @throws RuntimeException on failure
     *
     * @return int Total number of bytes copied
     */
    public function copyToStream(StreamInterface $targetStream, int $maxLength = null, int $chunkSize = 1024): int;

    /**
     * Output all remaining data in the stream.
     *
     * @throws LogicException   if stream is not readable
     * @throws RuntimeException on failure
     *
     * @return int number of characters read from stream and passed through to the output
     */
    public function output(): int;

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * This method MUST NOT raise an exception in order to conform with PHP's
     * string casting operations.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     *
     * @return string
     */
    public function __toString(): string;
}
