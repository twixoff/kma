<?php

declare(strict_types=1);

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/MyAMQP.php";

use app\MyAMQP;
use PhpAmqpLib\Message\AMQPMessage;

$exchange = $_ENV['EXCHANGE_NAME'];
$queue = $_ENV['QUEUE_NAME'];
try {
    $connection = MyAMQP::getConnect();
} catch (Exception|RuntimeException $e) {
    echo "Consumer AMQP connection error: " .$e->getMessage() . "\n";
    exit();
}
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

        try {
            saveResult(['url' => $url, 'code' => $responseCode, 'header' => $headers, 'body' => $content]);
        } catch (Exception $e) {
            echo "Error saving in db: " . $e->getMessage() . "\n";
            return false;
        }

        return true;
    } else {
        echo "ERROR: " . $responseCode . "\n";

        return false;
    }
}

function saveResult(array $parts): void
{
    $db = new PDO(
        'mysql:dbname=' . $_ENV['MYSQL_DATABASE'] . ';host=' . $_ENV['MYSQL_HOST'] . ':3306',
        $_ENV['MYSQL_USER'],
        $_ENV['MYSQL_PASSWORD']
    );
    $sql = $db->prepare(
        "insert into results set
        worker_id = :worker_id,
        url = :url,
        code = :code,
        header = :header,
        body = :body"
    );

    $sql->execute([
        ':worker_id' => getmypid(),
        ':url' => $parts['url'],
        ':code' => $parts['code'],
        ':header' => $parts['header'],
        ':body' => $parts['body']
    ]);
}

function processMessage(AMQPMessage $message)
{
    echo "Processing (" . date('H:i:s') . ") " . $message->body . "\n";
    if (processTask($message->body)) {
        $message->ack();
    } else {
        $message->nack(!$message->isRedelivered());
    }
}

$channel->basic_qos(null, 1, null);
$channel->basic_consume($queue, '', false, false, false, false, 'processMessage');

try {
    while ($channel->is_open()) {
        $channel->wait(null, false, 30);
    }
    $channel->close();
    $connection->close();
} catch (Exception|RuntimeException $e) {
    echo "Consumer AMQP wait or close connection error: " .$e->getMessage() . "\n";
    exit();
}
