<?php
namespace Contract;

interface ParserContract {

    /**
     * 解析
     */
    public static function parse($obj, $options = []);
}
