<?php

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;

class TicketQrCode
{
    private const VERSION = 5;

    private const SIZE = 37;

    private const DATA_CODEWORDS = 108;

    private const ECC_CODEWORDS = 26;

    private const QUIET_ZONE = 4;

    private array $matrix = [];

    private array $functionModules = [];

    /**
     * Return a PNG QR code for a short public ticket URL.
     *
     * Version 5-L supports public URLs up to 106 bytes.
     */
    public function png(string $data, int $scale = 7): string
    {
        if ($data === '' || strlen($data) > 106) {
            throw new InvalidArgumentException(
                'Le contenu du QR code doit contenir entre 1 et 106 octets.'
            );
        }

        if ($scale < 1 || $scale > 16) {
            throw new InvalidArgumentException(
                'La taille des modules QR doit être comprise entre 1 et 16.'
            );
        }

        $this->matrix = array_fill(
            0,
            self::SIZE,
            array_fill(0, self::SIZE, null)
        );
        $this->functionModules = array_fill(
            0,
            self::SIZE,
            array_fill(0, self::SIZE, false)
        );

        $this->drawFunctionPatterns();
        $codewords = $this->encodeData($data);
        $this->drawCodewords($codewords);
        $this->drawFormatBits();

        return $this->renderPng($scale);
    }

    /**
     * Build the byte-mode data and its Reed-Solomon error correction bytes.
     */
    private function encodeData(string $data): array
    {
        $bits = '0100';
        $bits .= str_pad(decbin(strlen($data)), 8, '0', STR_PAD_LEFT);

        for ($index = 0, $length = strlen($data); $index < $length; $index++) {
            $bits .= str_pad(
                decbin(ord($data[$index])),
                8,
                '0',
                STR_PAD_LEFT
            );
        }

        $capacity = self::DATA_CODEWORDS * 8;
        $bits .= str_repeat('0', min(4, $capacity - strlen($bits)));
        $bits .= str_repeat('0', (8 - (strlen($bits) % 8)) % 8);

        $dataCodewords = [];

        for ($index = 0; $index < strlen($bits); $index += 8) {
            $dataCodewords[] = bindec(substr($bits, $index, 8));
        }

        $paddingBytes = [0xEC, 0x11];
        $paddingIndex = 0;

        while (count($dataCodewords) < self::DATA_CODEWORDS) {
            $dataCodewords[] = $paddingBytes[$paddingIndex % 2];
            $paddingIndex++;
        }

        $generator = $this->reedSolomonGenerator(self::ECC_CODEWORDS);
        $remainder = array_merge(
            $dataCodewords,
            array_fill(0, self::ECC_CODEWORDS, 0)
        );

        for ($index = 0; $index < self::DATA_CODEWORDS; $index++) {
            $factor = $remainder[$index];

            for ($offset = 0; $offset < count($generator); $offset++) {
                $remainder[$index + $offset] ^=
                    $this->multiplyInGaloisField($generator[$offset], $factor);
            }
        }

        return array_merge(
            $dataCodewords,
            array_slice($remainder, self::DATA_CODEWORDS)
        );
    }

    /**
     * Return a Reed-Solomon generator polynomial over GF(256).
     */
    private function reedSolomonGenerator(int $degree): array
    {
        $polynomial = [1];
        $root = 1;

        for ($index = 0; $index < $degree; $index++) {
            $next = array_fill(0, count($polynomial) + 1, 0);

            foreach ($polynomial as $offset => $coefficient) {
                $next[$offset] ^= $coefficient;
                $next[$offset + 1] ^=
                    $this->multiplyInGaloisField($coefficient, $root);
            }

            $polynomial = $next;
            $root = $this->multiplyInGaloisField($root, 2);
        }

        return $polynomial;
    }

    /**
     * Multiply two values in GF(256), using the QR Code primitive polynomial.
     */
    private function multiplyInGaloisField(int $left, int $right): int
    {
        $product = 0;

        while ($right > 0) {
            if (($right & 1) !== 0) {
                $product ^= $left;
            }

            $right >>= 1;
            $left <<= 1;

            if (($left & 0x100) !== 0) {
                $left ^= 0x11D;
            }
        }

        return $product;
    }

    /**
     * Draw finder, timing, alignment, and reserved format modules.
     */
    private function drawFunctionPatterns(): void
    {
        $this->drawFinderPattern(0, 0);
        $this->drawFinderPattern(self::SIZE - 7, 0);
        $this->drawFinderPattern(0, self::SIZE - 7);
        $this->drawAlignmentPattern(30, 30);

        for ($position = 8; $position < self::SIZE - 8; $position++) {
            $dark = ($position % 2) === 0;
            $this->setFunctionModule($position, 6, $dark);
            $this->setFunctionModule(6, $position, $dark);
        }

        $this->setFunctionModule(8, self::SIZE - 8, true);

        for ($index = 0; $index <= 5; $index++) {
            $this->setFunctionModule(8, $index, false);
        }

        $this->setFunctionModule(8, 7, false);
        $this->setFunctionModule(8, 8, false);
        $this->setFunctionModule(7, 8, false);

        for ($index = 9; $index < 15; $index++) {
            $this->setFunctionModule(14 - $index, 8, false);
        }

        for ($index = 0; $index < 8; $index++) {
            $this->setFunctionModule(self::SIZE - 1 - $index, 8, false);
        }

        for ($index = 8; $index < 15; $index++) {
            $this->setFunctionModule(8, self::SIZE - 15 + $index, false);
        }
    }

    private function drawFinderPattern(int $left, int $top): void
    {
        for ($offsetY = -1; $offsetY <= 7; $offsetY++) {
            for ($offsetX = -1; $offsetX <= 7; $offsetX++) {
                $x = $left + $offsetX;
                $y = $top + $offsetY;

                if ($x < 0 || $x >= self::SIZE || $y < 0 || $y >= self::SIZE) {
                    continue;
                }

                $distance = max(
                    abs($offsetX - 3),
                    abs($offsetY - 3)
                );
                $dark = $offsetX >= 0
                    && $offsetX <= 6
                    && $offsetY >= 0
                    && $offsetY <= 6
                    && $distance !== 2;

                $this->setFunctionModule($x, $y, $dark);
            }
        }
    }

    private function drawAlignmentPattern(int $centerX, int $centerY): void
    {
        for ($offsetY = -2; $offsetY <= 2; $offsetY++) {
            for ($offsetX = -2; $offsetX <= 2; $offsetX++) {
                $distance = max(abs($offsetX), abs($offsetY));
                $this->setFunctionModule(
                    $centerX + $offsetX,
                    $centerY + $offsetY,
                    $distance !== 1
                );
            }
        }
    }

    private function setFunctionModule(int $x, int $y, bool $dark): void
    {
        $this->matrix[$y][$x] = $dark;
        $this->functionModules[$y][$x] = true;
    }

    /**
     * Place codeword bits in the standard QR zigzag pattern with mask 0.
     */
    private function drawCodewords(array $codewords): void
    {
        $bits = '';

        foreach ($codewords as $codeword) {
            $bits .= str_pad(decbin($codeword), 8, '0', STR_PAD_LEFT);
        }

        $bitIndex = 0;

        for ($right = self::SIZE - 1; $right >= 1; $right -= 2) {
            if ($right === 6) {
                $right = 5;
            }

            $upward = ((($right + 1) & 2) === 0);

            for ($vertical = 0; $vertical < self::SIZE; $vertical++) {
                $y = $upward
                    ? self::SIZE - 1 - $vertical
                    : $vertical;

                for ($offset = 0; $offset < 2; $offset++) {
                    $x = $right - $offset;

                    if ($this->functionModules[$y][$x]) {
                        continue;
                    }

                    $dark = isset($bits[$bitIndex])
                        && $bits[$bitIndex] === '1';

                    if ((($x + $y) % 2) === 0) {
                        $dark = ! $dark;
                    }

                    $this->matrix[$y][$x] = $dark;
                    $bitIndex++;
                }
            }
        }
    }

    /**
     * Set the error-correction and mask information in both format areas.
     */
    private function drawFormatBits(): void
    {
        $formatData = (0b01 << 3) | 0;
        $remainder = $formatData << 10;

        for ($bit = 14; $bit >= 10; $bit--) {
            if ((($remainder >> $bit) & 1) !== 0) {
                $remainder ^= 0x537 << ($bit - 10);
            }
        }

        $formatBits = (($formatData << 10) | $remainder) ^ 0x5412;

        for ($index = 0; $index <= 5; $index++) {
            $this->matrix[$index][8] = (($formatBits >> $index) & 1) !== 0;
        }

        $this->matrix[7][8] = (($formatBits >> 6) & 1) !== 0;
        $this->matrix[8][8] = (($formatBits >> 7) & 1) !== 0;
        $this->matrix[8][7] = (($formatBits >> 8) & 1) !== 0;

        for ($index = 9; $index < 15; $index++) {
            $this->matrix[8][14 - $index] =
                (($formatBits >> $index) & 1) !== 0;
        }

        for ($index = 0; $index < 8; $index++) {
            $this->matrix[8][self::SIZE - 1 - $index] =
                (($formatBits >> $index) & 1) !== 0;
        }

        for ($index = 8; $index < 15; $index++) {
            $this->matrix[self::SIZE - 15 + $index][8] =
                (($formatBits >> $index) & 1) !== 0;
        }

        $this->matrix[self::SIZE - 8][8] = true;
    }

    /**
     * Render the matrix as a standards-compliant, truecolor PNG without GD.
     */
    private function renderPng(int $scale): string
    {
        $width = (self::SIZE + self::QUIET_ZONE * 2) * $scale;
        $rawImage = '';

        for ($pixelY = 0; $pixelY < $width; $pixelY++) {
            $moduleY = intdiv($pixelY, $scale) - self::QUIET_ZONE;
            $rawImage .= "\x00";

            for ($pixelX = 0; $pixelX < $width; $pixelX++) {
                $moduleX = intdiv($pixelX, $scale) - self::QUIET_ZONE;
                $dark = $moduleX >= 0
                    && $moduleX < self::SIZE
                    && $moduleY >= 0
                    && $moduleY < self::SIZE
                    && $this->matrix[$moduleY][$moduleX];

                $rawImage .= $dark
                    ? "\x00\x00\x00"
                    : "\xFF\xFF\xFF";
            }
        }

        if (! function_exists('gzcompress')) {
            throw new RuntimeException(
                'L’extension PHP zlib est requise pour générer le QR code.'
            );
        }

        return "\x89PNG\r\n\x1A\n"
            .$this->pngChunk(
                'IHDR',
                pack('NNCCCCC', $width, $width, 8, 2, 0, 0, 0)
            )
            .$this->pngChunk('IDAT', gzcompress($rawImage, 9))
            .$this->pngChunk('IEND', '');
    }

    private function pngChunk(string $type, string $data): string
    {
        return pack('N', strlen($data))
            .$type
            .$data
            .pack('N', crc32($type.$data));
    }
}