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

abstract class XpathHandlerBase implements XpathHandlerInterface
{
	private $nodeType;

	private $xpath;

	/**
	 * @return int
	 */
	public function getNodeType(): int
	{
		return $this->nodeType;
	}

	/**
	 * @param   int  $nodeType
	 *
	 * @return XpathHandlerBase
	 */
	public function setNodeType(int $nodeType): self
	{
		$this->nodeType = $nodeType;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getXpath(): string
	{
		return $this->xpath;
	}

	/**
	 * @param   string  $xpath
	 *
	 * @return XpathHandlerBase
	 */
	public function setXpath(string $xpath): self
	{
		$this->xpath = $xpath;

		return $this;
	}
}