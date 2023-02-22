<?php

namespace App\Financial;

class Buffer {
    var $data;
    var $len;
    var $ptr;

    function __construct($data, $ptr=0){
        $this->data=$data;
        $this->len=strlen($data);
        $this->ptr=$ptr;
    }

    function shift($chars){
        $prefix=substr($this->data, $this->ptr, $chars);
        $this->ptr+=$chars;
        return $prefix;
    }

    function shift_unpack($chars, $format, $reverse=false){
        $data=$this->shift($chars);
        if ($reverse)
            $data=strrev($data);
        $unpack=unpack($format, $data);
        return reset($unpack);
    }

    function shift_varint(){
        $value=$this->shift_unpack(1, 'C');

        if ($value==0xFF)
            $value=$this->shift_uint64();
        elseif ($value==0xFE)
            $value=$this->shift_unpack(4, 'V');
        elseif ($value==0xFD)
            $value=$this->shift_unpack(2, 'v');

        return $value;
    }

    function shift_uint64(){
        return $this->shift_unpack(4, 'V')+($this->shift_unpack(4, 'V')*4294967296);
    }

    function used(){
        return min($this->ptr, $this->len);
    }

    function remaining(){
        return max($this->len-$this->ptr, 0);
    }
}