<?php
require_once __DIR__ . '/../vendor/autoload.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../XMLReaderReg.php';

$inputFile = __DIR__ ."/../tests/data/simpleTest1.xml";
$reader = new XMLReaderReg();
$reader->open($inputFile);

$reader->process([
    '(.*/person(?:\[\d*\])?)' => function (SimpleXMLElement $data, $path): void {
        echo "1) Value for ".$path[1]." is ".PHP_EOL.
            $data->asXML().PHP_EOL;
    },
    '(.*/person3(\[\d*\])?)' => function (DOMElement $data, $path): void {
        echo "2) Value for ".$path[1]." is ".PHP_EOL.
            $data->ownerDocument->saveXML($data).PHP_EOL;
    },
    '/root/person2/firstname' => function (string $data): void {
        echo "3) Value for /root/person2/firstname is ". $data.PHP_EOL;
    }
    ]);

$reader->close();

echo PHP_EOL.PHP_EOL;
echo "Without array notation...".PHP_EOL;
$reader->open($inputFile);

$reader->setArrayNotation(false);
$reader->process([
    '(.*/person)' => function (SimpleXMLElement $data, $path): void {
        echo "4) Value for ".$path[1]." is ".PHP_EOL.
        $data->asXML().PHP_EOL;
    },
    '(.*/person3)' => function (DOMElement $data, $path): void {
        echo "5) Value for ".$path[1]." is ".PHP_EOL.
        $data->ownerDocument->saveXML($data).PHP_EOL;
    },
    '/root/person2/firstname' => function ($data): void {
        echo "6) Value for /root/person2/firstname is ". $data.PHP_EOL;
    }
    ]);

$reader->close();


echo PHP_EOL.PHP_EOL;
echo "With namespaces, but not in output...".PHP_EOL;
$inputFile = __DIR__ ."/../tests/data/NameSpaceTest1.xml";
$reader->setArrayNotation(true);
$reader->setUseNamespaces(true);
$reader->setOutputNamespaces(false);
$reader->open($inputFile);

$reader->setArrayNotation(false);
$reader->process([
    '(.*/d:person4)' => function (SimpleXMLElement $data, $path): void {
        echo "4) Value for ".$path[1]." is ".PHP_EOL.
        $data->asXML().PHP_EOL;
        }
    ]);

$reader->close();

echo PHP_EOL.PHP_EOL;
echo "Without namespaces...".PHP_EOL;
$reader->setArrayNotation(true);
$reader->setUseNamespaces(false);
$reader->open($inputFile);

$reader->setArrayNotation(false);
$reader->process([
    '(.*/person)' => function (SimpleXMLElement $data, $path): void {
        echo "4) Value for ".$path[1]." is ".PHP_EOL.
        $data->asXML().PHP_EOL;
        }
    ]);

$reader->close();

echo PHP_EOL.PHP_EOL;
echo "Read XML and stop after first person3 element...".PHP_EOL;
$reader->open($inputFile);

$reader->process([
    '(.*/person(\[\d*\])?)' => function (SimpleXMLElement $data, $path): void {
        echo "1) Value for ".$path[1]." is ".PHP_EOL.
                $data->asXML().PHP_EOL;
    },
    '(.*/person3(\[\d*\])?)' => function (DOMElement $data, $path)
                    use ($reader): void {
        echo "2) Value for ".$path[1]." is ".PHP_EOL.
        $data->ownerDocument->saveXML($data).PHP_EOL;
        $reader->flagToStop();
    },
    '/root/person2/firstname' => function (string $data): void {
        echo "3) Value for /root/person2/firstname is ". $data.PHP_EOL;
    }
    ]);

$reader->close();

