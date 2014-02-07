<?php

namespace MemePuush\Output;


use Imagick;
use MemePuush\Output\File;

class Puush extends File
{
    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $outputPath;

    public function __construct( $apiKey )
    {
        $this->setApiKey( $apiKey );
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey( $apiKey )
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @return string
     */
    public function getOutputPath()
    {
        return $this->outputPath;
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
            if( !file_exists( parent::getDirectory() ) )
                mkdir( parent::getDirectory() );

            $image->writeImage( parent::getOutputPath() );
        }

        //set POST variables
        $url    = 'https://puush.me/api/up';
        $fields = array(
            'k' => urlencode( $this->getApiKey() ),
            'z' => urlencode( 'poop' ),
            'f' => '@' . parent::getOutputPath()
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
            $this->outputPath = $response[ 1 ];
        }
        else
        {
            //otherwise return error
            $this->outputPath = $response[ 0 ];
        }
    }
}
