<?php

namespace MemePuush\Output;


use Imagick;
use MemePuush\Output\AbstractOutput;

class File extends AbstractOutput
{
    const PATH = '/tmp/img/';

    /**
     * @return string
     */
    protected function getDirectory()
    {
        return self::PATH;
    }

    /**
     * @return string
     */
    public function getOutputPath()
    {
        return $this->getDirectory() . $this->getFilename();
    }

    /**
     * @return bool
     */
    public function exists()
    {
        if( file_exists( $this->getOutputPath() ) )
            return true;

        return false;
    }

    /**
     * @param Imagick $image
     *
     * @return string
     */
    public function upload( Imagick $image )
    {
        //finally output the image if it doesn't exist yet
        if( !$this->exists() )
        {
            if( !file_exists( $this->getDirectory() ) )
                mkdir( $this->getDirectory() );

            $image->writeImage( $this->getOutputPath() );
        }
    }
}
