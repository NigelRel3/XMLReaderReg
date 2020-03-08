<?php
use PHPUnit\Framework\TestCase;
use XMLReaderReg\XMLReaderReg;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class XMLReaderRegTest extends TestCase
{
    public function testFetchFirstname ()    {
        $inputFile = __DIR__ ."/data/simpleTest1.xml";
        $reader = new XMLReaderReg();
        $reader->open($inputFile);
        
        $firstName ='';
        $reader->process([
            '/root/person2/firstname' => function ($data) use (&$firstName): void {
                $firstName =  $data;
            }
            ]);
        $reader->close();
        
        $this->assertEquals ($firstName, "John3");
        
    }
 
    public function testFetchFirstnamesArray ()    {
        $inputFile = __DIR__ ."/data/simpleTest1.xml";
        $reader = new XMLReaderReg();
        $reader->open($inputFile);
        
        $firstNameList =[];
        $reader->process([
            '(.*/firstname)' => function ($data, $path) use (&$firstNameList): void {
                $firstNameList[$path[1]] =  $data;
            }
            ]);
        $reader->close();
        
        $matchList = ['/root/person/firstname' => 'John',
            '/root/person2/firstname' => 'John3',
            '/root/person3/firstname' => 'John1',
            '/root/person/firstname' => 'John32',
        ];
        
        $this->assertEquals ($firstNameList, $matchList);
    }
   
    public function testFetchOuterFirstnamesArray ()    {
        $inputFile = __DIR__ ."/data/simpleTest1.xml";
        $reader = new XMLReaderReg();
        $reader->open($inputFile);
        
        $firstNameList =[];
        $reader->process([
            '(.*/firstname)' => function ($data, $path) use (&$firstNameList, $reader): void {
                $firstNameList[$path[1]] =  $reader->readOuterXML();
            }
            ]);
        $reader->close();
        
        $matchList = ['/root/person/firstname' => '<firstname>John</firstname>',
            '/root/person2/firstname' => '<firstname>John3</firstname>',
            '/root/person3/firstname' => '<firstname>John1</firstname>',
            '/root/person/firstname' => '<firstname>John32</firstname>',
        ];
        
        $this->assertEquals ($firstNameList, $matchList);
    }
    
    public function testFetchFirstnamesNoArray ()    {
        $inputFile = __DIR__ ."/data/simpleTest1.xml";
        $reader = new XMLReaderReg();
        $reader->open($inputFile);
        $reader->setArrayNotation(false);
        
        $firstNameList =[];
        $reader->process([
            '(.*/firstname)' => function ($data, $path) use (&$firstNameList): void {
                $firstNameList[$path[1]] =  $data;
            }
            ]);
        $reader->close();
        
        $matchList = ['/root/person/firstname' => 'John',
            '/root/person2/firstname' => 'John3',
            '/root/person3/firstname' => 'John1',
            '/root/person/firstname' => 'John32',
        ];
        
        $this->assertEquals ($firstNameList, $matchList);
    }

    public function testFetchPersonString ()    {
        $inputFile = __DIR__ ."/data/simpleTest1.xml";
        $reader = new XMLReaderReg();
        $reader->open($inputFile);
        $reader->setArrayNotation(true);
        
        $firstNameList =[];
        $reader->process([
            '(.*/person(?:\[\d*\])?)' => function ($data, $path) use (&$firstNameList): void {
                $firstNameList[$path[1]] =  $data;
            }
            ]);
        $reader->close();
        
        $matchList = ['/root/person' => '<firstname>John</firstname>
        <lastname>Doe</lastname>',
            '/root/person[1]' => '<firstname>John32</firstname>
        <lastname>Doe3</lastname>'
        ];
        
        $this->assertEquals ($firstNameList, $matchList);
    }
    
    public function testFetchPersonStringMultiCapture ()    {
        $inputFile = __DIR__ ."/data/simpleTest1.xml";
        $reader = new XMLReaderReg();
        $reader->open($inputFile);
        $reader->setArrayNotation(true);
        
        $firstNameList =[];
        $reader->process([
            '(.*/person)(\[\d*\])?' => function ($data, $path) use (&$firstNameList): void {
                $firstNameList[] =  ["data" => $data, "capt" => $path];
            }
            ]);
        $reader->close();
        
        $matchList = [[ 'data' => '<firstname>John</firstname>
        <lastname>Doe</lastname>',
            'capt' => ['/root/person', '/root/person']],
            [ 'data' =>  '<firstname>John32</firstname>
        <lastname>Doe3</lastname>',
                'capt' => [ '/root/person[1]', '/root/person', '[1]' ]]
        ];
        
        $this->assertEquals ($firstNameList, $matchList);
    }
    
    public function testFetchPersonSimpleXMLElement ()    {
        $inputFile = __DIR__ ."/data/simpleTest1.xml";
        $reader = new XMLReaderReg();
        $reader->open($inputFile);
        $reader->setArrayNotation(true);
        
        $xml =[];
        $reader->process([
            '(.*/person(?:\[\d*\])?)' => function (\SimpleXMLElement $data, $path) use (&$xml): void {
                $xml[$path[1]] =  $data;
            }
            ]);
        $reader->close();
        
        $matchList = ['/root/person' => new SimpleXMLElement('<person><firstname>John</firstname>
        <lastname>Doe</lastname></person>'),
            '/root/person[1]' => new SimpleXMLElement('<person><firstname>John32</firstname>
        <lastname>Doe3</lastname></person>')
        ];
        
        $this->assertEquals ($xml, $matchList);
    }
    
    public function testFetchPersonDOMElement ()    {
        $inputFile = __DIR__ ."/data/simpleTest1.xml";
        $reader = new XMLReaderReg();
        $reader->open($inputFile);
        $reader->setArrayNotation(true);
        
        $firstNameList =[];
        $reader->process([
            '(.*/person(?:\[\d*\])?)' => function (\DOMElement $data, $path) use (&$firstNameList): void {
                $firstNameList[$path[1]] =  $data->ownerDocument->saveXML($data);
            }
            ]);
        $reader->close();
        
        $doc = new DOMDocument();
        $doc->load($inputFile);
        $people = $doc->getElementsByTagName("person");
        $matchList = ["/root/person" => $doc->saveXML($people[0]),
            "/root/person[1]" => $doc->saveXML($people[1])
        ];
      
        $this->assertEquals ($matchList, $firstNameList);
    }
  
    public function testFetchPersonDOMElementOwner ()    {
        $inputFile = __DIR__ ."/data/simpleTest1.xml";
        $reader = new XMLReaderReg();
        $reader->open($inputFile);
        $doc = new DOMDocument();
        $reader->setDocument($doc);
        $reader->setArrayNotation(false);
        
        $firstNameList =[];
        $reader->process([
            '(.*/person)' => function (\DOMElement $data, $path) use (&$firstNameList, $doc): void {
                $firstNameList[$path[1]] =  [$doc->saveXML($data), $doc];
            }
            ]);
        $reader->close();
        
        $doc->load($inputFile);
        $people = $doc->getElementsByTagName("person");
        $matchList = ["/root/person" => [$doc->saveXML($people[0]), $doc],
            "/root/person" => [$doc->saveXML($people[1]), $doc]
        ];
        
        $this->assertEquals ($matchList, $firstNameList);
    }
  
    public function testFetchPersonTypeException ()    {
        $inputFile = __DIR__ ."/data/simpleTest1.xml";
        $reader = new XMLReaderReg();
        $reader->open($inputFile);
        $doc = new DOMDocument();
        $reader->setDocument($doc);
        $reader->setArrayNotation(false);
        
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage("Cannot pass value to callback as type int");
        $firstNameList =[];
        $reader->process([
            '(.*/person)' => function (int $data, $path) use (&$firstNameList, $doc): void {
                $firstNameList[$path[1]] =  [$doc->saveXML($data), $doc];
            }
            ]);
        $reader->close();
    }
    
    /**
     * Testing for self closed element.
     */
    public function testFetchPerson4SimpleXMLElement ()    {
        $inputFile = __DIR__ ."/data/simpleTest1.xml";
        $reader = new XMLReaderReg();
        $reader->open($inputFile);
        
        $xml =[];
        $reader->process([
            '(.*/person4(?:\[\d*\])?)' => function (\SimpleXMLElement $data, $path) use (&$xml): void {
                $xml[$path[1]] =  $data;
            }
            ]);
        $reader->close();
        
        $matchList = ['/root/person4' => new SimpleXMLElement('<person4 id="12" />') ];
        
        $this->assertEquals ($xml, $matchList);
    }
    
    public function testFetchFirstnameNS ()    {
        $inputFile = __DIR__ ."/data/NameSpaceTest1.xml";
        $reader = new XMLReaderReg();
        $reader->setUseNamespaces(true);
        $reader->open($inputFile);
        
        $aNSfirstName =[];
        $nNSfirstName =[];
        $reader->process([
            '(.*/a:firstname)' => function ($data) use (&$aNSfirstName): void {
                $aNSfirstName[] =  $data;
            },
            '(.*/firstname)' => function ($data) use (&$nNSfirstName): void {
                $nNSfirstName[] =  $data;
            },
            ]);
        $reader->close();
        
        $this->assertEquals ($aNSfirstName, ["John"]);
        $this->assertEquals ($nNSfirstName, ["John3" , "John1" , "John32"]);
        
    }
    
    public function testFetchFirstnameNSFalse ()    {
        $inputFile = __DIR__ ."/data/NameSpaceTest1.xml";
        $reader = new XMLReaderReg();
        $reader->setUseNamespaces(false);
        $reader->open($inputFile);
        
        $firstName =[];
        $streetName =[];
        $reader->process([
            '(.*/firstname)' => function ($data) use (&$firstName): void {
                $firstName[] =  $data;
            },
            '(.*/street)' => function ($data) use (&$streetName): void {
            $streetName[] =  $data;
            }
            ]);
        $reader->close();
        
        $this->assertEquals ($firstName, ["John" , "John3" , "John1" , "John32"]);
        $this->assertEquals ($streetName, ["Streetvalue" , "Streetvalueb", "Streetvalued"]);
    }
    
    public function testFetchperson2NS ()    {
        $inputFile = __DIR__ ."/data/NameSpaceTest1.xml";
        $reader = new XMLReaderReg();
        $reader->setUseNamespaces(true);
        $reader->open($inputFile);
        
        $nNSperson2 =[];
        $bNSperson2 =[];
        $reader->process([
            '(.*/person2)' => function ($data) use (&$nNSperson2): void {
                $nNSperson2[] =  $data;
            },
            '(.*/b:person2)' => function ($data) use (&$bNSperson2): void {
                $bNSperson2[] =  $data;
            },
            ]);
        $reader->close();
        
        $this->assertEquals ($nNSperson2, ["<firstname>John3</firstname>
        <lastname>Doe3</lastname>"]);
        $this->assertEquals ($bNSperson2, ['<b:street xmlns:b="http://someurl2.com">Streetvalueb</b:street>
        <b:city xmlns:b="http://someurl2.com">NYC</b:city>
        <b:region xmlns:b="http://someurl2.com">NY</b:region>']);
        
    }
    
    public function testFetchperson2NoOutputNS ()    {
        $inputFile = __DIR__ ."/data/NameSpaceTest1.xml";
        $reader = new XMLReaderReg();
        $reader->setUseNamespaces(true);
        $reader->setOutputNamespaces(false);
        $reader->open($inputFile);
        
        $nNSperson2 =[];
        $bNSperson2 =[];
        $reader->process([
            '(.*/person2)' => function ($data) use (&$nNSperson2): void {
            $nNSperson2[] =  $data;
            },
            '(.*/b:person2)' => function (\SimpleXMLElement $data) use (&$bNSperson2): void {
            $bNSperson2[] =  $data->asXML();
            },
            ]);
        $reader->close();
        
        $this->assertEquals ($nNSperson2, ["<firstname>John3</firstname>
        <lastname>Doe3</lastname>"]);
        $this->assertEquals ($bNSperson2, ['<person2>
        <street>Streetvalueb</street>
        <city>NYC</city>
        <region>NY</region>
    </person2>']);
        
    }

    public function testFetchperson4NoOutputNestedNS ()    {
        $inputFile = __DIR__ ."/data/NameSpaceTest1.xml";
        $reader = new XMLReaderReg();
        $reader->setUseNamespaces(false);
        $reader->setOutputNamespaces(false);
        $reader->open($inputFile);
        
        $nNSperson4 =[];
        $reader->process([
            '(.*/person4)' => function (\SimpleXMLElement $data) use (&$nNSperson4): void {
                $nNSperson4[] =  $data->asXML();
            },
            ]);
        $reader->close();
        
        $this->assertEquals ($nNSperson4, ['<person4>
        <street>Streetvalued</street>
        <city>NYC</city>
        <city1>
            <shortname>NYC</shortname>
            <longname>New York</longname>
        </city1>
        <region>NY</region>
    </person4>']);
    }
  
    public function testFetchperson4NoOutputNestedNSString ()    {
        $inputFile = __DIR__ ."/data/NameSpaceTest1.xml";
        $reader = new XMLReaderReg();
        $reader->setUseNamespaces(false);
        $reader->setOutputNamespaces(false);
        $reader->open($inputFile);
        
        $nNSperson4 =[];
        $reader->process([
            '(.*/person4)' => function ($data) use (&$nNSperson4): void {
            $nNSperson4[] =  $data;
            },
            ]);
        $reader->close();
        
        $this->assertEquals ($nNSperson4, ['<street>Streetvalued</street>
        <city>NYC</city>
        <city1>
            <shortname>NYC</shortname>
            <longname>New York</longname>
        </city1>
        <region>NY</region>']);
    }
    
    
    public function testFetchFirstnameDNS ()    {
        $inputFile = __DIR__ ."/data/NameSpaceTestDef.xml";
        $reader = new XMLReaderReg();
        $reader->setUseNamespaces(true);
        $reader->open($inputFile);
        
        $aNSfirstName =[];
        $nNSfirstName =[];
        $reader->process([
            '(.*/a:firstname)' => function ($data) use (&$aNSfirstName): void {
                $aNSfirstName[] =  $data;
            },
            '(.*/firstname)' => function ($data) use (&$nNSfirstName): void {
                $nNSfirstName[] =  $data;
            },
            ]);
        $reader->close();
        
        $this->assertEquals ($aNSfirstName, []);
        $this->assertEquals ($nNSfirstName, ["John", "John3" , "John1"]);
        
    }
  
    public function testFetchPersonDNS ()    {
        $inputFile = __DIR__ ."/data/NameSpaceTestDef.xml";
        $reader = new XMLReaderReg();
        $reader->setUseNamespaces(true);
        $reader->open($inputFile);
        
        $aNSfirstName =[];
        $reader->process([
            '(.*/a:person)' => function ($data) use (&$aNSfirstName): void {
                $aNSfirstName =  $data;
            }
            ]);
        $reader->close();
        
        $this->assertEquals ($aNSfirstName, '<firstname xmlns="http://somedefurl.com">John</firstname>
        <lastname xmlns="http://somedefurl.com">Doe</lastname>');
        
    }
    
    public function testFetchPersonDNSNoNSOutput ()    {
        $inputFile = __DIR__ ."/data/NameSpaceTestDef.xml";
        $reader = new XMLReaderReg();
        $reader->setUseNamespaces(true);
        $reader->setOutputNamespaces(false);
        $reader->open($inputFile);
        
        $aNSfirstName =[];
        $reader->process([
            '(.*/a:person)' => function ($data) use (&$aNSfirstName): void {
            $aNSfirstName =  $data;
            }
            ]);
        $reader->close();
        
        $this->assertEquals ($aNSfirstName, '<firstname>John</firstname>
        <lastname>Doe</lastname>');
        
    }
    
    public function testFetchLargeFullRead ()    {
        $inputFile = "compress.zlib://".__DIR__ ."/data/nasa.xml.gz";
        $reader = new XMLReaderReg();
        $reader->setArrayNotation(false);
        $reader->open($inputFile);
        
        $identifier ='';
        $reader->process([
            '/datasets/dataset/identifier' =>
            function ($data) use (&$identifier): void {
                $identifier =  $data;
            }
            ]);
        $reader->close();
        
        $this->assertEquals ($identifier, "J_PAZh_25_447.xml");
    }
    
    /**
     * Fetch a list of the firstname element values, but stop after
     * the first one.
     */
    public function testFetchFirstnameStop ()    {
        $inputFile = __DIR__ ."/data/simpleTest1.xml";
        $reader = new XMLReaderReg();
        $reader->open($inputFile);
        
        $firstName = [];
        $reader->process([
            '(.*/firstname)' => function ($data) use (&$firstName, $reader): void {
                $firstName[] =  $data;
                $reader->flagToStop();
            }
            ]);
        $reader->close();
        
        $this->assertEquals ($firstName, ["John"]);
        
    }
    
}


