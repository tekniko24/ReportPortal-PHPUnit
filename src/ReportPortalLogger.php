<?php
/**
 * Created by Luis Cinco.
 * PHP Unit ReportPortal Agent using Events System
 */
namespace ReportPortal\PHPUnitAgent;

use ReportPortalBasic\Service\ReportPortalHTTPService;
use GuzzleHttp\Psr7\Response as Response;

class ReportPortalLogger
{
    private $APIKey;
    private $projectName;
    private $host;
    private $timeZone;
    private $launchName;
    private $launchDescription;
    private static $className;
    private static $classDescription;
    private static $testName;
    private static $rootItemID;
    private static $testItemID;
    private static $currentItemID;
    private static $suiteCounter = 0;
    private static $testresult;

    /**
     * @var ReportPortalHTTPService
     */
    protected static $RPTestRun;

    /**
     * ReportPortalLogger constructor.
     * @param $APIKey
     * @param $host
     * @param $projectName
     * @param $timeZone
     * @param $launchName
     * @param $launchDescription
     */
    public function __construct($APIKey, $projectName, $host, $timeZone, $launchName, $launchDescription)
    {
        $this->APIKey = $APIKey;
        $this->host = $host;
        $this->projectName = $projectName;
        $this->timeZone = $timeZone;
        $this->launchName = $launchName;
        $this->launchDescription = $launchDescription;

        $this->configureClient();
        self::$RPTestRun->launchTestRun($this->launchName, $this->launchDescription, ReportPortalHTTPService::DEFAULT_LAUNCH_MODE, []);
    }
    /**
     * ReportPortalLogger destructor.
     */
    public function __destruct()
    {
        $status = self::getStatusByBool(true);
        $HTTPResult = self::$RPTestRun->finishTestRun($status);
        self::$RPTestRun->finishAll($HTTPResult);
    }
    private function configureClient()
    {
        $isHTTPErrorsAllowed = false;
        $baseURI = sprintf(ReportPortalHTTPService::BASE_URI_TEMPLATE, $this->host);
        ReportPortalHTTPService::configureClient($this->APIKey, $baseURI, $this->host, $this->timeZone, $this->projectName, $isHTTPErrorsAllowed);
        self::$RPTestRun = new ReportPortalHTTPService();
    }
    protected function getTime()
    {
        return date('Y-m-d\TH:i:s');
    }
    /**
     * @param bool $isFailedItem
     * @return string
     */
    private static function getStatusByBool(bool $isFailedItem)
    {
        if ($isFailedItem) {
            $stringItemStatus = "FAILED";
        } else {
            $stringItemStatus = "PASSED";
        }
        return $stringItemStatus;
    }
    /**
     * Is a suite without name
     *
     * @param $suite
     * @return bool
     */
    private static function isNoNameSuite($suite):bool
    {
        return $suite->testSuite()->name() !== "";
    }

    /**
     * Get ID from response
     *
     * @param Response $HTTPResponse
     * @return string
     */
    private static function getID(Response $HTTPResponse)
    {
        return json_decode($HTTPResponse->getBody(), true)['id'];
    }

// EVENTS Handlers

    public function startTestSuite($event)
    {
        if (strpos($event->testSuite()->name(), 'phpunit.xml') === false)
        {
            self::$suiteCounter++;
            if (self::$suiteCounter == 1)
            {
                $suiteName = $event->testSuite()->name();
                self::$classDescription = '';
                $response = self::$RPTestRun->createRootItem($suiteName,'',[]);
                self::$rootItemID = self::getID($response);
                
            } 
            elseif (self::$suiteCounter > 1)
            {
                self::$className = $event->testSuite()->name();
                self::$classDescription = '';
                $response = self::$RPTestRun->startChildItem(self::$rootItemID, self::$classDescription, self::$className, "SUITE", []);
                self::$currentItemID = self::getID($response);
            }
        }
    }

    public function endTestSuite($event)
    {
        if (self::isNoNameSuite($event))
        {
            self::$suiteCounter--;
            if (self::$suiteCounter == 0)
            {
                self::$RPTestRun->finishRootItem();
            } elseif (self::$suiteCounter >= 1)
            {
                self::$RPTestRun->finishItem(self::$currentItemID, "PASSED", self::$classDescription);
            }
            
        }
    }

    public function startTest($event)
    {
        self::$testresult = 'UNKNOWN';
        self::$testName = $event->test()->name();
        self::$classDescription = '';
        $response = self::$RPTestRun->startChildItem(self::$currentItemID, self::$classDescription = '', self::$testName, "TEST", []);
        self::$testItemID = self::getID($response);
    }

    public function endTest($test, $time)
    {
        $testStatus = self::$testresult;
        self::$RPTestRun->finishItem(self::$testItemID, $testStatus, $time);
    }

    public function testErrored($event)
    {
        self::$testresult = "FAILED";
        $loglevel = "FATAL";
        self::$RPTestRun->addLogMessage(
            self::$testItemID,
            $event->throwable()->message(),
            $loglevel);

        self::$RPTestRun->addLogMessage(
            self::$testItemID,
            $event->throwable()->stackTrace(),
            $loglevel);
    }

    public function testFailed($event)
    {
        self::$testresult = "FAILED";
        $loglevel = "ERROR";
        self::$RPTestRun->addLogMessage(
            self::$testItemID,
            $event->throwable()->message(),
            $loglevel);
    }

    public function testSkipped($event)
    {
        // TODO: Add Log Messages to Test Skipped Event
        self::$testresult = "SKIPPED";
        $loglevel = "INFO";
    }

    public function testPassed($event)
    {
        // TODO: Add Log Messages to Test Passed Event
        self::$testresult = "PASSED";
        $loglevel = "INFO";
    }

    public function testIncomplete($event)
    {
        // TODO: Add Log Messages to Incomplete Test Event
        self::$testresult = "SKIPPED";
        $loglevel = "WARN";
    }

// Trace Handler
    public function trace($event)
    {
        // Capture Events Relevant to Report Portal
        if (str_contains($event->asString(), "Test Suite Started"))
        {
            $this->startTestSuite($event);
        }
        elseif (str_contains($event->asString(), "Test Suite Finished"))
        {
            $this->endTestSuite($event);
        }
        elseif (str_contains($event->asString(), "Test Preparation Started"))
        {
            $this->startTest($event);
        }
        elseif (str_contains($event->asString(), "Test Finished"))
        {
            $this->endTest($event, self::getTime());
        }
        elseif (str_contains($event->asString(), "Test Errored"))
        {
            $this->testErrored($event);
        }
        elseif (str_contains($event->asString(), "Test Failed"))
        {
            $this->testFailed($event);
        }
        elseif (str_contains($event->asString(), "Test Skipped"))
        {
            $this->testSkipped($event);
        }
        elseif (str_contains($event->asString(), "Test Passed"))
        {
            $this->testPassed($event);
        }
        elseif (str_contains($event->asString(), "Test Marked Incomplete"))
        {
            $this->testIncomplete($event);
        }
        // Log Event as String to the Current Item ID
        elseif (!empty(self::$testItemID)){
            self::$RPTestRun->addLogMessage(self::$testItemID,$event->asString(),"TRACE");
        }
    }
}
