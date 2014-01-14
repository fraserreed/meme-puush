<?php

namespace MemePuush;


use Imagick, ImagickDraw, ImagickPixel;

class Caption
{
    /**
     * @var Imagick
     */
    protected $target;

    /**
     * @var string
     */
    protected $text;

    /**
     * @var string
     */
    protected $location;

    /**
     * @var int
     */
    protected $stringLength;

    /**
     * @var int
     */
    protected $fontSize;

    /**
     * @var ImagickDraw
     */
    protected $drawLayer;

    /**
     * @var array
     */
    protected $boundingBox;

    /**
     * @var bool
     */
    protected $debug = false;

    public function __construct( Image $image, $text, $location = 'top' )
    {
        $this->target   = $image;
        $this->text     = $text;
        $this->location = $location;

        //set border params based on location
        if( $this->location == 'top' )
        {
            $this->boundingBox = array(
                'x1'     => 2,
                'x2'     => (int) ( $this->target->getWidth() - 2 ),
                'x'      => (int) ( $this->target->getWidth() / 2 ),
                'width'  => (int) $this->target->getWidth(),
                'y1'     => 2,
                'y2'     => (int) ( ( $this->target->getHeight() / 3 ) - 2 ),
                'y'      => 10,
                'height' => (int) ( $this->target->getHeight() / 3 )

            );
        }
        else
        {
            $this->boundingBox = array(
                'x1'     => 2,
                'x2'     => (int) ( $this->target->getWidth() - 2 ),
                'x'      => (int) ( $this->target->getWidth() / 2 ),
                'width'  => (int) $this->target->getWidth(),
                'y1'     => (int) ( 2 * ( $this->target->getHeight() / 3 ) - 2 ),
                'y2'     => (int) ( $this->target->getHeight() - 2 ),
                'y'      => (int) ( 2 * ( $this->target->getHeight() / 3 ) - 2 ),
                'height' => (int) ( $this->target->getHeight() / 3 )
            );
        }
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return ( $this->getStringLength() > 0 ) ? false : true;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return strtoupper( $this->text );
    }

    /**
     * @param string $text
     */
    private function setText( $text )
    {
        $this->text = $text;
    }

    /**
     * @return int
     */
    public function getStringLength()
    {
        return strlen( $this->text );
    }

    /**
     * @return int
     */
    public function getFontSize()
    {
        return (int) $this->fontSize;
    }

    /**
     * @param int $fontSize
     */
    private function setFontSize( $fontSize )
    {
        $this->fontSize = $fontSize;
    }

    /**
     * @return array
     */
    public function getBoundingBox()
    {
        return $this->boundingBox;
    }

    /**
     * @return ImagickDraw
     */
    public function getDrawLayer()
    {
        //initialize the draw layer
        $drawLayer = new ImagickDraw();

        //set the font
        $drawLayer->setFont( './font/impact.ttf' );

        //set stroke colour to black
        $drawLayer->setStrokeColor( new ImagickPixel( "#000000" ) );
        $drawLayer->setStrokeAntialias( true );
        $drawLayer->setTextAntialias( true );

        //north gravity is top center
        $drawLayer->setGravity( Imagick::GRAVITY_NORTH );

        //set alignment to center
        $drawLayer->setTextAlignment( 2 );

        //set text spacing
        $drawLayer->setTextKerning( 0.75 );

        //set font colour to black initially to create a smooth stroke
        $drawLayer->setFillColor( new ImagickPixel( "#000000" ) );

        //and make the stroke transparent
        $drawLayer->setStrokeAlpha( 100 );

        $this->calculateFontSize( $drawLayer );

        //set the font size that was calculated
        $drawLayer->setFontSize( $this->getFontSize() );

        return $drawLayer;
    }

    /**
     * Annotate the target image with the caption
     */
    public function annotateImage()
    {
        //get the initial draw layer
        $drawLayer = $this->getDrawLayer();

        $boundingBox = $this->getBoundingBox();
        $x           = $boundingBox[ 'x' ];
        $y           = $boundingBox[ 'y' ] + $this->getFontSize();

        //write the initial caption
        $this->target->getImage()->annotateImage( $drawLayer, $x, $y, 0, $this->getText() );

        //now change the fill colour to black
        $drawLayer->setFillColor( new ImagickPixel( '#FFFFFF' ) );
        //and make the stroke transparent
        $drawLayer->setStrokeAlpha( 0 );

        //re-write the text on the image
        $this->target->getImage()->annotateImage( $drawLayer, $x, $y, 0, $this->getText() );

        //if in debug mode, show bounding box rectangles
        if( $this->debug == true )
        {
            $drawLayer->setFillAlpha( 0 );
            $drawLayer->setStrokeColor( new ImagickPixel( 'white' ) );
            $drawLayer->setStrokeWidth( 2 );
            $drawLayer->rectangle( $boundingBox[ 'x1' ], $boundingBox[ 'y1' ], $boundingBox[ 'x2' ], $boundingBox[ 'y2' ] );

            $this->target->getImage()->drawImage( $drawLayer );
        }
    }

    /**
     * @param \ImagickDraw $drawLayer
     * @param int          $rows
     */
    private function calculateFontSize( ImagickDraw $drawLayer, $rows = 1 )
    {
        $boundingBox = $this->getBoundingBox();

        // Create an array for the textwidth and textheight
        $textProperties = array( 'textWidth' => 0 );

        //make sure text is no wider than 78% of image size
        $textDesiredWidth    = intval( $boundingBox[ 'width' ] * .94 );
        $minTextDesiredWidth = intval( $boundingBox[ 'width' ] * .75 );

        //set the max and min font sizes based on the height and string length
        $maxFont = floor( $boundingBox[ 'height' ] * 3 * .070 ) * ( min( 1, ( $boundingBox[ 'height' ] * 3 * .33 ) / $this->getStringLength() ) );
        $minFont = floor( $boundingBox[ 'height' ] * 3 * .060 ) * ( min( 1, ( $boundingBox[ 'height' ] * 3 * .33 ) / $this->getStringLength() ) );

        $image = $this->target;

        // Increase the fontsize until we have reached our desired width
        while( $textProperties[ 'textWidth' ] <= $textDesiredWidth )
        {
            $drawLayer->setFontSize( $this->getFontSize() );
            $textProperties = $image->getImage()->queryFontMetrics( $drawLayer, $this->getText() );
            $this->setFontSize( $this->getFontSize() + 1 );

            //set a threshold so the font doesn't get too big for short strings
            if( $this->getFontSize() >= ( $maxFont * 2 ) )
                break;

            //try to fill the horizontal space
            if( $this->getFontSize() >= $maxFont && $textProperties[ 'textWidth' ] >= $minTextDesiredWidth )
                break;
        }

        //if the calculated font size is not within the threshold,
        // wrap the text and try again
        if( $this->getFontSize() < $minFont )
        {
            $wrapLength = intval( strlen( $this->getText() ) / $rows );

            //remove the newlines
            $caption = str_replace( "\n", ' ', $this->getText() );

            //re-wrap caption
            $this->setText( wordwrap( $caption, $wrapLength, "\n", true ) );

            $this->calculateFontSize( $drawLayer, $rows + 1 );
        }

        //if bounding box is too high, make font size smaller until it fits
        while( $textProperties[ 'textHeight' ] >= $boundingBox[ 'height' ] )
        {
            $drawLayer->setFontSize( $this->getFontSize() );
            $textProperties = $image->getImage()->queryFontMetrics( $drawLayer, $this->getText() );
            $this->setFontSize( $this->getFontSize() - 1 );
        }
    }
}