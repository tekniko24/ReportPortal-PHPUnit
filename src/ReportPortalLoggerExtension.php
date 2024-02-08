<?php
/**
 * Created by Luis Cinco.
 * PHP Unit ReportPortal Agent using Events System
 */
namespace ReportPortal\PHPUnitAgent;

use PHPUnit\Runner;
use PHPUnit\TextUI;

class ReportPortalLoggerExtension implements Runner\Extension\Extension
{
    public function bootstrap(
        TextUI\Configuration\Configuration $configuration,
        Runner\Extension\Facade $facade,
        Runner\Extension\ParameterCollection $parameters
    ): void {
        
        if ($parameters->has('APIKey')) {
            $APIKey = $parameters->get('APIKey');
        }
        if ($parameters->has('host')) {
            $host = $parameters->get('host');
        }
        if ($parameters->has('projectName')) {
            $projectName = $parameters->get('projectName');
        }
        if ($parameters->has('timeZone')) {
            $timeZone = $parameters->get('timeZone');
        }
        if ($parameters->has('launchName')) {
            $launchName = $parameters->get('launchName');
        }
        if ($parameters->has('launchDescription')) {
            $launchDescription = $parameters->get('launchDescription');
        }

        $reportPortalLogger = new ReportPortalLogger(
            $APIKey,
            $projectName,
            $host,
            $timeZone,
            $launchName,
            $launchDescription
        );
        $facade->registerTracer(
            new ReportPortalLogTracer($reportPortalLogger)
        );
    }
}
