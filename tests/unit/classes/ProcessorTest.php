<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Models\Users;
use Symfony\Component\HttpFoundation\Request;

class ProcessorTest extends \PHPUnit\Framework\TestCase
{
    private Users $Users;

    protected function setUp(): void
    {
        $this->Users = new Users(1, 1);
    }

    public function testProcessorFactory(): void
    {
        $factory = new ProcessorFactory();

        $getJsonRequest = Request::create(
            '/',
            'GET',
            array('p' => '{"method":"GET","action":"read","model":"todolist"}'),
        );
        $this->assertInstanceOf(GetJsonProcessor::class, $factory->getProcessor($this->Users, $getJsonRequest));

        $postJsonRequest = Request::create(
            '/',
            'POST',
            array(),
            // cookies
            array(),
            // files
            array(),
            // server
            array('CONTENT_TYPE' => 'application/json'),
            '{"method":"POST","action":"destroy","model":"todolist","id":2}',
        );
        $this->assertInstanceOf(PostJsonProcessor::class, $factory->getProcessor($this->Users, $postJsonRequest));

        $postRequest = Request::create(
            '/',
            'POST',
            array(
                'action' => 'update',
                'target' => 'file',
                'entity_id' => '1',
                'entity_type' => 'experiments',
                'id' => '1',
                'model' => 'upload',
            ),
            // cookies
            array(),
            // files
            array('content' => array(
                'name' => 'example.dna',
                'type' => 'application/vnd.dna',
                'tmp_name' => dirname(__DIR__, 2) . '/_data/example.txt',
                'error' => 0,
                'size' => 27050,
            )),
        );
        $this->assertInstanceOf(FormProcessor::class, $factory->getProcessor($this->Users, $postRequest));
    }
}
