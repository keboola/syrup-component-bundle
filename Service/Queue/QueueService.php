<?php
/**
 * Created by Miroslav Čillík <miro@keboola.com>
 * Date: 29/05/14
 * Time: 15:49
 */

namespace Syrup\ComponentBundle\Service\Queue;

use Aws\Sqs\SqsClient;

class QueueService
{
	/**
	 * @var SqsClient
	 */
	protected $client;
	protected $queueUrl;

	public function __construct(array $config)
	{
		$this->client = SqsClient::factory(array(
			'key'       => $config['access_key'],
			'secret'    => $config['secret_key'],
			'region'    => $config['region']
		));
		$this->queueUrl = $config['url'];
	}


	/**
	 */
	public function enqueue($body, $delay = 0)
	{
		$message = $this->client->sendMessage(array(
			'QueueUrl' => $this->queueUrl,
			'MessageBody' => json_encode($body),
			'DelaySeconds' => $delay,
		));
		return $message['MessageId'];
	}

	/**
	 * @param int $messagesCount
	 * @return array of QueueMessage
	 */
	public function receive($messagesCount = 1)
	{
		$result = $this->client->receiveMessage(array(
			'QueueUrl'          => $this->queueUrl,
			'WaitTimeSeconds'   => 20,
			'VisibilityTimeout' => 3600,
			'MaxNumberOfMessages' => $messagesCount,
		));

		$queueUrl = $this->queueUrl;
		return array_map(function($message) use ($queueUrl) {
			return new QueueMessage(
				$message['MessageId'],
				json_decode($message['Body']),
				$message['ReceiptHandle'],
				$queueUrl
			);
		}, (array) $result['Messages']);
	}


	public function deleteMessage(QueueMessage $message)
	{
		$this->client->deleteMessage(array(
			'QueueUrl' => $message->getQueueUrl(),
			'ReceiptHandle' => $message->getReceiptHandle(),
		));
	}



}
