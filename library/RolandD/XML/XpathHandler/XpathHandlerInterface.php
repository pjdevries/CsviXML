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

use RolandD\XML\XMLParser;

interface XpathHandlerInterface
{
	public function handle(XMLParser $parser): bool;

	public function getNodeType(): int;

	public function getXpath(): string;
}