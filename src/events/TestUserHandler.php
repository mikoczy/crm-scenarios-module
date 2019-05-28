<?php

namespace Crm\ScenariosModule\Events;

use Crm\ApplicationModule\Hermes\HermesMessage;
use Crm\ScenariosModule\Engine\Dispatcher;
use Tomaj\Hermes\Handler\HandlerInterface;
use Tomaj\Hermes\MessageInterface;

class TestUserHandler implements HandlerInterface
{
    const HERMES_MESSAGE_CODE = 'scenarios-test-user';

    private $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function handle(MessageInterface $message): bool
    {
        $payload = $message->getPayload();
        if (!isset($payload['user_id'])) {
            throw new \Exception('unable to handle event: user_id missing');
        }

        $this->dispatcher->dispatch('test_user', $payload['user_id']);
        return true;
    }

    public static function createHermesMessage($userId)
    {
        return new HermesMessage(self::HERMES_MESSAGE_CODE, [
            'user_id' => $userId
        ]);
    }
}
