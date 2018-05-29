<?php

/*
 * This file is part of the php-gelf package.
 *
 * (c) Benjamin Zikarsky <http://benjamin-zikarsky.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gelf\Transport;

use Gelf\Encoder\JsonEncoder as DefaultEncoder;
use Gelf\MessageInterface as Message;

if (!defined('STOMP_DURABLE')) {
    define('STOMP_DURABLE', 0);
}

if (!class_exists('\Stomp')) {
    throw new \Exception('PECL extension "Stomp" not installed');
}

/**
 * Class StompTransport
 *
 * @package Gelf\Transport
 * @see http://php.net/manual/en/book.stomp.php
 */
class StompTransport extends AbstractTransport
{
    /** @var \Stomp */
    protected $connection;

    /** @var string */
    protected $queue;

    /**
     * @param \Stomp $connection
     * @param string $queue
     */
    public function __construct(\Stomp $connection, $queue = 'graylog')
    {
        $this->connection    = $connection;
        $this->queue         = $queue;
        $this->messageEncoder= new DefaultEncoder();
    }

    /**
     * @inheritdoc
     */
    public function send(Message $message)
    {
        $rawMessage = $this->getMessageEncoder()->encode($message);

        $attributes = array(
            'Content-type' => 'application/json'
        );

        // if queue is durable then mark message as 'persistent'
        if (STOMP_DURABLE > 0) {
            $attributes['persistent'] = 'true';
        }

        $result = $this->getConnection()->send($this->queue, $rawMessage, $attributes);

        if (!$result) {
            $message = $this->getConnection()->error();
            if (empty($message)) {
                $message = 'Error when sending a message';
            }

            throw new \StompException($message, 500);
        }

        return 1;
    }

    /**
     * @return \Stomp
     */
    public function getConnection()
    {
        return $this->connection;
    }
}
