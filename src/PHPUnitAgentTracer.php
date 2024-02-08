<?php declare(strict_types=1);

namespace ReportPortal\PHPUnit;

use PHPUnit\Event\Event;
use PHPUnit\Event\Tracer\Tracer;

final class ReportPortalLogTracer implements Tracer
{
    public function __construct(public ReportPortalLogger $reportPortalLogger)
    {
    }
    public function trace(Event $event): void
    {
        // ...
        $this->reportPortalLogger->trace($event);
    }
}