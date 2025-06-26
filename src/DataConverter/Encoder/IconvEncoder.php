<?php declare(strict_types=1);

namespace TotalCRM\DBase\DataConverter\Encoder;

/**
 * Class IconvEncoder
 * @package TotalCRM\DBase\DataConverter\Encoder
 */
class IconvEncoder implements EncoderInterface
{
    public function encode(string $string, string $fromEncoding, string $toEncoding): string
    {
        try {
            return iconv($fromEncoding, $toEncoding, $string)??'';
        } catch (\TypeError $e) {
            return $string;
        }
    }
}
