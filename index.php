<?php

/** @noinspection PhpUnhandledExceptionInspection */

use qtism\data\AssessmentTest;
use qtism\data\results\AssessmentResult;
use qtism\data\storage\xml\XmlDocument;
use qtism\data\storage\xml\XmlResultDocument;

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';

$inputPath = 'data/input';
$outputPath = 'data/output';

$requestData = [
    'item-1' => [
        'GRAMMAR' => 4,
        'VALUE' => 1,
        'UNIQUENESS' => 5
    ],
    'item-2' => [
        'EXPRESSION' => 4,
        'VALUE' => 3
    ],
];


// read
$xmlTestDocument = new XmlDocument();
$xmlTestDocument->load("$inputPath/assessment-test.xml");
/** @var AssessmentTest $assessmentTest */
$assessmentTest = $xmlTestDocument->getDocumentComponent();

$xmlResultDocument = new XmlResultDocument();
$xmlResultDocument->load("$inputPath/assessment-result.xml");
/** @var AssessmentResult $assessmentResult */
$assessmentResult = $xmlResultDocument->getDocumentComponent();

$assessmentProcessor = new App\AssessmentProcessor($assessmentTest, $assessmentResult);
$assessmentProcessor->updateResultWithScores($requestData);

// write
$xmlResultDocument = new XmlResultDocument();
$xmlResultDocument->setDocumentComponent($assessmentProcessor->getAssessmentResult());
file_put_contents(
    "$outputPath/assessment-result.xml",
    $xmlResultDocument->saveToString()
);

echo 'Processing finished' . PHP_EOL;