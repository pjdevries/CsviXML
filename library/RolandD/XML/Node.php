<?php
/**
 * @package     CsviXML
 *
 * @author      Pieter-Jan de Vries/Obix webtechniek <pieter@obix.nl>
 * @copyright   Copyright (C) 2021 Obix webtechniek. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.obix.nl
 */

namespace RolandD\XML;

class Node
{
	private $type = \XMLReader::NONE;

	private $localName = '';

	/**
	 * Node constructor.
	 *
	 * @param   int     $type
	 * @param   string  $localName
	 */
	public function __construct(int $type, string $localName)
	{
		$this->type      = $type;
		$this->localName = $localName;
	}

	/**
	 * @return int
	 */
	public function getType(): int
	{
		return $this->type;
	}

	/**
	 * @param   int  $type
	 *
	 * @return Node
	 */
	public function setType(int $type): Node
	{
		$this->type = $type;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getLocalName(): string
	{
		return $this->localName;
	}

	/**
	 * @param   string  $localName
	 *
	 * @return Node
	 */
	public function setLocalName(string $localName): Node
	{
		$this->localName = $localName;

		return $this;
	}

	private function getTypeName(): string
	{
		return self::typeName($this->type);
	}

	public static function typeName(int $nodeType): string
	{
		static $names = [
			\XMLReader::NONE => 'NONE',
			\XMLReader::ELEMENT => 'ELEMENT',
			\XMLReader::ATTRIBUTE => 'ATTRIBUTE',
			\XMLReader::TEXT => 'TEXT',
			\XMLReader::CDATA => 'CDATA',
			\XMLReader::ENTITY_REF => 'ENTITY_REF',
			\XMLReader::ENTITY => 'ENTITY',
			\XMLReader::PI => 'PI',
			\XMLReader::COMMENT => 'COMMENT',
			\XMLReader::DOC => 'DOC',
			\XMLReader::DOC_TYPE => 'DOC_TYPE',
			\XMLReader::DOC_FRAGMENT => 'DOC_FRAGMENT',
			\XMLReader::NOTATION => 'NOTATION',
			\XMLReader::WHITESPACE => 'WHITESPACE',
			\XMLReader::SIGNIFICANT_WHITESPACE => 'SIGNIFICANT_WHITESPACE',
			\XMLReader::END_ELEMENT => 'END_ELEMENT',
			\XMLReader::END_ENTITY => 'END_ENTITY',
			\XMLReader::XML_DECLARATION => 'XML_DECLARATION'
		];

		return $names[$nodeType];
	}
}