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

$parser = new XMLParser();
$parser->open('../tmp/Groups.xml');
$parser
	->addXpathHandler('/GroupExport/Groups/Group/GroupName/text()', new EchoHandler(), \XMLReader::TEXT)
	->addXpathHandler('/GroupExport/Groups/Group/SubGroups/Group/GroupName/text()', new EchoHandler(), \XMLReader::TEXT)
	->addXpathHandler('/GroupExport/Groups/Group/SubGroups/Group/SubGroups/Group/GroupName/text()', new EchoHandler(), \XMLReader::TEXT)
;
$parser->parse();