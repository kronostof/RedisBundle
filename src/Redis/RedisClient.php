<?php

declare(strict_types=1);

namespace M6Web\Bundle\RedisBundle\Redis;

use Predis\Client as PredisClient;
use Predis\Command\CommandInterface;
use Symfony\Component\EventDispatcher;
use M6Web\Bundle\RedisBundle\EventDispatcher\RedisEvent;

class RedisClient extends PredisClient
{
    const DEFAULT_EVENT = 'redis.command';

    /**
     * event dispatcher
     *
     * @var EventDispatcher
     */
    protected $eventDispatcher = null;

    /**
     * class of the event notifier
     *
     * @var string
     */
    protected $eventClass = null;

    /**
     * eventName to be dispatched
     *
     * @var array
     */
    protected $eventName;

    public function __construct($parameters, $options)
    {
        \Predis\Profile\Factory::define('compression', 'M6Web\Bundle\RedisBundle\Profile\CompressionProfile');
        parent::__construct($parameters, $options);
    }

    public function setEventDispatcher(EventDispatcher\EventDispatcherInterface $eventDispacher): self
    {
        $this->eventDispatcher = $eventDispacher;

        return $this;
    }

    public function setEventName(string $name): self
    {
        $this->eventName = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function executeCommand(CommandInterface $command)
    {
        $start = microtime(true);
        $result = parent::executeCommand($command);
        $this->notifyEvents($command, microtime(true) - $start);

        return $result;
    }

    public function notifyEvents(CommandInterface $command, float $time): self
    {
        if ($this->eventDispatcher) {
            $event = new RedisEvent();
            $event->setCommand($command->getId());
            $event->setExecutionTime($time);
            $event->setArguments($command->getArguments());
            $this->eventDispatcher->dispatch(self::DEFAULT_EVENT, $event);
            if (!is_null($this->eventName)) {
                $this->eventDispatcher->dispatch($this->eventName, $event);
            }
        }

        return $this;
    }
}
