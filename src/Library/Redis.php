<?php
namespace Library;

class Redis
{
    public function getValue($key)
    {
        return "+{$key}+";
    }
}