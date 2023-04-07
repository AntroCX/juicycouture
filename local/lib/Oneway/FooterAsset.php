<?php


namespace Oneway;


use Bitrix\Main\Page\Asset;


final class FooterAsset
{
    const FOOTER_LOCATION = 'FOOTER';

    public static function addJs($files)
    {
        foreach ((array)$files as $file) {
            $path = self::getFullAssetPath('/local/templates/.default/scripts/' . $file);
            self::addScript($path);
        }
    }

    public static function getFullAssetPath($filePath)
    {
        if (file_exists($filePath) && filesize($filePath) > 0)
        {
            return \CUtil::GetAdditionalFileURL($filePath, true);
        }

        return null;
    }

    public static function addScript(string $src)
    {
        self::addString('<script src="' . $src . '"></script>');
    }

    public static function addJsCode(string $sourceCode)
    {
        self::addString("<script>{$sourceCode}</script>");
    }

    public static function addString(string $string)
    {
        Asset::getInstance()->addString($string, false, self::FOOTER_LOCATION);
    }

    public static function show()
    {
        echo Asset::getInstance()->getStrings(self::FOOTER_LOCATION);
    }
}
