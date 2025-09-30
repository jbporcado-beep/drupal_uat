<?php

namespace Drupal\common\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Drupal\Core\Messenger\MessengerInterface;

class SuppressFormErrorsSubscriber implements EventSubscriberInterface
{

    protected MessengerInterface $messenger;

    public function __construct(MessengerInterface $messenger)
    {
        $this->messenger = $messenger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onResponse', 0],
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        $messages = $this->messenger->all();

        if (!empty($messages['error'])) {
            unset($messages['error']);

            $this->messenger->deleteAll();
            foreach ($messages as $type => $msgs) {
                foreach ($msgs as $msg) {
                    $this->messenger->addMessage($msg, $type);
                }
            }
        }
    }
}
