<?php

/*
 * This file is part of the Stomp package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Stomp\Tests\Functional\Generic;

use Stomp\Connection;
use Stomp\Exception\ConnectionException;
use Stomp\Exception\ErrorFrameException;
use Stomp\Frame;

/* vim: set expandtab tabstop=3 shiftwidth=3: */

/**
 * Stomp test case.
 * @package Stomp
 * @author Jens Radtke <swefl.oss@fin-sn.de>
 */
class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    public function testReadFrameThrowsExceptionIfStreamIsBroken()
    {
        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->getMockBuilder('\Stomp\Connection')
            ->setMethods(array('hasDataToRead', 'connectSocket'))
            ->setConstructorArgs(array('tcp://host'))
            ->getMock();

        $fp = tmpfile();

        $connection->expects($this->once())->method('connectSocket')->will($this->returnValue($fp));
        $connection->expects($this->once())->method('hasDataToRead')->will($this->returnValue(true));

        $connection->connect();
        fclose($fp);
        try {
            $connection->readFrame();
            $this->fail('Expected a exception!');
        } catch (ConnectionException $excpetion) {
            $this->assertContains('Check failed to determine if the socket is readable.', $excpetion->getMessage());
        }
    }

    public function testReadFrameThrowsExceptionIfErrorFrameIsReceived()
    {
        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->getMockBuilder('\Stomp\Connection')
            ->setMethods(array('hasDataToRead', 'connectSocket'))
            ->setConstructorArgs(array('tcp://host'))
            ->getMock();

        $fp = tmpfile();

        fwrite($fp, "ERROR\nmessage:stomp-err-info\n\nbody\x00");
        fseek($fp, 0);

        $connection->expects($this->once())->method('connectSocket')->will($this->returnValue($fp));
        $connection->expects($this->once())->method('hasDataToRead')->will($this->returnValue(true));

        $connection->connect();

        try {
            $connection->readFrame();
            $this->fail('Expected a exception!');
        } catch (ErrorFrameException $excpetion) {
            $this->assertContains('stomp-err-info', $excpetion->getMessage());
            $this->assertEquals('body', $excpetion->getFrame()->body);
        }
        fclose($fp);
    }

    public function testWriteFrameThrowsExceptionIfConnectionIsBroken()
    {
        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->getMockBuilder('\Stomp\Connection')
            ->setMethods(array('connectSocket'))
            ->setConstructorArgs(array('tcp://host'))
            ->getMock();

        $name = tempnam(sys_get_temp_dir(), 'stomp');
        $fp = fopen($name, 'r');

        $connection->expects($this->once())->method('connectSocket')->will($this->returnValue($fp));

        $connection->connect();

        try {
            $connection->writeFrame(new Frame('TEST'));
            $this->fail('Expected a exception!');
        } catch (ConnectionException $excpetion) {
            $this->assertContains('Was not possible to write frame!', $excpetion->getMessage());
        }
        fclose($fp);
    }

    public function testHasDataToReadThrowsExceptionIfConnectionIsBroken()
    {
        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $connection */
        $connection = $this->getMockBuilder('\Stomp\Connection')
            ->setMethods(array('isConnected', 'connectSocket'))
            ->setConstructorArgs(array('tcp://host'))
            ->getMock();

        $fp = tmpfile();

        $connection->expects($this->once())->method('connectSocket')->will($this->returnValue($fp));

        $connected = false;
        $connection->expects($this->exactly(2))
            ->method('isConnected')
            ->will(
                $this->returnCallback(
                    function () use (&$connected) {
                        return $connected;
                    }
                )
            );

        $connection->connect();
        // simulate active connection (reference)
        $connected = true;
        fclose($fp);
        try {
            $connection->readFrame();
            $this->fail('Expected a exception!');
        } catch (ConnectionException $excpetion) {
            $this->assertContains('Check failed to determine if the socket is readable', $excpetion->getMessage());
        }
    }

    public function testConnectionFailLeadsToException()
    {
        $connection = new Connection('tcp://0.0.0.1:15');
        try {
            $connection->connect();
            $this->fail('Expected an exception!');
        } catch (ConnectionException $ex) {
            $this->assertContains('Could not connect to a broker', $ex->getMessage());

            $this->assertInstanceOf(
                'Stomp\Exception\ConnectionException',
                $ex->getPrevious(),
                'There should be a previous exception.'
            );
            /** @var ConnectionException $prev */
            $prev = $ex->getPrevious();
            $hostInfo = $prev->getConnectionInfo();
            $this->assertEquals('0.0.0.1', $hostInfo['host']);
            $this->assertEquals('15', $hostInfo['port']);

        }
    }
}
