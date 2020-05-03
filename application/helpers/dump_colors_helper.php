<?php

if (! function_exists('dump_success')) {
    /**
     * @param string $message
     * @param bool $br
     */
    function dump_success(string $message = '', $br = true) {
        dump_color($message, 'success', $br);
    }
}

if (! function_exists('dump_error')) {
    /**
     * @param string $message
     * @param bool $br
     */
    function dump_error(string $message = '', $br = true) {
        dump_color($message, 'error', $br);
    }

    /**
     * @param string $message
     * @param bool $br
     */
    function dump_danger(string $message = '', $br = true) {
        dump_error($message, 'error', $br);
    }

    /**
     * @param string $message
     * @param bool $br
     */
    function dump_fail(string $message = '', $br = true) {
        dump_error($message, 'error', $br);
    }
}

if (! function_exists('dump_warning')) {
    /**
     * @param string $message
     * @param bool $br
     */
    function dump_warning(string $message = '', $br = true) {
         dump_color($message, 'warning', $br);
    }
}

if (! function_exists('dump_info')) {
    /**
     * @param string $message
     * @param bool $br
     */
    function dump_info(string $message = '', $br = true) {
        dump_color($message, 'info', $br);
    }
}

if (! function_exists('dump_color'))
{
    /**
     * @param string $message
     * @param string $color
     * @param bool $br
     */
    function dump_color(string $message = '', $color = 'default', $br = true)
    {
        $colors = [
            'success' => '1;32',
            'error'   => '1;31',
            'info'    => '1;34',
            'warning' => '1;33'
        ];

        $br = $br ? "\n" : '';
        if ($color == 'default' || ! key_exists($color, $colors)) {
            echo $message . $br;
            return;
        }

        echo "\e[".$colors[$color]."m". trim($message) ."\e[0m" . $br;
    }
}
