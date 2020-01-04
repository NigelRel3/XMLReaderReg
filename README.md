# XMLReaderReg
An extension of PHP's XMLReader to include a simplified interface.

### Quick Start

Rather than having to use boiler plate code to fetch particular elements, XMLReaderReg allows you to register an interest in certain elements along with a callback.  This effectively changes it from a pull parser to a push parser.

```php
require_once __DIR__ . '/../XMLReaderReg.php';

$inputFile = __DIR__ ."/../tests/data/simpleTest1.xml";
$reader = new XMLReaderReg();
$reader->open($inputFile);

$reader->process([
    // Find all person elements
    '(.*/person(?:\[\d*\])?)' => function (SimpleXMLElement $data, $path): void {
        echo "1) Value for ".$path." is ".PHP_EOL.
            $data->asXML().PHP_EOL;
    },
    // Find the corresponding element in the hierarchy
    '/root/person2/firstname' => function (string $data): void {
        echo "3) Value for /root/person2/firstname is ". $data.PHP_EOL;
    }
    ]);

$reader->close();
```

The main addition is the `process()` method, rather than looping through the document structure, `process()` is passed an array of regex and associated callback elements.  When a particular XML element matches the pattern you are interested in the callback will be passed the data from that element.

#How the document hierarchy is encoded
As the document is loaded, the code builds a simple document hierarchy based on the nesting of the elements.  So...

```xml
<person>
    <firstname>John</firstname>
    <lastname>Doe</lastname>
</person>
```
will produce the following tree...

```
/person
/person/firstname
/person/lastname
```

To allow for multiple elements, this is slightly modified to keep track of the number of elements...

```xml
<root>
    <firstname>John</firstname>
    <firstname>Fred</firstname>
</root>
```
will produce the following tree...

```
/root
/root/firstname
/root/firstname[1]
```
Note that the first instance doesn't get a suffix (as it doesn't yet know there is any more elements of this name) and they start at 1 when added.

The array elements are remembered at any particular level of nesting, so

```xml
<root>
    <firstname>John</firstname>
    <lastname>Doe</lastname>
    <firstname>Fred</firstname>
</root>
```
will produce the following tree...

```
/root
/root/firstname
/root/lastname
/root/firstname[1]
```

### Regex matching
The matching process is as simple as working out where the data you want lies in the document.  You can be as explicit or as vague as you wish using regex's ability to match the content of the above hierarchy.

From the quick start sample code...

```
/root/person2/firstname
```
directly matches an element in the hierarchy, whereas  

```
.*/person(?:\[\d*\])?
```
will find any `<person>` element and allow an optional suffix for use when multiple elements are present.

Also something useful in regex's is capture groups,  notice that this last regex is actually `(.*/person(?:\[\d*\])?)` in the code.  The capture groups will be passed to the callback.

#The callback function
The basic callback function definition is

```
function (mixed $data[, mixed $path]): void {}
```

**data** 

The data content of the matching element.  This can be type hinted to a `string`, `SimpleXMLElement` or `DOMElement`.

In this callback,

```php
function ( $data ) {}
```

as there is no typehint for the callback value, it will be passed the results of [readInnerXml()](https://www.php.net/manual/en/xmlreader.readinnerxml.php) which is a string containing just the contents of the XML element.

There are a couple of alternatives which are more specific...

```php
// same as above, just with a type hint
function ( string $data ) {}

// The element is passed as a SimpleXMLelement
function ( \SimpleXMLElement $data ) {}

// The element is passed as a DOMElement
function ( \DOMElement $data ) {}
```
the last 2 allow you to fetch the content in a more accessible format if you need to do any further processing.

For `DOMElement` the equivalent of `$reader->importNode($reader->expand(), true)` is passed.

For `SimpleXMLElement` the equivalent of `simplexml_import_dom(importNode($reader->expand(), true))` is passed.

**path** 

The capture group(s) from the regex.

If you don't use capture groups, you can omit the `$path` parameter.  If you do use capture groups, then it will pass an array which is the return value of `$matches` from [preg_match()](https://www.php.net/manual/en/function.preg-match.php) which is used internally to check the path against the regex patterns.

## Options

### DOM Document owner

```php
public function setDocument ( DOMDocument $doc ): void;
```
When using DOMDocument, the owner of a created node can be important.  If you want to control this, then create your own instance of DOMDocument and pass that to this call.  Any subsequently generated nodes passed to callbacks will be owned by this document.

If this is not called, all nodes will be owned by an internally created document.

#Namespace usage - Matching

```php
public function setUseNamespaces ( bool $useNamespaces ): void;
```
Flag to indicate if the path is built with namespaces or not.  By default, this flag is set to `true` and will use namespaces where defined in the document.

With

```xml
<a:root xmlns:a="http://someurl.com">
    <a:person>
    ...
```
set to `true`, it will generate a path hierarchy of

```
/a:root
/a:root/a:person
```
set to `false`, it will generate a path hierarchy of

```
/root
/root/person
```
### Namespace usage - Output

```php
public function setOutputNamespaces ( bool $outputNamespace ) : void;
```
If you don't need/want the namespaces in the output, calling this with `false` will remove all namespaces from the output.  This includes the definition and any namespaces prefixes from the nodes.

Due to the processing this will incur an overhead.

### Configuring array notation
By default array notation is turned off, this will present duplicated elements as

```
/root
/root/firstname
/root/firstname
```
This removes the need to include a regex to match the optional array index in (for example) `(.*/person(?:\[\d*\])?)` and just use `(.*/person)` to retrieve every `<person>` element.

In some cases you may not need to know which instance of an element is being processed, this allows you to extract a specific instance or simply to know from the path what instance is being processed.   

```php
public function setArrayNotation ( bool $arrayNotation ): void;
```
Calling this with `false` will stop the generation of array indicies when matching is done.  So from the above example the path will look like the following...

```
/root
/root/firstname
/root/firstname[1]
```
### Stop the processing

```php
public function flagToStop () : void;
```
During a callback, you may decide that you do not need to process any more of the content, this method will flag the `process()` method to stop at the next iteration.

This can be done something like...

```PHP
function (DOMElement $data, $path)
                    use ($reader): void {
    // process $data
    $reader->flagToStop();
}
```
## Examples
examples/XMLReaderBasic.php has a brief set of examples on how to use XMLReaderReg

## Tests
tests/XMLReaderRegTest.php is a PHPUnit test set for XMLReaderReg.

Please note that `testFetchLargeFullRead` reads a 25MB XML file so will take some time to complete.
## License
Please see the LICENSE file.
