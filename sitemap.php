<?php 
	require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
	CModule::IncludeModule('iblock');
	$site = 'https://juicycouture.ru';
	$APPLICATION->RestartBuffer();
	header("Content-Type: text/xml");
	define(IBLOCK_CATALOG_ID, 1);
?>
<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
	<url>
		<loc><?php echo $site; ?>/</loc>
		<lastmod><?php echo(date("Y-m-d")); ?></lastmod>
		<changefreq>daily</changefreq>
		<priority>1</priority>
	</url>
	<?php
		$dbRes = CIBlockSection::GetTreeList(
			array(
				'IBLOCK_ID' => IBLOCK_CATALOG_ID,
				'ACTIVE' => 'Y',
			),
			array(
				'ID',
				'IBLOCK_ID',
				'SECTION_PAGE_URL',
				'DEPTH_LEVEL',
			)
		);

		while ($arRes = $dbRes->GetNext())
		{
			$changefreq = 'weekly';
			$priority = '0.8';

			if ($arRes['DEPTH_LEVEL'] == 1)
				$priority = '0.9';

			echo "<url>";
			echo "<loc>" . $site . $arRes['SECTION_PAGE_URL'] . "</loc>";
			echo "<lastmod>" . date("Y-m-d") . "</lastmod>";
			echo "<changefreq>" . $changefreq . "</changefreq>";
			echo "<priority>" . $priority . "</priority>";
			echo "</url>";
		}

		$dbRes = CIBlockElement::GetList(
			array(),
			array(
				'IBLOCK_ID' => IBLOCK_CATALOG_ID,
				'ACTIVE' => 'Y',
			),
			false,
			false,
			array(
				'ID',
				'IBLOCK_ID',
				'DETAIL_PAGE_URL',
			)
		);

		while ($arRes = $dbRes->GetNext())
		{
			$changefreq = 'monthly';
			$priority = '0.6';

			echo "<url>";
			echo "<loc>" . $site . $arRes['DETAIL_PAGE_URL'] . "</loc>";
			echo "<lastmod>" . date("Y-m-d") . "</lastmod>";
			echo "<changefreq>" . $changefreq . "</changefreq>";
			echo "<priority>" . $priority . "</priority>";
			echo "</url>";
		}
	?>
</urlset> 
<?php die(); ?>