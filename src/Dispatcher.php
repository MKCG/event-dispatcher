<?php

namespace MKCG\Event;

class Dispatcher
{
    private $debug = false;

    private $broadcast = [];
    private $listeners = [];

    private $di;

    public function __construct(callable $di)
    {
        $this->broadcast = new \ArrayIterator();
        $this->listeners = [];
        $this->di = $di;
    }

    public function addBroadcastListener($listener, int $priority = 1)
    {
        $listener = $this->makeProxyListener($listener);
        $this->broadcast->append($listener);

        return $this;
    }

    public function addListener(string $eventName, $listener, int $priority = 1)
    {
        if (!isset($this->listeners[$eventName])) {
            $this->listeners[$eventName] = new \ArrayIterator();
        }

        $listener = $this->makeProxyListener($listener);
        $this->listeners[$eventName]->append($listener);

        return $this;
    }

    public function trigger(Event $event)
    {
        $this->applyListeners($event, $this->broadcast);

        if (isset($this->listeners[$event->getName()])) {
            $this->applyListeners($event, $this->listeners[$event->getName()]);
        }

        return $this;
    }

    private function applyListeners(Event $event, $listeners)
    {
        foreach ($listeners as $listener) {
            $innerEvents = $listener($event);

            if (!$innerEvents instanceof \Iterator) {
                continue;
            }

            foreach ($innerEvents as $innerEvent) {
                if ($innerEvent === null) {
                    continue;
                }

                if ($innerEvent->getContext() === null) {
                    $innerEvent->setContext($event->getContext());
                }

                $this->trigger($innerEvent);
            }
        }

        return $this;
    }

    private function makeProxyListener($listener)
    {
        $di = $this->di;

        if (!is_array($listener) && is_callable($listener)) {
            $reflParams = (new \ReflectionFunction($listener))->getParameters();
            $di = $di($reflParams);

            return function ($event) use ($listener, $di) {
                $params = $di($event);
                return $listener(...$params);
            };
        }

        $isInstantiable = is_array($listener)
            && isset($listener[0], $listener[1])
            && is_object($listener[0]) && is_string($listener[1])
            && method_exists($listener[0], $listener[1]);

        if (!$isInstantiable) {
            return function () {
                yield;
            };
        }

        $reflParams = (new \ReflectionMethod($listener[0], $listener[1]))->getParameters();

        $di = $di($reflParams);

        return function ($event) use ($listener, $di) {
            $params = $di($event);
            $value = $listener[0]->{$listener[1]}(...$params);

            if ($value instanceof \Generator) {
                while ($value->valid()) {
                    yield $value->current();
                    $value->next();
                }

                yield $value->getReturn();
            } elseif ($value instanceof Event) {
                $value->setContext($event->getContext());
                yield $value;
            }
        };
    }
}
