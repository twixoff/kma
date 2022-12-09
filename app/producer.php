<?php

declare(strict_types=1);

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/MyAMQP.php";

use app\MyAMQP;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Exception\AMQPRuntimeException;

$exchange = $_ENV['EXCHANGE_NAME'];
$queue = $_ENV['QUEUE_NAME'];
$connection = MyAMQP::getConnect();
$channel = $connection->channel();
$channel->queue_declare($queue, false, true, false, false);
$channel->exchange_declare($exchange, AMQPExchangeType::DIRECT, false, true, false);
$channel->queue_bind($queue, $exchange);

$urls = [
    'google.com',
    'mail.ru',
    'ya.ru',
    'rambler.com',
    'kase.kz',
    'hh.ru',
    'habr.ru',
    'stackoverflow.com',
    'disney.com',
    'netflix.com',
    'google.com/zxzx'
];

while (true) {
    $url = $urls[array_rand($urls)];
    echo "Pushing to AMPQ " . $url . "\n";
    try {
        $message = new AMQPMessage($url, ['content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
        $channel->basic_publish($message, $exchange);
    } catch (AMQPRuntimeException|Exception $e) {
        echo "ERROR: " . $e->getMessage();
    }
    sleep(5);
}
