<?php

namespace MemePuush;


use Imagick;

use MemePuush\Caption;
use MemePuush\Output\AbstractOutput;
use MemePuush\Output\File;
use MemePuush\Output\Puush;

class Image
{
    /**
     * @var Imagick
     */
    protected $image;

    /**
     * @var AbstractOutput
     */
    protected $output;

    /**
     * @var array
     */
    private $imageProperties = array();

    /**
     * @var int
     */
    protected $height;

    /**
     * @var int
     */
    protected $width;

    /**
     * @var Caption
     */
    protected $topCaption;

    /**
     * @var Caption
     */
    protected $bottomCaption;

    /**
     * @var string
     */
    protected $outputFormat;

    /**
     * @var string
     */
    protected $apiKey;

    public function __construct( $url = '' )
    {
        if( $url )
            $this->setImage( new Imagick( $url ) );

        return $this;
    }

    /**
     * @param \Imagick $image
     */
    public function setImage( Imagick $image )
    {
        $this->image = $image;
    }

    /**
     * @return \Imagick
     */
    public function getImage()
    {
        return $this->image;
    }

    private function getImageProperties()
    {
        if( !$this->imageProperties )
            $this->imageProperties = $this->image->getImageGeometry();

        return $this->imageProperties;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        if( !$this->height )
        {
            $properties = $this->getImageProperties();

            $this->height = $properties[ 'height' ];
        }

        return $this->height;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        if( !$this->width )
        {
            $properties = $this->getImageProperties();

            $this->width = $properties[ 'width' ];
        }

        return $this->width;
    }

    /**
     * @param string $topCaption
     */
    public function setTopCaption( $topCaption )
    {
        $this->topCaption = new Caption( $this, $topCaption, 'top' );
    }

    /**
     * @return Caption
     */
    public function getTopCaption()
    {
        return $this->topCaption;
    }

    /**
     * @param string $bottomCaption
     */
    public function setBottomCaption( $bottomCaption )
    {
        $this->bottomCaption = new Caption( $this, $bottomCaption, 'bottom' );
    }

    /**
     * @return Caption
     */
    public function getBottomCaption()
    {
        return $this->bottomCaption;
    }

    /**
     * @param        $format
     * @param string $apiKey
     */
    public function setOutputFormat( $format, $apiKey = '' )
    {
        $this->outputFormat = $format;
        $this->apiKey       = $apiKey;
    }

    /**
     * @return string
     */
    public function getOutputFormat()
    {
        return $this->outputFormat;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @return \Imagick
     */
    public function output()
    {
        switch( strtolower( $this->outputFormat ) )
        {
            case 'puush':
                $this->output = new Puush( $this->apiKey );
                break;
            default:
                //generate file
                $this->output = new File();
                break;
        }

        if( !$this->image )
            throw new \Exception( 'Could not generate image output' );

        $this->output->addHashInput( $this->image->getImageSignature() );
        $this->output->addHashInput( $this->topCaption->getText() );
        $this->output->addHashInput( $this->bottomCaption->getText() );

        if( !$this->output->exists() )
        {
            if( !$this->topCaption->isEmpty() )
                $this->topCaption->annotateImage();

            if( !$this->bottomCaption->isEmpty() )
                $this->bottomCaption->annotateImage();

            $this->image->setImageFormat( "jpg" );
            $this->image->setCompression( Imagick::COMPRESSION_JPEG );
            $this->image->setCompressionQuality( 70 );

            $this->output->upload( $this->image );
        }

        return $this->output->getOutputPath();
    }
}