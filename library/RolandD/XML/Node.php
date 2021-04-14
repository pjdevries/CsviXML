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

/**
 * Class Node
 * @package RolandD\XML
 *
 * @property $nodeType
 * @property $name
 * @property $localName
 * @property $attributeCount
 * @property $baseURI
 * @property $depth
 * @property $hasAttributes
 * @property $hasValue
 * @property $isDefault
 * @property $isEmptyElement
 * @property $namespaceURI
 * @property $prefix
 * @property $value
 * @property $xmlLang
 */
class Node
{
	private string $xpath = '';

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
	 * @return Node
	 */
	public function setXpath(string $xpath): Node
	{
		$this->xpath = $xpath;

		return $this;
	}

	public static function fromReader(\XMLReader $reader): self
	{
		static $otherProps = [
			'nodeType',
			'name',
			'localName',
			'attributeCount',
			'baseURI',
			'depth',
			'hasAttributes',
			'hasValue',
			'isDefault',
			'isEmptyElement',
			'namespaceURI',
			'prefix',
			'value',
			'xmlLang'
		];

		$node = new self();

		foreach ($otherProps as $name)
		{
			$node->{$name} = $reader->{$name};
		}

		return $node;
	}

	private function getTypeName(): string
	{
		return self::typeName($this->type);
	}

	public static function typeName(int $nodeType): string
	{
		static $names = [
			\XMLReader::NONE                   => 'NONE',
			\XMLReader::ELEMENT                => 'ELEMENT',
			\XMLReader::ATTRIBUTE              => 'ATTRIBUTE',
			\XMLReader::TEXT                   => 'TEXT',
			\XMLReader::CDATA                  => 'CDATA',
			\XMLReader::ENTITY_REF             => 'ENTITY_REF',
			\XMLReader::ENTITY                 => 'ENTITY',
			\XMLReader::PI                     => 'PI',
			\XMLReader::COMMENT                => 'COMMENT',
			\XMLReader::DOC                    => 'DOC',
			\XMLReader::DOC_TYPE               => 'DOC_TYPE',
			\XMLReader::DOC_FRAGMENT           => 'DOC_FRAGMENT',
			\XMLReader::NOTATION               => 'NOTATION',
			\XMLReader::WHITESPACE             => 'WHITESPACE',
			\XMLReader::SIGNIFICANT_WHITESPACE => 'SIGNIFICANT_WHITESPACE',
			\XMLReader::END_ELEMENT            => 'END_ELEMENT',
			\XMLReader::END_ENTITY             => 'END_ENTITY',
			\XMLReader::XML_DECLARATION        => 'XML_DECLARATION'
		];

		return $names[$nodeType];
	}
}