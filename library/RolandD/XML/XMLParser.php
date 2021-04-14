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

use RolandD\XML\XpathHandler\XpathHandlerInterface;

/**
 * XML Parser
 *
 * Based on SimpleXMLReader by Dmitry Pyatkov(aka dkrnl) <dkrnl@yandex.ru>
 * http://github.com/dkrnl/XMLParser
 */
class XMLParser extends \XMLReader
{
	/**
	 * Callbacks
	 *
	 * @var XpathHandlerInterface[][]
	 */
	protected $xpathHandlers = [];

	/**
	 * Previos depth
	 *
	 * @var int
	 */
	protected $prevDepth = 0;

	/**
	 * Stack of the nodes
	 *
	 * @var Node[]
	 */
	protected $nodes = [];

	/**
	 * Stack of node position
	 *
	 * @var int[]
	 */
	protected $nodeCounter = [];

	/**
	 * Do not remove redundant white space.
	 *
	 * @var bool
	 */
	protected $preserveWhiteSpace = true;

	/**
	 * Add xpath handler.
	 *
	 * @param   string                 $xpath
	 * @param   XpathHandlerInterface  $handler
	 * @param   int                    $nodeType
	 *
	 * @return XMLParser
	 */
	public function addXpathHandler(string $xpath,
	                                XpathHandlerInterface $handler, int $nodeType = \XMLReader::ELEMENT): self
	{
		if (isset($this->xpathHandlers[$nodeType][$xpath]))
		{
			throw new XmlParserException(sprintf('Callback %s for node type %s already exists!',
				$xpath, Node::typeName($nodeType)));
		}

		return $this->setXpathHandler($xpath, $handler, $nodeType);
	}

	/**
	 * Set xpath handler.
	 *
	 * @param   string                 $xpath
	 * @param   XpathHandlerInterface  $handler
	 * @param   int                    $nodeType
	 *
	 * @return XMLParser
	 */
	public function setXpathHandler(string $xpath, XpathHandlerInterface $handler, $nodeType = \XMLReader::ELEMENT): self
	{
		$this->xpathHandlers[$nodeType][$xpath] = $handler;

		return $this;
	}

	/**
	 * Remove xpath handler.
	 *
	 * @param   string  $xpath
	 * @param   int     $nodeType
	 *
	 * @return XMLParser
	 */
	public function unsetXpathHandler($xpath, $nodeType = \XMLReader::ELEMENT): self
	{
		if (!isset($this->xpathHandlers[$nodeType][$xpath]))
		{
			return $this;
		}

		unset($this->xpathHandlers[$nodeType][$xpath]);

		return $this;
	}

	/**
	 * Moves cursor to the next node in the document.
	 *
	 * @link http://php.net/manual/en/xmlreader.read.php
	 * @return bool Returns TRUE on success or FALSE on failure.
	 */
	public function read(): ?Node
	{
		if (!parent::read())
		{
			return null;
		}

		$node = Node::fromReader($this);

		if ($this->depth < $this->prevDepth)
		{
			if (!isset($this->nodes[$this->depth]))
			{
				throw new XmlParserException("Invalid XML: missing nodes in XMLParser::\$nodes");
			}

			if (!isset($this->nodeCounter[$this->depth]))
			{
				throw new XmlParserException("Invalid XML: missing items in XMLParser::\$nodesCounter");
			}

			$previousNode    = array_pop($this->nodes);
			$previousCounter = array_pop($this->nodeCounter);
		}

		$this->prevDepth = $this->depth;

		if (isset($this->nodes[$this->depth])
			&& $this->localName === $this->nodes[$this->depth]->localName
			&& $this->nodeType === $this->nodes[$this->depth]->nodeType)
		{
			$this->nodeCounter[$this->depth]++;

			return true;
		}

		$this->nodes[$this->depth]       = $node;
		$this->nodeCounter[$this->depth] = 1;

		$node->setXpath($this->currentXpath());

		return $node;
	}

	/**
	 * Return current xpath node.
	 *
	 * @param   boolean  $nodeCounter
	 *
	 * @return string
	 */
	public function currentXpath(bool $nodeCounter = false): string
	{
		if (count($this->nodeCounter) !== count($this->nodes))
		{
			throw new XmlParserException("Empty reader!");
		}

		$xpath = '';

		foreach ($this->nodes as $depth => $node)
		{
			switch ($node->nodeType)
			{
				case \XMLReader::ELEMENT:
					$xpath .= '/' . $node->localName;

					if ($nodeCounter)
					{
						$xpath .= '[' . $this->nodeCounter[$depth] . ']';
					}
					break;

				case \XMLReader::TEXT:
				case \XMLReader::CDATA:
					$xpath .= '/text()';
					break;

				case \XMLReader::COMMENT:
					$xpath .= '/comment()';
					break;

				case \XMLReader::ATTRIBUTE:
					$xpath .= '[@{' . $node->localName . '}]';
					break;
			}
		}

		return $xpath;
	}

	/**
	 * Run parser
	 *
	 * @return void
	 */
	public function parse(): \Generator
	{
		while ($node = $this->read())
		{
			$pathHandler = $this->getHandler();

			$result = $pathHandler ? $pathHandler->handle($this, $node) : null;

			if ($result)
			{
				yield $result;
			}
		}
	}

	public function parse2array(): array
	{
		$array = [];

		while ($node = $this->read())
		{
			$pathHandler = $this->getHandler();

			$result = $pathHandler ? $pathHandler->handle($this, $node) : null;

			if ($result)
			{
				$array[] = $result;
			}
		}

		return $array;
	}

	private function getHandler(): ?XpathHandlerInterface
	{
		if (!isset($this->xpathHandlers[$this->nodeType]))
		{
			return null;
		}

		$nodeTypeHandlers = $this->xpathHandlers[$this->nodeType];

		if ($pathHandler = $this->findHandler($nodeTypeHandlers, '//*'))
		{
			return $pathHandler;
		}

		if ($pathHandler = $this->findHandler($nodeTypeHandlers, $this->name))
		{
			return $pathHandler;
		}

		$xpath = $this->currentXpath(false); // without node counter

		if ($pathHandler = $this->findHandler($nodeTypeHandlers, $xpath))
		{
			return $pathHandler;
		}

		$xpath = $this->currentXpath(true); // with node counter

		if ($pathHandler = $this->findHandler($nodeTypeHandlers, $xpath))
		{
			return $pathHandler;
		}

		return null;
	}

	private function findHandler(array $nodeTypeHandlers, string $searchForXpath): ?XpathHandlerInterface
	{
		if (isset($nodeTypeHandlers[$searchForXpath]))
		{
			return $nodeTypeHandlers[$searchForXpath];
		}

		foreach ($nodeTypeHandlers as $xpath => $handler)
		{
			if (fnmatch($xpath, $searchForXpath))
			{
				return $handler;
			}
		}

		return null;
	}
	
	private function match()
	{
	/**
	* From W3C Reccommendation
	* https://www.w3.org/TR/1999/REC-xpath-19991116/#location-paths
	**/
	// para: selects the para element children of the context node
	// *: selects all element children of the context node
	// text(): selects all text node children of the context node
	// @name: selects the name attribute of the context node
	// @*: selects all the attributes of the context node
	// para[1]: selects the first para child of the context node
	// para[last()]: selects the last para child of the context node
	// */para: selects all para grandchildren of the context node
	// /doc/chapter[5]/section[2]: selects the second section of the fifth chapter of the doc
	// chapter//para: selects the para element descendants of the chapter element children of the context node
	// //para: selects all the para descendants of the document root and thus selects all para elements in the same document as the context node
	// //olist/item: selects all the item elements in the same document as the context node that have an olist parent
	// .: selects the context node
	// .//para: selects the para element descendants of the context node
	// ..: selects the parent of the context node
	// ../@lang: selects the lang attribute of the parent of the context node
	// para[@type="warning"]: selects all para children of the context node that have a type attribute with value warning
	// para[@type="warning"][5]: selects the fifth para child of the context node that has a type attribute with value warning
	// para[5][@type="warning"]: selects the fifth para child of the context node if that child has a type attribute with value warning
	// chapter[title="Introduction"]: selects the chapter children of the context node that have one or more title children with string-value equal to Introduction
	// chapter[title]: selects the chapter children of the context node that have one or more title children
	// employee[@secretary and @assistant]: selects all the employee children of the context node that have both a secretary attribute and an assistant attribute
	}

	/**
	 * Run XPath query on current node
	 *
	 * @param   string  $path
	 * @param   string  $version
	 * @param   string  $encoding
	 * @param   string  $className
	 *
	 * @return \SimpleXMLElement[]
	 */
	public function expandXpath($path, $version = "1.0", $encoding = "UTF-8", $className = null)
	{
		return $this->expandSimpleXml($version, $encoding, $className)->xpath($path);
	}

	/**
	 * Expand current node to string
	 *
	 * @param   string  $version
	 * @param   string  $encoding
	 * @param   string  $className
	 *
	 * @return \SimpleXMLElement
	 */
	public function expandString($version = "1.0", $encoding = "UTF-8", $className = null)
	{
		return $this->expandSimpleXml($version, $encoding, $className)->asXML();
	}

	/**
	 * Expand current node to SimpleXMLElement
	 *
	 * @param   string  $version
	 * @param   string  $encoding
	 * @param   string  $className
	 *
	 * @return \SimpleXMLElement
	 */
	public function expandSimpleXml($version = "1.0", $encoding = "UTF-8", $className = null)
	{
		$element                      = $this->expand();
		$document                     = new \DomDocument($version, $encoding);
		$document->preserveWhiteSpace = $this->preserveWhiteSpace;

		if ($element instanceof \DOMCharacterData)
		{
			/** @var Node $node */
			$node     = array_splice($this->nodes, -2, 1);
			$nodeName = $node->localName;
			$nodeName = (isset($nodeName[0]) && $nodeName[0] ? $nodeName[0] : "root");
			$node     = $document->createElement($nodeName);
			$node->appendChild($element);
			$element = $node;
		}

		$node = $document->importNode($element, true);
		$document->appendChild($node);

		return simplexml_import_dom($node, $className);
	}

	/**
	 * Expand current node to DomDocument
	 *
	 * @param   string  $version
	 * @param   string  $encoding
	 *
	 * @return \DomDocument
	 */
	public function expandDomDocument($version = "1.0", $encoding = "UTF-8")
	{
		$element                      = $this->expand();
		$document                     = new \DomDocument($version, $encoding);
		$document->preserveWhiteSpace = $this->preserveWhiteSpace;
		if ($element instanceof \DOMCharacterData)
		{
			/** @var Node $node */
			$node     = array_splice($this->nodes, -2, 1);
			$nodeName = $node->localName;
			$nodeName = (isset($nodeName[0]) && $nodeName[0] ? $nodeName[0] : "root");
			$node     = $document->createElement($nodeName);
			$node->appendChild($element);
			$element = $node;
		}
		$node = $document->importNode($element, true);
		$document->appendChild($node);

		return $document;
	}

	/**
	 * @return int
	 */
	public function getPrevDepth(): int
	{
		return $this->prevDepth;
	}

	/**
	 * @return Node[]
	 */
	public function getNodes(): array
	{
		return $this->nodes;
	}

	/**
	 * @return int[]
	 */
	public function getNodeCounter(): array
	{
		return $this->nodeCounter;
	}

	/**
	 * @return bool
	 */
	public function isPreserveWhiteSpace(): bool
	{
		return $this->preserveWhiteSpace;
	}

	/**
	 * @param   bool  $preserveWhiteSpace
	 *
	 * @return XMLParser
	 */
	public function setPreserveWhiteSpace(bool $preserveWhiteSpace): XMLParser
	{
		$this->preserveWhiteSpace = $preserveWhiteSpace;

		return $this;
	}
}
