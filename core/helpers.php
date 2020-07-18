<?php

    namespace framer;

    class Helpers
    {
        public static function log( $content )
        {
            $f = date('Ymd');
            $c = @\file_get_contents(__DIR__ . '/../logs/' . $f) ?? '';
            $c = $c . "\n\n" . $content;

            \file_put_contents(__DIR__ . '/../logs/' . $f, $c);
        }

        public static function dumplog( $var )
        {
            \ob_start();
            var_dump( $var );
            \ob_end_flush();

            $content = \ob_get_contents();
            self::log($content);

            \ob_clean();
        }

        public static function replaceWhenEmpty( $var, $replace, $consideredasempty=false )
        {
            if ( $consideredasempty !== false )
            {
                return $var != $consideredasempty ? $var : $replace;
            }
            else {
                return strlen( $var ) ? $var : $replace;
            }
        }
    }
