<?php
/**
 * @package     CsviXML
 *
 * @author      Pieter-Jan de Vries/Obix webtechniek <pieter@obix.nl>
 * @copyright   Copyright (C) 2021 Obix webtechniek. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.obix.nl
 */
require_once '../library/RolandD/autoload.php';

use RolandD\XML\XMLParser;
use RolandD\XML\XpathHandler\EchoHandler;
use RolandD\XML\XpathHandler\CsviHandler;
use RolandD\XML\XpathHandler\ShowXpathHandler;

$parser = new XMLParser();
$parser->open('../tmp/Groups.xml');

//$handler = new EchoHandler();
//$parser
//	->addXpathHandler('/GroupExport/Groups/Group/GroupName/text()',
//		$handler, \XMLReader::TEXT)
//	->addXpathHandler('/GroupExport/Groups/Group/SubGroups/Group/GroupName/text()',
//		$handler, \XMLReader::TEXT)
//	->addXpathHandler('/GroupExport/Groups/Group/SubGroups/Group/SubGroups/Group/GroupName/text()',
//		$handler, \XMLReader::TEXT)
//;
$handler = new ShowXpathHandler();
$parser->addXpathHandler('*', $handler, \XMLReader::ELEMENT);

$elements = [];
foreach ($parser->parse() as $element)
{
	if (!isset($elements[$element['xpath']])
		|| (empty($elements[$element['xpath']]) && !empty($element['value'])))
	{
		$elements[$element['xpath']] = $element['value'];
	}
}

ksort($elements);
foreach ($elements as $key => $value)
{
	printf("%s: [%s]\n", $key, $value);
}

//$parser
//	->addXpathHandler('/GroupExport/Groups/Group/Specs/Spec[0]', new EchoHandler())
//	->addXpathHandler('/GroupExport/Groups/Group/Specs/Spec[1]', new EchoHandler())
//;

//$csviHandler = new CsviHandler();
//$parser
////	->addXpathHandler('/GroupExport/Groups/Group/*/text()', $csviHandler, \XMLReader::TEXT)
//	->addXpathHandler('/GroupExport/Groups/Group/*', $csviHandler)
//	->addXpathHandler('/GroupExport/Groups/Group', $csviHandler, \XMLReader::END_ELEMENT)
//;
//foreach ($parser->parse() as $result)
//{
//	printf("%s\n", print_r($result, true));
//}