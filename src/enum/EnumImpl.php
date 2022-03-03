<?php


namespace PHPDTool\enum;


class EnumImpl implements IEnum
{
    /**
     * @var mixed
     */
    protected static $value;
    /**
     * @var string
     */
    protected static $message;

    /**
     * @param $value
     * @param string $message
     * @return IEnum
     */
    public static function set($value, string $message): IEnum
    {
        $self = new self();
        $self::$value = $value;
        $self::$message = $message;
        return $self;
    }

    /**
     * @return mixed
     */
    public static function getValue()
    {
        return self::$value;
    }

    /**
     * @return string
     */
    public static function getMessage(): string
    {
        return self::$message;
    }
}