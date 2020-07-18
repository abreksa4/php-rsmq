Redis Simple Message Queue
--------------------------
[![Travis CI](https://travis-ci.org/abreksa4/php-rsmq.svg?branch=master)](https://travis-ci.org/abreksa4/php-rsmq)
[![codecov](https://codecov.io/gh/abreksa4/php-rsmq/branch/master/graph/badge.svg)](https://codecov.io/gh/abreksa4/php-rsmq)
[![License](https://poser.pugx.org/andrewbreksa/rsmq/license)](//packagist.org/packages/andrewbreksa/rsmq)
[![GitHub issues](https://img.shields.io/github/issues/abreksa4/php-rsmq)](https://github.com/abreksa4/php-rsmq/issues)
[![Latest Stable Version](https://poser.pugx.org/andrewbreksa/rsmq/v)](//packagist.org/packages/andrewbreksa/rsmq) 
[![Latest Unstable Version](https://poser.pugx.org/andrewbreksa/rsmq/v/unstable)](//packagist.org/packages/andrewbreksa/rsmq) 
[![composer.lock](https://poser.pugx.org/andrewbreksa/rsmq/composerlock)](//packagist.org/packages/andrewbreksa/rsmq)
[![Total Downloads](https://poser.pugx.org/andrewbreksa/rsmq/downloads)](//packagist.org/packages/andrewbreksa/rsmq) 
[![GitHub stars](https://img.shields.io/github/stars/abreksa4/php-rsmq)](https://github.com/abreksa4/php-rsmq/stargazers)
[![Dependents](https://poser.pugx.org/andrewbreksa/rsmq/dependents)](//packagist.org/packages/andrewbreksa/rsmq)

A lightweight message queue for PHP that requires no dedicated queue server. Just a Redis server. See 
[smrchy/rsmq](https://github.com/smrchy/rsmq) for more information.

This is a fork of [eislambey/php-rsmq](https://github.com/eislambey/php-rsmq) with the following changes:
- Uses [predis](https://github.com/nrk/predis) instead of the Redis extension
- Has some OO wrappers for QueueAttributes and Message
- Provides a simple [QueueWorker](./src/QueueWorker.php)

# Table of Contents

<!-- toc -->

- [Installation](#installation)
- [Methods](#methods)
  * [Construct](#construct)
  * [Queue](#queue)
    + [createQueue](#createqueue)
    + [listQueues](#listqueues)
    + [deleteQueue](#deletequeue)
    + [getQueueAttributes](#getqueueattributes)
    + [setQueueAttributes](#setqueueattributes)
  * [Messages](#messages)
    + [sendMessage](#sendmessage)
    + [receiveMessage](#receivemessage)
    + [deleteMessage](#deletemessage)
    + [popMessage](#popmessage)
    + [changeMessageVisibility](#changemessagevisibility)
- [QueueWorker](#queueworker)
- [LICENSE](#license)

<!-- tocstop -->

# Installation
    composer require andrewbreksa/rsmq

# Methods

## Construct
Creates a new instance of RSMQ.

Parameters:

* `$predis` (\Predis\ClientInterface): *required The Predis instance
* `$ns` (string): *optional (Default: "rsmq")* The namespace prefix used for all keys created by RSMQ
* `$realtime` (Boolean): *optional (Default: false)* Enable realtime PUBLISH of new messages

Example:

```php
<?php
use Predis\Client;
use AndrewBreksa\RSMQ\RSMQClient;

$predis = new Client(
    [
        'host' => '127.0.0.1',
        'port' => 6379
    ]
);
$this->rsmq = new RSMQClient($predis);
```

## Queue

### createQueue
Create a new queue.

Parameters:

* `$name` (string): The Queue name. Maximum 160 characters; alphanumeric characters, hyphens (-), and underscores (_) 
are allowed.
* `$vt` (int): *optional* *(Default: 30)* The length of time, in seconds, that a message received from a queue will be 
invisible to other receiving components when they ask to receive messages. Allowed values: 0-9999999 (around 115 days)
* `$delay` (int): *optional* *(Default: 0)* The time in seconds that the delivery of all new messages in the queue will 
be delayed. Allowed values: 0-9999999 (around 115 days)
* `$maxsize` (int): *optional* *(Default: 65536)* The maximum message size in bytes. Allowed values: 1024-65536 and -1 
(for unlimited size)

Returns:

* `true` (Bool)

Throws:
* `\AndrewBreksa\RSMQ\Exceptions\QueueAlreadyExistsException`

Example:

```php
<?php
/**
 * @var AndrewBreksa\RSMQ\RSMQClientInterface $rsmq
 */

$rsmq->createQueue('myqueue');
```

### listQueues
List all queues

Returns an array:

* `["qname1", "qname2"]`

Example:

```php
<?php
/**
 * @var AndrewBreksa\RSMQ\RSMQClientInterface $rsmq
 */

$queues = $rsmq->listQueues();
```

### deleteQueue
Deletes a queue and all messages.

Parameters:

* `$name` (string): The Queue name.

Returns:

* `true` (Bool)


Throws:

* `\AndrewBreksa\RSMQ\Exceptions\QueueNotFoundException`

Example:

```php
<?php
/**
 * @var AndrewBreksa\RSMQ\RSMQClientInterface $rsmq
 */

$rsmq->deleteQueue('myqueue');
```

### getQueueAttributes
Get queue attributes, counter and stats

Parameters:

* `$queue` (string): The Queue name.

Returns a `\AndrewBreksa\RSMQ\QueueAttributes` object with the following properties:

* `vt` (int): The visibility timeout for the queue in seconds
* `delay` (int): The delay for new messages in seconds
* `maxSize` (int): The maximum size of a message in bytes
* `totalReceived` (int): Total number of messages received from the queue
* `totalSent` (int): Total number of messages sent to the queue
* `created` (float): Timestamp (epoch in seconds) when the queue was created
* `modified` (float): Timestamp (epoch in seconds) when the queue was last modified with `setQueueAttributes`
* `messageCount` (int): Current number of messages in the queue
* `hiddenMessageCount` (int): Current number of hidden / not visible messages. A message can be hidden while "in flight" 
due to a `vt` parameter or when sent with a `delay`

Example:

```php
<?php
/**
 * @var AndrewBreksa\RSMQ\RSMQClientInterface $rsmq
 */

$attributes =  $rsmq->getQueueAttributes('myqueue');
echo "visibility timeout: ", $attributes->getVt(), "\n";
echo "delay for new messages: ", $attributes->getDelay(), "\n";
echo "max size in bytes: ", $attributes->getMaxSize(), "\n";
echo "total received messages: ", $attributes->getTotalReceived(), "\n";
echo "total sent messages: ", $attributes->getTotalSent(), "\n";
echo "created: ", $attributes->getCreated(), "\n";
echo "last modified: ", $attributes->getModified(), "\n";
echo "current n of messages: ", $attributes->getMessageCount(), "\n";
echo "hidden messages: ", $attributes->getHiddenMessageCount(), "\n";
```


### setQueueAttributes
Sets queue parameters.

Parameters:

* `$queue` (string): The Queue name.
* `$vt` (int): *optional* * The length of time, in seconds, that a message received from a queue will be invisible to 
other receiving components when they ask to receive messages. Allowed values: 0-9999999 (around 115 days)
* `$delay` (int): *optional* The time in seconds that the delivery of all new messages in the queue will be delayed. 
Allowed values: 0-9999999 (around 115 days)
* `$maxsize` (int): *optional* The maximum message size in bytes. Allowed values: 1024-65536 and -1 (for unlimited size)

Note: At least one attribute (vt, delay, maxsize) must be supplied. Only attributes that are supplied will be modified.

Returns a `\AndrewBreksa\RSMQ\QueueAttributes` object with the following properties:

* `vt` (int): The visibility timeout for the queue in seconds
* `delay` (int): The delay for new messages in seconds
* `maxSize` (int): The maximum size of a message in bytes
* `totalReceived` (int): Total number of messages received from the queue
* `totalSent` (int): Total number of messages sent to the queue
* `created` (float): Timestamp (epoch in seconds) when the queue was created
* `modified` (float): Timestamp (epoch in seconds) when the queue was last modified with `setQueueAttributes`
* `messageCount` (int): Current number of messages in the queue
* `hiddenMessageCount` (int): Current number of hidden / not visible messages. A message can be hidden while "in flight" 
due to a `vt` parameter or when sent with a `delay`

Throws:
* `\AndrewBreksa\RSMQ\QueueAttributes`
* `\AndrewBreksa\RSMQ\QueueParametersValidationException`
* `\AndrewBreksa\RSMQ\QueueNotFoundException`

Example:

```php
<?php
/**
 * @var AndrewBreksa\RSMQ\RSMQClientInterface $rsmq
 */

$queue = 'myqueue';
$vt = 50;
$delay = 10;
$maxsize = 2048;
$rsmq->setQueueAttributes($queue, $vt, $delay, $maxsize);
```

## Messages

### sendMessage
Sends a new message.

Parameters:

* `$queue` (string)
* `$message` (string)
* `$delay` (int): *optional* *(Default: queue settings)* The time in seconds that the delivery of the message will be 
delayed. Allowed values: 0-9999999 (around 115 days)

Returns:

* `$id` (string): The internal message id.

Throws:
* `\AndrewBreksa\RSMQ\Exceptions\MessageToLongException`
* `\AndrewBreksa\RSMQ\Exceptions\QueueNotFoundException`
* `\AndrewBreksa\RSMQ\Exceptions\QueueParametersValidationException`

Example:

```php
<?php
/**
 * @var AndrewBreksa\RSMQ\RSMQClientInterface $rsmq
 */

$id = $rsmq->sendMessage('myqueue', 'a message');
echo "Message Sent. ID: ", $id;
```

### receiveMessage
Receive the next message from the queue.

Parameters:

* `$queue` (string): The Queue name.
* `$vt` (int): *optional* *(Default: queue settings)* The length of time, in seconds, that the received message will be 
invisible to others. Allowed values: 0-9999999 (around 115 days)

Returns a `\AndrewBreksa\RSMQ\Message` object with the following properties:

  * `message` (string): The message's contents.
  * `id` (string): The internal message id.
  * `sent` (int): Timestamp of when this message was sent / created.
  * `firstReceived` (int): Timestamp of when this message was first received.
  * `receiveCount` (int): Number of times this message was received.

Note: Will return an empty array if no message is there  

Throws:
* `\AndrewBreksa\RSMQ\Exceptions\QueueNotFoundException`
* `\AndrewBreksa\RSMQ\Exceptions\QueueParametersValidationException`

Example:

```php
<?php
/**
 * @var AndrewBreksa\RSMQ\RSMQClientInterface $rsmq
 */

$message = $rsmq->receiveMessage('myqueue');
echo "Message ID: ", $message->getId();
echo "Message: ", $message->getMessage();
```

### deleteMessage
Parameters:

* `$queue` (string): The Queue name.
* `$id` (string): message id to delete.

Returns:

* `true` if successful, `false` if the message was not found (bool).

Throws:
* `\AndrewBreksa\RSMQ\Exceptions\QueueParametersValidationException`

Example:

```php
<?php
/**
 * @var AndrewBreksa\RSMQ\RSMQClientInterface $rsmq
 */

$id = $rsmq->sendMessage('queue', 'a message');
$rsmq->deleteMessage('queue', $id);
```

### popMessage
Receive the next message from the queue **and delete it**.

**Important:** This method deletes the message it receives right away. There is no way to receive the message again if 
something goes wrong while working on the message.

Parameters:

* `$queue` (string): The Queue name.

Returns a `\AndrewBreksa\RSMQ\Message` object with the following properties:

  * `message` (string): The message's contents.
  * `id` (string): The internal message id.
  * `sent` (int): Timestamp of when this message was sent / created.
  * `firstReceived` (int): Timestamp of when this message was first received.
  * `receiveCount` (int): Number of times this message was received.

Note: Will return an empty object if no message is there

Throws:
* `\AndrewBreksa\RSMQ\Exceptions\QueueNotFoundException`
* `\AndrewBreksa\RSMQ\Exceptions\QueueParametersValidationException`

Example:

```php
<?php
/**
 * @var AndrewBreksa\RSMQ\RSMQClientInterface $rsmq
 */

$message = $rsmq->popMessage('myqueue');
echo "Message ID: ", $message->getId();
echo "Message: ", $message->getMessage();
```

### changeMessageVisibility
Change the visibility timer of a single message.
The time when the message will be visible again is calculated from the current time (now) + `vt`.

Parameters:

* `qname` (string): The Queue name.
* `id` (string): The message id.
* `vt` (int): The length of time, in seconds, that this message will not be visible. Allowed values: 0-9999999 (around 
115 days)

Returns: 

* `true` if successful, `false` if the message was not found (bool).

Throws:
* `\AndrewBreksa\RSMQ\Exceptions\QueueParametersValidationException`
* `\AndrewBreksa\RSMQ\Exceptions\QueueNotFoundException`

Example:

```php
<?php
/**
 * @var AndrewBreksa\RSMQ\RSMQClientInterface $rsmq
 */

$queue = 'myqueue';
$id = $rsmq->sendMessage($queue, 'a message');
if($rsmq->changeMessageVisibility($queue, $id, 60)) {
	echo "Message hidden for 60 secs";
}
```

# QueueWorker
The QueueWorker class provides an easy way to consume RSMQ messages, to use it:
```php
<?php
/**
 * @var AndrewBreksa\RSMQ\RSMQClientInterface $rsmq
 */

use AndrewBreksa\RSMQ\ExecutorInterface;
use AndrewBreksa\RSMQ\Message;
use AndrewBreksa\RSMQ\QueueWorker;
use AndrewBreksa\RSMQ\WorkerSleepProvider;

$executor = new class() implements ExecutorInterface{
    public function __invoke(Message $message) : bool {
        //@todo: do some work, true will ack/delete the message, false will allow the queue's config to "re-publish"
        return true;
    }
};

$sleepProvider = new class() implements WorkerSleepProvider{
    public function getSleep() : ?int {
        /**
         * This allows you to return null to stop the worker, which can be used with something like redis to mark.
         *
         * Note that this method is called _before_ we poll for a message, and therefore if it returns null we'll eject
         * before we process a message.
         */
        return 1;
    }
};

$worker = new QueueWorker($rsmq, $executor, $sleepProvider, 'test_queue');
$worker->work(); // here we can optionally pass true to only process one message
```

# LICENSE
The MIT LICENSE. See [LICENSE](./LICENSE)
