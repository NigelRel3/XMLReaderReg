<?php
declare(strict_types=1);

namespace XMLReaderReg;

use DOMDocument;
use DOMElement;
use DOMXPath;
use ReflectionFunction;
use SimpleXMLElement;
use XMLReader;

/**
 * @author NigelRen <nigelrel3@yahoo.co.uk>
 */
class XMLReaderReg extends XMLReader {
    /**
     * Various matching criteria along with the callback to execute.
     *
     * @var callable[] - [regex => callable, ...]
     */
    private $dataMatch = null;
    
    /**
     * Matches the regex array $dataMatch except the value is the
     * datatype to be passed to the callable.  This will usually
     * be something like SimpleXMLElement or DOMElement.
     * @var string array
     */
    private $callableParam = null;
    
    /**
     * Path to the current data item, used to match with $dataMatch for extract.
     * Path is made up of keys for each level of structure.
     *
     * @var array string
     */
    private $path = [];

    /**
     * For arrays, this will have the numerical index, for objects this
     * will be -1.
     *
     * @var array
     */
    private $pathIndex = [];

    /**
     * The last index of the path arrays.
     *
     * @var int
     */
    private $pathLength = 0;

    /**
     * The 'current' label.
     *
     * @var string|null
     */
    private $label;

    /**
     * Document used for importing data to SimpleXML or DOMElement
     * @var \DOMDocument
     */
    private $doc = null;
    
    /**
     * Used for processing namespace removal.
     * @var \DOMXPath
     */
    private $xpath = null;
    
    /**
     * Flag to indicate if array indicies are to be used as part
     * of the path.
     * @var boolean
     */
    private $arrayNotation = false;
    
    /**
     * Flag to indicate if the regex is designed to use namespaces
     * or not.  Set to false to ignore namespaces in comparison.
     * @var string
     */
    private $useNamespaces = true;
    private $elementNameFn = "name";
    
    /**
     * Flag to indicate if output should be stripped of namespces.
     * This incurrs an overhead as the processing is not trivial.
     * @var string
     */
    private $outputNamespace = true;
    
    /**
     * Flag which allows code to halt main process() loop.
     * @var string
     */
    private $terminate = false;
    
    /**
     * Read through the document and process the matches.
     * @return void
     * @throws \TypeError
     */
    public function process (array $dataMatch) : void   {
        $this->dataMatch = $dataMatch;
        $this->processClosures();
        
        // Create document to use when processing content (if needed).
        if ( $this->doc == null )   {
            $this->doc = new DOMDocument();
        }
        $this->xpath = new DOMXPath($this->doc);
        $this->terminate = false;
        while (!$this->terminate && parent::read()) {
            if ($this->nodeType == XMLReader::ELEMENT )    {
                $this->label = $this->{$this->elementNameFn};
                $this->startElement();
            }   else if ($this->nodeType == XMLReader::END_ELEMENT )    {
                $this->endElement();
            }
        }
    }
    
    /**
     * Set flag to indicate if the array index is to be used as
     * part of the matching process.
     * @param bool $arrayNotation
     */
    public function setArrayNotation ( bool $arrayNotation ): void  {
        $this->arrayNotation = $arrayNotation;
    }
    
    /**
     * Set the document used for creating the content.  This allows
     * the content to be owned by a user defined document.
     * @param DOMDocument $doc
     */
    public function setDocument ( DOMDocument $doc ): void  {
        $this->doc = $doc;
    }
    
    /**
     * Flag to indicate if namespace prefixes are to be used in the matching.
     * @param bool $useNamespaces
     */
    public function setUseNamespaces ( bool $useNamespaces ): void  {
        $this->useNamespaces = $useNamespaces;
        if ( $useNamespaces )   {
            $this->elementNameFn = "name";
        }
        else    {
            $this->elementNameFn = "localName";
        }
    }
    
    /**
     * Define if the output should contain namespaces.
     * Note that there is an overhead in processing this.
     * @param bool $outputNamespace
     */
    public function setOutputNamespaces ( bool $outputNamespace ) : void {
        $this->outputNamespace = $outputNamespace;
    }
    
    /**
     * Call to flag main process loop to stop.
     */
    public function flagToStop () : void   {
        $this->terminate = true;
    }
    
    /**
     * Called to work out what each closure should be passed.  So for
     *  function (\SimpleXMLElement $data)
     * the code will ensure that it is passed a SimpleXMLElement.
     * @throws \TypeError
     */
    private function processClosures() {
        $this->callableParam = [];
        $returnMethods = ['SimpleXMLElement' => "returnSimpleXML",
            'DOMElement' => "returnDOMElement",
            'string' => "returnString",
            '' => "returnString"
        ];
        
        foreach ( $this->dataMatch as $key => $callable )    {
            $reflection = new ReflectionFunction($callable);
            $argType = $reflection->getParameters()[0]->getType();
            if ( $argType != null ) {
                $argType = $argType->getName();
            }
            else    {
                $argType = 'string';
            }
            // Set element associated closure according to type
            if ( isset($returnMethods[$argType]) )  {
                $this->callableParam[ $key ] = $returnMethods[$argType];
            }
            else {
                throw new \TypeError("Cannot pass value to callback as type ".$argType);
            }
        }
    }
    
    /**
     * Start of XML element.
     * Also checks for self terminated elements.
     */
    protected function startElement(): void    {
        $this->path[] = $this->label;
        $this->pathIndex[][$this->label] = 0;
        $this->pathLength++;

        $path = $this->getPath();
        // See if interesed in this data
        foreach ( $this->dataMatch as $pathReg => $closure ) {
            // Check if matches regex...
            $matches = [];
            if ( preg_match('#^'.$pathReg.'$#', $path, $matches) ) {
                $closure($this->{$this->callableParam [ $pathReg ]}(), $matches );
            }
        }

        // For self terminated elements, ensure end element is called.
        if ( $this->isEmptyElement )    {
            $this->endElement();
        }
    }

    /**
     * Call at end of element to do house keeping.
     */
    protected function endElement(): void    {
        $label = array_pop($this->path);
        $this->label = $label;
        array_pop($this->pathIndex);
        $this->pathLength--;
        
        $this->pathIndex[$this->pathLength][$label] =
                ($this->pathIndex[$this->pathLength][$label] ?? 0) + 1;
    }

    /**
     * Returns the path at the current element.  This is made up
     * of both the path and any array indicies.
     */
    private function getPath(): string    {
        $path = '';
        foreach ($this->path as $key => $pathElement) {
            $path .= '/'.$pathElement;
            // Add in array index if needed
            if ($this->arrayNotation &&
                    ($this->pathIndex[$key][$pathElement] ?? 0) > 0) {
                $path .= '['.$this->pathIndex[$key][$pathElement].']';
            }
        }

        return $path;
    }
    
    /**
     * Routine used for function (\SimpleXMLElement $data) callbacks
     * @return SimpleXMLElement
     */
    private function returnSimpleXML () : \SimpleXMLElement {
        return simplexml_import_dom($this->returnDOMElement());
    }
    
    /**
     * Routine used for function (\DOMElement $data) callbacks
     * @return DOMElement
     */
    private function returnDOMElement () : \DOMElement {
        $dom = $this->doc->importNode($this->expand(), true);
        if ( $this->outputNamespace == false )  {
            $this->doc->appendChild($dom);
            $namespaces = $this->xpath->query("//namespace::*[local-name()!='xml']");
            foreach ( $namespaces as $ns) {
                $ns->parentNode->removeAttributeNS($ns->namespaceURI, $ns->prefix);
            }
            $this->doc->removeChild($dom);
        }
        return $dom;
    }
    
    /**
     * Routine used for function (string $data) or function ($data) callbacks
     * @return string
     */
    private function returnString() : string {
        $output = "";
        if ( $this->outputNamespace == false )  {
            $dom = $this->returnDOMElement();
            foreach ($dom->childNodes as $child)    {
                $output .= $this->doc->saveHTML($child);
            }
            $output = $output;
        }
        else    {
            $output = $this->readInnerXml();
        }
            
        return trim($output);
    }
    
}
