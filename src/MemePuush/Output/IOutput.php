<?php

namespace MemePuush\Output;


use Imagick;

interface IOutput
{

    /**
     * @return string
     */
    public function getOutputPath();

    /**
     * @return bool
     */
    public function exists();

    /**
     * @param Imagick $image
     *
     * @return string
     */
    public function upload( Imagick $image );
}