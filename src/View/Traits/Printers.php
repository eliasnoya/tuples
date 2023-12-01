<?php

namespace Tuples\View\Traits;

trait Printers
{
    /**
     * Dump the array or object for debugging purposes.
     *
     * @param array|object $array The array or object to be dumped.
     *
     * @return void
     */
    public function debug(array|object $array): void
    {
        dump($array);
    }

    /**
     * Echo the JSON representation of the array.
     *
     * @param array $content The content to be converted to JSON.
     *
     * @return void
     */
    public function json(array $content): void
    {
        $this->echo(json_encode($content));
    }

    /**
     * Echo the uppercase version of the string.
     *
     * @param string $content The content to be converted to uppercase.
     *
     * @return void
     */
    public function upper(string $content): void
    {
        $this->echo(strtoupper($content));
    }

    /**
     * Echo the lowercase version of the string.
     *
     * @param string $content The content to be converted to lowercase.
     *
     * @return void
     */
    public function lower(string $content): void
    {
        $this->echo(strtolower($content));
    }

    /**
     * Echo the string with the first character in uppercase.
     *
     * @param string $content The content with the first character in uppercase.
     *
     * @return void
     */
    public function firstUpper(string $content): void
    {
        $this->echo(ucfirst($content));
    }

    /**
     * Echo the string with the first character of each word in uppercase.
     *
     * @param string $content The content with the first character of each word in uppercase.
     *
     * @return void
     */
    public function wordsFirstUpper(string $content): void
    {
        $this->echo(ucwords($content));
    }

    /**
     * Echo the HTML-safe version of the string.
     *
     * @param string $content The content to be echoed in HTML-safe format.
     *
     * @return void
     */
    public function echo(string $content): void
    {
        echo @htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Echo the raw content without HTML escaping.
     *
     * @param string $content The content to be echoed as-is.
     *
     * @return void
     */
    public function raw(string $content): void
    {
        echo $content;
    }
}
