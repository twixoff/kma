<?php

declare(strict_types=1);

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/MyAMQP.php";

use app\MyAMQP;
use PhpAmqpLib\Message\AMQPMessage;

$exchange = $_ENV['EXCHANGE_NAME'];
$queue = $_ENV['QUEUE_NAME'];
$connection = MyAMQP::getConnect();
$channel = $connection->channel();
$channel->queue_declare($queue, false, true, false, false);

function processTask(string $url): bool
{
    $c = curl_init();
    curl_setopt_array($c, [
        CURLOPT_URL => $url,
        CURLOPT_HEADER => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $response = curl_exec($c);
    $info = curl_getinfo($c);
    $responseCode = curl_getinfo($c, CURLINFO_HTTP_CODE);
    curl_close($c);

    if ($responseCode === 200) {
        $headerSize = $info['header_size'];
        $headers = substr($response, 0, $headerSize);
        $content = substr($response, $headerSize);

        saveResult(['url' => $url, 'code' => $responseCode, 'header' => $headers, 'body' => $content]);

        return true;
    } else {
        echo "ERROR: " . $responseCode . "\n";

        return false;
    }
}

function saveResult(array $parts): void
{
    $db = new PDO('mysql:dbname=' . $_ENV['MYSQL_DATABASE'] . ';host=kma-db:3306', $_ENV['MYSQL_USER'], $_ENV['MYSQL_PASSWORD']);
    $sql = $db->prepare(
        "insert into results set
        url = :url,
        code = :code,
        header = :header,
        body = :body"
    );
    $sql->execute([
        ':url' => $parts['url'],
        ':code' => $parts['code'],
        ':header' => $parts['header'],
        ':body' => $parts['body']
    ]);
}

function processMessage(AMQPMessage $message)
{
    echo "Processing " . $message->body . "\n";
    if (processTask($message->body)) {
        $message->ack();
    } else {
        $message->nack(!$message->isRedelivered());
    }
    sleep(30);
}

$channel->basic_qos(null, 1, null);
$channel->basic_consume($queue, '', false, false, false, false, 'processMessage');
while ($channel->is_open()) {
    $channel->wait();
}

$channel->close();
$connection->close();
