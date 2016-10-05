<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site;

use Pantheon\Terminus\Commands\Site\DeleteCommand;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Test suite for class for Pantheon\Terminus\Commands\Site\DeleteCommand
 */
class DeleteCommandTest extends CommandTestCase
{

    /**
     * Test suite setup
     *
     * @return void
     */
    protected function setup()
    {
        parent::setUp();
        $this->command = new DeleteCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }

    /**
     * Exercises the site:delete command
     */
    public function testDelete()
    {
        $site_name = 'my-site';

        $this->site->expects($this->once())
            ->method('delete')
            ->with();
        $this->logger->expects($this->once())
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo("Deleted {site} from Pantheon"),
                $this->equalTo(['site' => $site_name,])
            );

        $out = $this->command->delete($site_name);
        $this->assertNull($out);
    }


    /**
     * Exercises the site:delete command when Site::delete fails to ensure message gets through
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Error message
     */
    public function testDeleteFailure()
    {
        $site_name = 'my-site';

        $this->site->expects($this->once())
            ->method('delete')
            ->with()
            ->will($this->throwException(new \Exception('Error message')));
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->delete($site_name);
        $this->assertNull($out);
    }
}
