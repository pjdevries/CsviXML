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
	 * @var array
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
	 * @var array
	 */
	protected $nodeCounter = [];

	/**
	 * Do not remove redundant white space.
	 *
	 * @var bool
	 */
	public $preserveWhiteSpace = true;

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
	public function read(): bool
	{
		if (!parent::read())
		{
			return false;
		}

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

			$this->nodes       = array_slice($this->nodes, 0, $this->depth + 1, true);
			$this->nodeCounter = array_slice($this->nodeCounter, 0, $this->depth + 1, true);
		}

		$this->prevDepth = $this->depth;

		if (isset($this->nodes[$this->depth])
			&& $this->localName === $this->nodes[$this->depth]->getLocalName()
			&& $this->nodeType === $this->nodes[$this->depth]->getType())
		{
			$this->nodeCounter[$this->depth]++;

			return true;
		}

		$this->nodes[$this->depth]       = new Node($this->nodeType, $this->localName);
		$this->nodeCounter[$this->depth] = 1;

		return true;
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
			switch ($node->getType())
			{
				case \XMLReader::ELEMENT:
					$xpath .= '/' . $node->getLocalName();

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
					$xpath .= '[@{' . $node->getLocalName() . '}]';
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
	public function parse(): void
	{
		while ($this->read())
		{
			if (!isset($this->xpathHandlers[$this->nodeType]))
			{
				continue;
			}

			/** @var XpathHandlerInterface $pathHandler */

			$pathHandler = $this->xpathHandlers[$this->nodeType]['*'] ?? null;

			if ($pathHandler && $pathHandler->handle($this))
			{
				continue;
			}

			$pathHandler = $this->xpathHandlers[$this->nodeType][$this->name] ?? null;

			if ($pathHandler && $pathHandler->handle($this))
			{
				continue;
			}

			$xpath       = $this->currentXpath(false); // without node counter
			$pathHandler = $this->xpathHandlers[$this->nodeType][$xpath] ?? null;

			if ($pathHandler && $pathHandler->handle($this))
			{
				continue;
			}

			$xpath       = $this->currentXpath(true); // with node counter
			$pathHandler = $this->xpathHandlers[$this->nodeType][$xpath] ?? null;

			if ($pathHandler && !$pathHandler->handle($this))
			{
				break;
			}
		}
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
			$nodeName = $node->getLocalName();
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
			$nodeName = $node->getLocalName();
			$nodeName = (isset($nodeName[0]) && $nodeName[0] ? $nodeName[0] : "root");
			$node     = $document->createElement($nodeName);
			$node->appendChild($element);
			$element = $node;
		}
		$node = $document->importNode($element, true);
		$document->appendChild($node);

		return $document;
	}
}
