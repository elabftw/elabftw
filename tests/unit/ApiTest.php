<?php
namespace Elabftw\Elabftw;

use PDO;

class ApiTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $Users = new Users(1);
        $Users->generateApiKey();
        $Users->populate();
        $this->key = $Users->userData['api_key'];
        $request = 'experiments/1';
        $this->Api= new Api($this->key, 'GET', $request);
    }

    public function testGetEntity()
    {
        $this->assertTrue(is_array($this->Api->getEntity()));
        $request = 'items/1';
        $this->Api= new Api($this->key, 'GET', $request);
        $this->setExpectedException('Exception');
        $request = 'database/1';
        $this->Api= new Api($this->key, 'GET', $request);
        $this->setExpectedException('Exception');
        $request = 'items/1';
        $this->Api= new Api($this->key, 'PUT', $request);
    }

    public function testUpdateEntity()
    {
        $request = 'experiments';
        $this->Api= new Api($this->key, 'POST', $request);
        $this->setExpectedException('Exception');
        $this->Api->updateEntity();
    }
}
