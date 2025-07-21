<?php declare(strict_types=1);

namespace TotalCRM\DBase\Memo;

use TotalCRM\DBase\DataConverter\Encoder\EncoderInterface;
use TotalCRM\DBase\Enum\TableType;
use TotalCRM\DBase\Table\Table;

class MemoFactory
{
    public static function create(Table $table, EncoderInterface $encoder): ?MemoInterface
    {
        $class = self::getClass($table->getVersion());
        $refClass = new \ReflectionClass($class);
        if (!$refClass->implementsInterface(MemoInterface::class)) {
            return null;
        }

        $memoExt = $refClass->getMethod('getExtension')->invoke(null);
        if ('.' !== substr($memoExt, 0, 1)) {
            $memoExt = '.'.$memoExt;
        }

        $fileInfo = pathinfo($table->filepath);
        $expectedBase = $fileInfo['filename'];
        $expectedExt = ltrim($memoExt, '.');
        $dir = $fileInfo['dirname'];
        $foundMemoFile = null;

        foreach (scandir($dir) as $file) {
            if (!is_file($dir . DIRECTORY_SEPARATOR . $file)) {
                continue;
            }

            $info = pathinfo($file);
            if (
                isset($info['filename'], $info['extension']) &&
                strcasecmp($info['filename'], $expectedBase) === 0 &&
                strcasecmp($info['extension'], $expectedExt) === 0
            ) {
                $foundMemoFile = $dir . DIRECTORY_SEPARATOR . $file;
                break;
            }
        }

        if (!$foundMemoFile) {
            return null;
        }
        return $refClass->newInstance($table, $foundMemoFile, $encoder);
    }

    private static function getClass(int $version): string
    {
        switch ($version) {
            case TableType::DBASE_III_PLUS_MEMO:
                return DBase3Memo::class;
            case TableType::DBASE_IV_MEMO:
                return DBase4Memo::class;
            case TableType::DBASE_7_MEMO:
            case TableType::DBASE_7_NOMEMO:
                return DBase7Memo::class;
            case TableType::FOXPRO_MEMO:
            case TableType::VISUAL_FOXPRO:
            case TableType::VISUAL_FOXPRO_AI:
            case TableType::VISUAL_FOXPRO_VAR:
                return FoxproMemo::class;
        }

        throw new \LogicException('Unknown table memo type: '.$version);
    }
}
