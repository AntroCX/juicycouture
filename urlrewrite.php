<?php
$arUrlRewrite=array (
  0 => 
  array (
    'CONDITION' => '#^/bitrix/services/ymarket/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/bitrix/services/ymarket/index.php',
    'SORT' => 100,
  ),
  1 => 
  array (
    'CONDITION' => '#^/sale/brand/([^\\/]+)/.*#',
    'RULE' => 'BRAND=$1',
    'ID' => '',
    'PATH' => '/sale/index.php',
    'SORT' => 100,
  ),
  2 => 
  array (
    'CONDITION' => '#^/brand/([^/]*)/.*#',
    'RULE' => 'BRAND=$1',
    'ID' => '',
    'PATH' => '/brand/index.php',
    'SORT' => 100,
  ),
  3 => 
  array (
    'CONDITION' => '#^/blackfriday/#',
    'RULE' => '',
    'ID' => 'bitrix:catalog',
    'PATH' => '/blackfriday/index.php',
    'SORT' => 100,
  ),
  10 => 
  array (
    'CONDITION' => '#^/vip-presale/#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/vip-presale/index.php',
    'SORT' => 100,
  ),
  4 => 
  array (
    'CONDITION' => '#^/sitemap.xml#',
    'RULE' => '',
    'ID' => '',
    'PATH' => '/sitemap.php',
    'SORT' => 100,
  ),
  11 => 
  array (
    'CONDITION' => '#^/personal/#',
    'RULE' => '',
    'ID' => 'bitrix:sale.personal.section',
    'PATH' => '/personal/index.php',
    'SORT' => 100,
  ),
  12 => 
  array (
    'CONDITION' => '#^/preorder/#',
    'RULE' => '',
    'ID' => 'bitrix:catalog',
    'PATH' => '/preorder/index.php',
    'SORT' => 100,
  ),
  5 => 
  array (
    'CONDITION' => '#^/catalog/#',
    'RULE' => '',
    'ID' => 'bitrix:catalog',
    'PATH' => '/catalog/index.php',
    'SORT' => 100,
  ),
  8 => 
  array (
    'CONDITION' => '#^/rest/#',
    'RULE' => '',
    'ID' => NULL,
    'PATH' => '/bitrix/services/rest/index.php',
    'SORT' => 100,
  ),
  9 => 
  array (
    'CONDITION' => '#^/sale/#',
    'RULE' => '',
    'ID' => 'bitrix:catalog',
    'PATH' => '/sale/index.php',
    'SORT' => 100,
  ),
  7 => 
  array (
    'CONDITION' => '#^/new/#',
    'RULE' => '',
    'ID' => 'bitrix:catalog',
    'PATH' => '/new/index.php',
    'SORT' => 100,
  ),
);
