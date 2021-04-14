<?php
/**
 * @package     CsviXML
 *
 * @author      Pieter-Jan de Vries/Obix webtechniek <pieter@obix.nl>
 * @copyright   Copyright (C) 2021 Obix webtechniek. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.obix.nl
 */

namespace RolandD\XML\XpathHandler;

use RolandD\XML\Node;
use RolandD\XML\XMLParser;

class ShowXpathHandler extends XpathHandlerBase
{
	public function handle(XMLParser $parser, Node $node):? string
	{
		$value = trim((string) (new \SimpleXMLElement($parser->readOuterXml())));

		if ($value === '')
		{
			return $node->getXpath();
		}

		return sprintf("%s: [%s]", $node->getXpath(), $value);
	}
}