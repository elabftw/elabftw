<?php declare(strict_types=1);

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Apiv2 extends \Codeception\Module
{

    // HOOK: after suite
    // wait a bit for the remote merge to finish before requesting the reports
    // This will avoid a truncated report which can happen when a report is requested
    // before the shutdown function is completed and/or the report generation is requested before each test is finished
    public function _afterSuite()
    {
        sleep(5);
    }
}
