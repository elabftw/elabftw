<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Models;

class SchedulerTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $Users = new Users(1, 1);
        $Database = new Database($Users, 1);
        $this->Scheduler = new Scheduler($Database);
        $this->id = 1;
        $this->delta = array(
            'years' => '0',
            'months' => '0',
            'days' => '1',
            'milliseconds' => '0',
        );
    }

    public function testCreate()
    {
        $this->id = $this->Scheduler->create('2016-07-22T19:42:00+02:00', '2016-07-23T19:42:00+02:00', 'Yep');
    }

    /** FIXME got error call to a member functio on bool with this
    public function testUpdateStart()
    {
        $this->Scheduler->setId($this->id);
        $this->Scheduler->updateStart($this->delta);
    }
    */

    /** FIXME got error call to a member functio on bool with this
    public function testUpdateEnd()
    {
        $this->Scheduler->setId($this->id);
        $this->Scheduler->updateEnd($this->delta);
    }
     */
    public function testDestroy()
    {
        $id = $this->Scheduler->create('2016-07-22T19:42:00+02:00', '2016-07-23T19:42:00+02:00', 'Yep');
        $this->Scheduler->setId($id);
        $this->Scheduler->destroy();
    }
}
