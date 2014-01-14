<?php

namespace MemePuush;


use Imagick;

class Image
{
    /**
     * @var Imagick
     */
    protected $image;

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

    public function __construct( $url )
    {
        $this->image = new Imagick( $url );

        return $this;
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
     * @param string $bottomCaption
     */
    public function setBottomCaption( $bottomCaption )
    {
        $this->bottomCaption = new Caption( $this, $bottomCaption, 'bottom' );
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
     * @return \Imagick
     */
    public function output()
    {
        if( !$this->topCaption->isEmpty() )
            $this->topCaption->annotateImage();

        if( !$this->bottomCaption->isEmpty() )
            $this->bottomCaption->annotateImage();

        $this->image->setImageFormat( "jpg" );
        $this->image->setCompression( Imagick::COMPRESSION_JPEG );
        $this->image->setCompressionQuality( 70 );

        switch( $this->outputFormat )
        {
            case 'file':
                $this->uploadImage();
                break;
            default:
                $this->echoImage();
                break;
        }
    }

    /**
     * @return mixed
     */
    public function uploadImage()
    {
        //set filename
        $filename = '/tmp/' . microtime( true ) . '.jpg';

        //finally output the image
        $this->image->writeimage( $filename );

        //set POST variables
        $url    = 'https://puush.me/api/up';
        $fields = array(
            'k' => urlencode( $this->apiKey ),
            'z' => urlencode( 'poop' ),
            'f' => '@' . $filename
        );

        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_POST, count( $fields ) );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        //execute post
        $result = curl_exec( $ch );

        //close connection
        curl_close( $ch );

        $response = explode( ',', $result );

        //if the response was successful, return the url
        if( $response[ 0 ] == 0 )
        {
            $url = $response[ 1 ];
        }
        else
        {
            //otherwise return error
            $url = $response[ 0 ];
        }

        //remove the file
        unlink( $filename );

        echo json_encode( array( 'url' => $url ) );
    }

    public function echoImage()
    {
        header( 'Content-type: image/jpg' );
        echo $this->image;
    }
}