<?php

namespace PHPDTool\enum;

use PHPDTool\exception\EnumException;
use ReflectionClass;

abstract class EnumBase
{
    /**
     * @var ReflectionClass
     */
    protected static $refClass;
    /**
     * @var string
     */
    protected static $classDocComment;
    /**
     * @var array
     */
    protected static $enums = [];
    /**
     * @var array
     */
    protected static $messages = [];
    /**
     * @var null
     */
    protected static $value = null;
    /**
     * @var IEnum
     */
    protected static $impl;
    /**
     * @var string
     */
    protected static $className;


    /**
     * 检测值是否在枚举类中
     * @param null $value
     * @param bool $strict
     * @return null
     * @throws
     */
    public static function inspect($value = null, bool $strict = true)
    {
        $constants = self::getEnums();
        if (!in_array($value, $constants, $strict)) {
            $class = self::getClassName();
            throw new EnumException("[{$value}]不存在{$class}枚举中");
        }
        return $value;
    }

    /**
     * 枚举数组
     * @return array
     * @throws EnumException
     * @throws \ReflectionException
     */
    public static function getEnums(): array
    {
        if (!static::$refClass) {
            static::$refClass = new \ReflectionClass(static::class);
        }
        $enums = static::$refClass->getConstants();
        $values = array_unique(array_values($enums));
        if (count($enums) != count($values)) {
            $class = self::getClassName();
            $enumCount = array_count_values($enums);
            $value = array_search(max($enumCount), $enumCount);
            throw new EnumException("{$class},枚举value: {$value}定义重复");
        }
        return self::$enums = $enums;
    }

    /**
     * @param $value
     * @return string|null
     * @throws EnumException
     * @throws \ReflectionException
     */
    public static function getMessage($value): ?string
    {
        if (!static::$refClass) {
            static::$refClass = new \ReflectionClass(static::class);
        }
        if (static::$messages && isset(static::$messages[$value])) {
            return static::$messages[$value];
        }
        $class = self::getClassName();
        foreach (self::getEnums() as $name => $val) {
            $docComment = static::$refClass->getReflectionConstant($name)->getDocComment();
            preg_match_all("/(?<=(\@Message\()).*?(?=(\)))/", $docComment, $doc);
            if ($doc) {
                static::$messages[$val] = trim($doc[0][0], '"');
            } else {
                throw new EnumException("{$class},枚举{$name}未定义@Message");
            }
        }
        if (isset(static::$messages[$value])) {
            return static::$messages[$value];
        }
        throw new EnumException("[{$value}]未匹配到{$class}枚举@Message");
    }

    /**
     * @return string
     * @throws \ReflectionException
     */
    public static function getClassName(): string
    {
        if (!static::$refClass) {
            static::$refClass = new \ReflectionClass(static::class);
        }
        $class = static::$refClass->getName();
        $class = explode('\\', $class);
        $class = end($class);
        self::$className = $class;
        return self::$className;
    }

    /**
     * @param string $docComment
     * @param string $constName
     * @throws EnumException
     * @throws \ReflectionException
     */
    private static function parseImpl(string $docComment, string $constName)
    {
        preg_match_all("/(?<=(@method static )).*?(?=({$constName}\r\n))/", $docComment, $doc);
        if ($doc) {
            if (isset($doc[0]) && !empty($doc[0])) {
                if (isset($doc[0][0]) && !empty($doc[0][0])) {
                    $class = "app\\enums\\impl\\" . trim($doc[0][0], " ");
                    self::$impl = new $class();
                }
            }
        }
        if (!self::$impl) {
            $class = self::getClassName();
            throw new EnumException($class . ",类Doc Comment未匹配到@method static IEnum implements {$constName}");
        }
    }

    /**
     * @param $name
     * @param $arguments
     * @return null
     * @throws EnumException
     * @throws \ReflectionException
     */
    public static function __callStatic($name, $arguments)
    {
        if (!static::$refClass) {
            static::$refClass = new \ReflectionClass(static::class);
        }
        if (!static::$classDocComment) {
            static::$classDocComment = static::$refClass->getDocComment();
        }
        $class = self::getClassName();
        if (preg_match("/{$name}\r\n/", static::$classDocComment)) {
            self::parseImpl(static::$classDocComment, $name);
            if (static::$enums && isset(static::$enums[$name])) {
                static::$value = static::$enums[$name];
            } else {
                static::getEnums();
                if (isset(static::$enums[$name])) {
                    static::$value = static::$enums[$name];
                } else {
                    throw new EnumException($class . ",未定义{$name}枚举");
                }
            }
            if (!static::$impl) {
                throw new EnumException($class . ",未找到IEnum实现类");
            }
            return static::$impl::set(static::$value, static::getMessage(static::$value));
        }
        throw new EnumException($class . ",未定义@method static {$name}");
    }
}