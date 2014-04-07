<?php

namespace MemePuush\Output;


use Guzzle\Http\Client;
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

    /**
     * @var \Guzzle\Http\Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $url;

    public function __construct( $apiKey )
    {
        $this->setApiKey( $apiKey );

        //set the puu.sh api url
        $this->url = 'https://puush.me/api/up';
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
     * @param \Guzzle\Http\Client $client
     */
    public function setHttpClient( Client $client )
    {
        $this->client = $client;
    }

    public function getHttpClient()
    {
        if( !$this->client )
            $this->client = new Client( $this->url );

        return $this->client;
    }

    /**
     * @param Imagick $image
     *
     * @throws \Exception
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

        $fields = array(
            'k' => urlencode( $this->getApiKey() ),
            'z' => urlencode( 'poop' ),
            'f' => '@' . parent::getOutputPath()
        );

        //set POST variables
        $request = $this->getHttpClient()->post( null, array(), $fields, array( 'exceptions' => false ) );

        $response = $request->send();

        switch( $response->getStatusCode() )
        {
            case 200:

                //split the response
                $output = explode( ',', $response->getBody( true ) );

                //Response (upload, success): 0,{url},{id},{size}
                //Response (failure): -1

                //if the response was successful, return the url
                $this->outputPath = ( $output[ 0 ] == 0 ) ? $output[ 1 ] : '';

                break;

            default:
                throw new \Exception( 'Could not upload meme to puu.sh.  Response code returned: ' . $response->getStatusCode() );
                break;
        }
    }
}
