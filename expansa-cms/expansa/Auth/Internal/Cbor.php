<?php

declare(strict_types=1);

namespace Expansa\Auth\Internal;

use Expansa\Auth\Exceptions\InvalidCredential;

/**
 * CBOR decoder (RFC 8949) for the subset authenticators emit: integers, byte and text strings,
 * arrays, maps, tags, booleans and null, definite lengths only. Byte strings stay raw binary.
 *
 * @internal
 */
final class Cbor
{
    /**
     * Nesting limit: WebAuthn structures are two or three levels deep.
     */
    private const int MAX_DEPTH = 16;

    /**
     * Read position in the data.
     */
    private int $offset = 0;

    private function __construct(

        /**
         * Encoded data.
         */
        private readonly string $data,
    ) {}

    /**
     * Decode data that holds exactly one item.
     *
     * @param string $data
     * @return mixed
     * @throws InvalidCredential If the data is malformed or has trailing bytes.
     */
    public static function decode(string $data): mixed
    {
        $cbor = new self($data);
        $item = $cbor->item();

        if ($cbor->offset !== strlen($data)) {
            throw new InvalidCredential('CBOR data has trailing bytes.');
        }

        return $item;
    }

    /**
     * Decode the first item of the data; the credential public key in authenticator data
     * is followed by extensions, so its length is only known after decoding.
     *
     * @param string $data
     * @param int    $length Receives the number of bytes the item took.
     * @return mixed
     * @throws InvalidCredential If the data is malformed.
     */
    public static function decodeFirst(string $data, ?int &$length = null): mixed
    {
        $cbor   = new self($data);
        $item   = $cbor->item();
        $length = $cbor->offset;

        return $item;
    }

    /**
     * Decode the item at the current offset.
     *
     * @param int $depth Nesting level of the item.
     * @return mixed
     */
    private function item(int $depth = 0): mixed
    {
        if ($depth > self::MAX_DEPTH) {
            throw new InvalidCredential('CBOR data is nested too deep.');
        }

        $byte  = ord($this->read(1));
        $major = $byte >> 5;
        $info  = $byte & 0x1f;

        if ($major === 7) {
            return match ($info) {
                20      => false,
                21      => true,
                22, 23  => null,
                default => throw new InvalidCredential('Unsupported CBOR simple value or float.'),
            };
        }

        $argument = $this->argument($info);

        switch ($major) {
            case 0:
                return $argument;
            case 1:
                return -1 - $argument;
            case 2:
            case 3:
                return $this->read($argument);
            case 4:
                // every item takes at least one byte, so a count beyond the data is malformed
                $this->assertAvailable($argument);

                $items = [];
                for ($i = 0; $i < $argument; $i++) {
                    $items[] = $this->item($depth + 1);
                }

                return $items;
            case 5:
                $this->assertAvailable($argument * 2);

                $map = [];
                for ($i = 0; $i < $argument; $i++) {
                    $key = $this->item($depth + 1);
                    if (! is_int($key) && ! is_string($key)) {
                        throw new InvalidCredential('Unsupported CBOR map key.');
                    }

                    $map[$key] = $this->item($depth + 1);
                }

                return $map;
            default:
                // tag: its meaning isn't needed, the tagged item is
                return $this->item($depth + 1);
        }
    }

    /**
     * Read the argument that follows the initial byte: a value, length or count.
     *
     * @param int $info Low five bits of the initial byte.
     * @return int
     */
    private function argument(int $info): int
    {
        $value = match ($info) {
            24      => ord($this->read(1)),
            25      => unpack('n', $this->read(2))[1],
            26      => unpack('N', $this->read(4))[1],
            27      => unpack('J', $this->read(8))[1],
            28, 29, 30, 31 => throw new InvalidCredential('Indefinite or reserved CBOR lengths are not supported.'),
            default => $info,
        };

        // 64-bit values above PHP_INT_MAX unpack as negative
        if ($value < 0) {
            throw new InvalidCredential('CBOR integer is out of range.');
        }

        return $value;
    }

    /**
     * Take bytes from the current offset.
     *
     * @param int $length
     * @return string
     */
    private function read(int $length): string
    {
        $this->assertAvailable($length);

        $bytes         = substr($this->data, $this->offset, $length);
        $this->offset += $length;

        return $bytes;
    }

    /**
     * Fail when fewer bytes are left than needed.
     *
     * @param int $length
     * @return void
     */
    private function assertAvailable(int $length): void
    {
        if ($length > strlen($this->data) - $this->offset) {
            throw new InvalidCredential('CBOR data is truncated.');
        }
    }
}
