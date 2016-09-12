<?php

namespace Tourbuzz\Format;

/**
 * Class Ov2File
 * @package App\Format
 * (c) 2006 Sid Baldwin
 * Created on 06-Feb-2006
 */
class Ov2File
{
    /**
     * @var string
     */
    public $content = "";

    /**
     * @param float $lat
     * @param float $long
     * @param string $text
     * @return null
     */
    function add_POI($lat,$long,$text) {
        $this->content .= "\x02";
        $this->content .= pack("I", 14 + strlen($text));
        $this->content .= pack("i", round($long*100000));
        $this->content .= pack("i", round($lat*100000));
        $this->content .= $text;
        $this->content .= "\x00";
        return;
    }
}