<?php
/**
 * Created by PhpStorm.
 * User: maxkrasnov
 * Date: 21.02.17
 * Time: 17:05
 *
 */

namespace Jamilco\TaggedPages;

use Bitrix\Main\Entity;

/**
 * * Класс для работы с таблицей j_taggedpages,
 * в таблице прописываются условия для теггированных страниц
 *
 * Class PagesTable
 * @package Jamilco\TaggedPages
 * @author maxkrasnov
 */
class PagesTable extends Entity\DataManager {
    public static function getTableName()
    {
        // таблица в бд, все наши таблицы начинаются с префиксом j_*
        return 'j_taggedpages';
    }
    public static function getMap()
    {
        return array(
            new Entity\IntegerField('ID', array(
                'primary' => true,
                'autocomplete' => true
            )),
            // заголовок страницы
            new Entity\StringField('TITLE', array(
                'required' => true
            )),
            // url страницы
            new Entity\StringField('URL', array(
                'required' => true
            )),

            // правило обработки страницы ссылкой
            new Entity\StringField('RULE_URL'),
            // правило обработки страницы, фильтр-массив
            new Entity\StringField('RULE_PARAMS'),

            // количество товаров отображаемых на странице, если 0, то все
            new Entity\IntegerField('NUM_PAGES'),
            // активность страницы
            new Entity\BooleanField('ACTIVE'),
            // отображать фильтр или нет
            new Entity\BooleanField('SHOW_FILTER'),
            // вставка html контента над списком товаров
            new Entity\TextField('TOP_HTML'),
            // вставка html контента под списком товаров
            new Entity\TextField('BOTTOM_HTML'),
            // seo метатег description
            new Entity\StringField('SEO_DESCRIPTION'),
            // seo метатег keywords
            new Entity\StringField('SEO_KEYWORDS'),
            new Entity\StringField('SEO_TITLE'),
            // привязка к разделам
            new Entity\StringField('SECTIONS'),
        );
    }

    /**
     * обработчик события добавления - добавляем запись в урлрекврайт при добавлении в таблицу
     * @param Entity\Event $event
     */
    public static function onAfterAdd(Entity\Event $event) {
        $fields = $event->getParameter("fields");
        $primaries = $event->getParameter("primary");

        \CUrlRewriter::Add(array(
            "CONDITION" => "#^".$fields['URL']."#",
            "RULE" => $fields['RULE_URL'],
            "ID" => "TAGGEDPAGE".$primaries['ID'],
            "PATH" => "/local/modules/jamilco.taggedpages/taggedpages.php",
        ));
    }

    /**
     * обработчик события удаления - удаляем записи в урлреврайт при удалении записи из таблицы
     * @param Entity\Event $event
     */
    public static function OnDelete(Entity\Event $event)
    {
        $primaries = $event->getParameter("primary");
        \CUrlRewriter::Delete(array(
            'ID' => "TAGGEDPAGE".$primaries['ID']
        ));
    }

    /**
     * обработчик события обновления записи - обновляем запись в урлреврайт
     * @param Entity\Event $event
     */
    public static function OnAfterUpdate(Entity\Event $event)
    {
        $fields = $event->getParameter("fields");
        $primaries = $event->getParameter("primary");
        \CUrlRewriter::Update(
            array(
                'ID' => "TAGGEDPAGE".$primaries['ID']
            ),
            array(
                "CONDITION" => "#^".$fields['URL']."#",
                "RULE" => $fields['RULE_URL']
            )
        );
    }

}