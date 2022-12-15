<?php

declare(strict_types=1);

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/MyAMQP.php";

use app\MyAMQP;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exchange\AMQPExchangeType;

$exchange = $_ENV['EXCHANGE_NAME'];
$queue = $_ENV['QUEUE_NAME'];
try {
    $connection = MyAMQP::getConnect();
} catch (Exception|RuntimeException $e) {
    echo "Producer AMQP connection error: " .$e->getMessage() . "\n";
    exit();
}
$channel = $connection->channel();
$channel->queue_declare($queue, false, true, false, false);
$channel->exchange_declare($exchange, AMQPExchangeType::DIRECT, false, true, false);
$channel->queue_bind($queue, $exchange);

$urls = [
    'https://google.com',
    'https://mail.ru',
    'https://ya.ru',
    'https://rambler.com',
    'https://kase.kz',
    'https://hh.ru',
    'https://habr.ru',
    'https://stackoverflow.com',
    'https://disney.com',
    'https://netflix.com',
    'https://google.com/zxzx'
];

$i = 0;
$timeout = 10;
while (true) {
    $url = $urls[array_rand($urls)];
    echo "Pushing to AMPQ (" . date('H:i:s') . "): " . $url . "\n";
    try {
        $message = new AMQPMessage($url, [
            'content_type' => 'text/plain',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ]);
        $channel->basic_publish($message, $exchange);
    } catch (Exception|RuntimeException $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        exit();
    }

    $i++;

    if ($i % $timeout === 0) {
        echo "Sleeping 10 sec...\n";
        $i = 0;
        sleep($timeout);
    }
}
