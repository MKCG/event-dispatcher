<?php

namespace MKCG\Event;

class Event
{
    private $name;

    private $context;

    private $data;

    public function __construct(string $name, array $data = [], $context = null)
    {
        $this->name = $name;
        $this->data = $data;
        $this->context = $context;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function setContext($context)
    {
        $this->context = $context;

        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getValue(string $name)
    {
        $names = explode('.', $name);

        $value = null;

        foreach ($names as $i => $key) {
            if ($i === 0) {
                if (!isset($this->data[$key])) {
                    return null;
                } else {
                    $value = $this->data[$key];
                }
            } elseif (isset($value[$key])) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }
}
