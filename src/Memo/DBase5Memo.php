<?php declare(strict_types=1);

namespace TotalCRM\DBase\Memo;

class DBase5Memo extends AbstractWritableMemo
{
    const BLOCK_TYPE_TEXT = 0x00000001;
    const BLOCK_HEADER_LENGTH = 8; // 4 bytes type + 4 bytes length

    /** @var int */
    protected $blockLengthInBytes;

    protected function readHeader(): void
    {
        $this->fp->seek(0);
        $this->nextFreeBlock = $this->fp->readUInt(); // 4 Bytes

        $blockSize = $this->fp->read(2); // dBASE V typically uses 512-byte blocks
        $this->blockLengthInBytes = unpack('n', $blockSize)[1] ?: 512;

        // Seek to start for safety
        $this->fp->seek(0);
    }

    public function get(int $pointer): ?MemoObject
    {
        if (!$this->isOpen()) {
            $this->open();
        }

        $this->fp->seek($pointer * $this->blockLengthInBytes);

        $type = unpack('N', $this->fp->read(4))[1]; // Memo type
        $length = unpack('N', $this->fp->read(4))[1]; // Memo length

        if ($length <= 0 || $length > $this->blockLengthInBytes - 8) {
            return null;
        }

        $data = $this->fp->read($length);

        $info = $this->guessDataType($data);
        assert(isset($info['type']));

        if (MemoObject::TYPE_TEXT === $info['type'] && $this->table->options['encoding']) {
            $data = $this->encoder->encode($data, $this->table->options['encoding'], 'utf-8');
        }

        return new MemoObject($data, $info['type'], $pointer, $length);
    }

    protected function getBlockLengthInBytes(): int
    {
        return $this->blockLengthInBytes;
    }

    protected function calculateBlockCount(string $data): int
    {
        $requiredBytes = self::BLOCK_HEADER_LENGTH + strlen($data);
        return (int) ceil($requiredBytes / $this->blockLengthInBytes);
    }

    protected function toBinaryString(string $data, int $lengthInBlocks): string
    {
        $header = pack('N', self::BLOCK_TYPE_TEXT) . pack('N', strlen($data));
        return str_pad($header . $data, $lengthInBlocks * $this->blockLengthInBytes, "\0");
    }
}