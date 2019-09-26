<?php

    namespace framer;

    /**
    * 
    */
    class Routes
    {

        static function get($action)
        {
            return BASE_URI . '/' . str_replace('.', '/', $action);
        }

        static function getRoutes()
        {
            return [
                
                "default" => "/",
                "list" => "/search/list",
                "listsingle" => "/search/details",
                "sendmessage" => "/search/send-message",
                "rate" => "/search/rate",
                "directions" => "/search/directions"

            ];
        }

        static function find($route)
        {
            $routes = self::getRoutes();
            return isset($routes[$route]) ? Config::$appfolder . $routes[$route] : Config::$appfolder . $routes['default'];
        }
    }