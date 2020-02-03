<?php

/** @noinspection PhpUnhandledExceptionInspection */

use qtism\common\datatypes\QtiFloat;
use qtism\data\results\AssessmentResult;
use qtism\data\results\ItemResult;
use qtism\data\results\ResultOutcomeVariable;
use qtism\data\state\Value;
use qtism\data\storage\xml\XmlResultDocument;
use qtism\runtime\processing\OutcomeProcessingEngine;

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'vendor/autoload.php';
$inputTestPath = 'data/input/tests';
$inputResultPath = 'data/input/results';
$outputPath = 'data/output';
$fileName = 'test-1.xml';

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
$xmlDocument = new XmlResultDocument();
$xmlDocument->load("$inputResultPath/$fileName");

/** @var AssessmentResult $assessmentResult */
$assessmentResult = $xmlDocument->getDocumentComponent();

// modify
foreach ($requestData as $itemId => $scores) {
    /** @var ItemResult $itemResult */
    foreach ($assessmentResult->getItemResults() as $itemResult) {
        if ($itemResult->getIdentifier()->getValue() === $itemId) {
            foreach ($itemResult->getItemVariables() as $itemVariable) {
                if (!$itemVariable instanceof ResultOutcomeVariable) {
                    continue;
                }

                $outcomeDeclaration = $itemVariable;

                foreach ($scores as $outcomeId => $score) {
                    if ($outcomeDeclaration->getIdentifier()->getValue() === $outcomeId) {
                        /** @var Value $value */
                        foreach ($outcomeDeclaration->getValues() as $value) {
                            $oldValue = (float) $value->getValue();
                            $value->setValue(new QtiFloat($oldValue + (float) $score));
                        }
                    }
                }
            }
        }

//        $itemProcessingEngine = new OutcomeProcessingEngine($itemResult);
//        $itemProcessingEngine->process();
    }
}

// write
$xmlDocument = new XmlResultDocument();
$xmlDocument->setDocumentComponent($assessmentResult);
file_put_contents("$outputPath/$fileName", $xmlDocument->saveToString());

echo 'Processing finished' . PHP_EOL;