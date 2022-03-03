<?php


namespace PHPDTool\enum;


interface IEnum
{
    /**
     * @param $value
     * @param string $message
     * @return IEnum
     */
    public static function set($value, string $message): self;

    /**
     * @return mixed
     */
    public static function getValue();

    /**
     * @return string
     */
    public static function getMessage(): string;
}