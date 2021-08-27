<?php declare(strict_types=1);

namespace Helper;

use PHPUnit_Framework_Assert;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends \Codeception\Module
{
    public function seeFileExists($filePath)
    {
        PHPUnit_Framework_Assert::assertTrue(file_exists($filePath));
    }

    public function seeFileIsZip($filePath)
    {
        $ret = $this->runSh('unzip -Z ' . escapeshellarg($filePath));
        PHPUnit_Framework_Assert::assertTrue($ret['retcode'] === 0);
    }

    private function runSh($cmd)
    {
        $retarray = array();
        exec('sh -c "' . $cmd . '" 2>&1', $retarray, $retcode);

        return array(
            'retarray' => $retarray,
            'retcode' => $retcode,
        );
    }
}
