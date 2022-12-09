<?php

namespace app;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class MyAMQP extends AMQPStreamConnection
{
    public static function getConnect()
    {
        return new parent(
            $_ENV['RABBITMQ_HOST'],
            $_ENV['RABBITMQ_PORT'],
            $_ENV['RABBITMQ_DEFAULT_USER'],
            $_ENV['RABBITMQ_DEFAULT_PASS']
        );
    }
}