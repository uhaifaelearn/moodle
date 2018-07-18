<?php

namespace tool_updatewizard\Manager\FileHandler;

abstract class HandlerFactory
{
    public static function createHandlerInstance($handlerData)
    {
        $handler = __NAMESPACE__.'\\'.$handlerData['class'];

        return new $handler($handlerData);
    }
}
