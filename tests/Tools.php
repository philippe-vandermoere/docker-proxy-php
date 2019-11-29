<?php

/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace Test\App;

class Tools
{
    public static function getPropertyValue(object $object, string $property)
    {
        $reflectionClass = new \ReflectionClass($object);
        $reflectionProperty = $reflectionClass->getProperty($property);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($object);
    }

    public static function getConstant(object $object, string $constant)
    {
        $reflectionClass = new \ReflectionClass($object);

        return $reflectionClass->getConstant($constant);
    }

    public static function callProtectedMethod(object $object, string $method, array $args = [])
    {
        $reflectionClass = new \ReflectionClass($object);
        $reflectionMethod = $reflectionClass->getMethod($method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($object, $args);
    }
}
