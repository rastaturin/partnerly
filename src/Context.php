<?php

namespace Partnerly;


class Context
{
    public $id, $payload;
    public function __construct($id, $payload)
    {
        $this->id = $id;
        $this->payload = $payload;
    }
}