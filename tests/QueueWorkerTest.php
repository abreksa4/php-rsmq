<?php declare(strict_types=1);

use AndrewBreksa\RSMQ\ExecutorInterface;
use AndrewBreksa\RSMQ\Message;
use AndrewBreksa\RSMQ\QueueWorker;
use AndrewBreksa\RSMQ\RSMQClientInterface;
use AndrewBreksa\RSMQ\WorkerSleepProvider;
use PHPUnit\Framework\TestCase;

/**
 * Class QueueWorkerTest
 *
 * @author Andrew Breksa <andrew@andrewbreksa.com>
 */
class QueueWorkerTest extends TestCase
{


    public function testEjectFromSleepProvider()
    {
        $rsmq = Mockery::mock(RSMQClientInterface::class);
        $executor = Mockery::mock(ExecutorInterface::class);
        $sleepProvider = Mockery::mock(WorkerSleepProvider::class);
        $sleepProvider->shouldReceive('getSleep')
            ->andReturn(null)
            ->once();
        $worker = new QueueWorker($rsmq, $executor, $sleepProvider, 'test');

        $worker->work(true);
        self::assertEquals(0, $worker->getProcessedCount());
    }

    public function testProcessOneAvilableFailed()
    {
        $message = Mockery::mock(Message::class);
        $rsmq = Mockery::mock(RSMQClientInterface::class);
        $rsmq->shouldReceive('receiveMessage')
            ->with('test')
            ->andReturn($message)
            ->once();
        $executor = Mockery::mock(ExecutorInterface::class);
        $executor->shouldReceive('__invoke')
            ->with($message)
            ->andReturn(false)
            ->once();
        $sleepProvider = Mockery::mock(WorkerSleepProvider::class);
        $sleepProvider->shouldReceive('getSleep')
            ->andReturn(0)
            ->once();
        $worker = new QueueWorker($rsmq, $executor, $sleepProvider, 'test');

        $worker->work(true);
        self::assertEquals(1, $worker->getProcessedCount());
        self::assertEquals(0, $worker->getSuccessful());
        self::assertEquals(1, $worker->getFailed());
    }

    public function testProcessOneNotAvilableSuccessful()
    {
        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getId')
            ->andReturn('test_id')
            ->once();
        $rsmq = Mockery::mock(RSMQClientInterface::class);
        $rsmq->shouldReceive('receiveMessage')
            ->with('test')
            ->andReturn(null, $message)
            ->twice();
        $rsmq->shouldReceive('deleteMessage')
            ->with('test', 'test_id')
            ->once();
        $executor = Mockery::mock(ExecutorInterface::class);
        $executor->shouldReceive('__invoke')
            ->with($message)
            ->andReturn(true)
            ->once();
        $sleepProvider = Mockery::mock(WorkerSleepProvider::class);
        $sleepProvider->shouldReceive('getSleep')
            ->andReturn(0)
            ->twice();
        $worker = new QueueWorker($rsmq, $executor, $sleepProvider, 'test');

        $worker->work(true);
        self::assertEquals(1, $worker->getProcessedCount());
        self::assertEquals(1, $worker->getSuccessful());
        self::assertEquals(0, $worker->getFailed());
    }

    public function testProcessThreeAndExit()
    {
        $message = Mockery::mock(Message::class);
        $message->shouldReceive('getId')
            ->andReturn('test_id')
            ->twice();
        $rsmq = Mockery::mock(RSMQClientInterface::class);
        $rsmq->shouldReceive('receiveMessage')
            ->with('test')
            ->andReturn($message, $message, $message)
            ->times(3);
        $rsmq->shouldReceive('deleteMessage')
            ->with('test', 'test_id')
            ->twice();
        $executor = Mockery::mock(ExecutorInterface::class);
        $executor->shouldReceive('__invoke')
            ->with($message)
            ->andReturn(false, true, true)
            ->times(3);
        $sleepProvider = Mockery::mock(WorkerSleepProvider::class);
        $sleepProvider->shouldReceive('getSleep')
            ->andReturn(0, 0, 0, null)
            ->times(4);
        $worker = new QueueWorker($rsmq, $executor, $sleepProvider, 'test');

        $worker->work();
        self::assertEquals(3, $worker->getProcessedCount());
        self::assertEquals(2, $worker->getSuccessful());
        self::assertEquals(1, $worker->getFailed());
        self::assertEquals(3, $worker->getReceived());
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
