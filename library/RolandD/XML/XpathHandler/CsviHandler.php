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

class CsviHandler extends XpathHandlerBase
{
	private $data = [];

	public function handle(XMLParser $parser, Node $node):? array
	{
		switch ($parser->nodeType)
		{
			case \XMLReader::TEXT:
				$this->data[$node->getXpath()] = $parser->value;

				return null;

			case \XMLReader::ELEMENT:
				$key = $parser->currentXpath();
				$value = (string) (new \SimpleXMLElement($parser->readOuterXml()));
				$this->data[$key] = $value;

				return null;

			case \XMLReader::END_ELEMENT:
				if (count($this->data))
				{
					$data = $this->data;
					$this->data = [];

					return $data;
				}

				return null;

			default:
				return null;
		}
	}
}