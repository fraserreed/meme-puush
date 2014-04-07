<?php

namespace MemePuush\Output;


use MemePuush\Output\IOutput;

abstract class AbstractOutput implements IOutput
{
    /**
     * @var string
     */
    protected $hash;

    /**
     * @var array
     */
    protected $hashInput = array();

    /**
     * @var string
     */
    protected $filename;

    /**
     * @return string
     */
    protected function getHash()
    {
        if( !$this->hash )
            $this->hash = hash( "adler32", implode( '', $this->hashInput ), false );

        return $this->hash;
    }

    /**
     * @param $string
     */
    public function addHashInput( $string )
    {
        $this->hashInput[ ] = $string;

        //reset hash and filename
        $this->hash     = '';
        $this->filename = '';
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        //set filename if it hasn't already been set
        if( empty( $this->filename ) )
            $this->filename = $this->getHash() . '.jpg';

        return $this->filename;
    }
}