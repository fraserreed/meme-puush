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

    public function __construct( Image $image, $text, $location = 'top' )
    {
        $this->target   = $image;
        $this->text     = $text;
        $this->location = $location;
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
     * @return int
     */
    public function getX()
    {
        return (int) $this->target->getWidth() / 2;
    }

    public function getY()
    {
        //determine the vertical position based on the top / bottom location
        switch( $this->getLocation() )
        {
            case 'top':
                return $this->getFontSize() + 10;
            case 'bottom':
            default:
                //if( $captionRows == 1 )
                //    $yPos = $h - $textProperties[ 'boundingBox' ][ 'y2' ];
                //else
                return $this->target->getHeight() - 100; //$textProperties[ 'textHeight' ];
            //break;
        }
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

        //write the initial caption
        $this->target->getImage()->annotateImage( $drawLayer, $this->getX(), $this->getY(), 0, $this->getText() );

        //now change the fill colour to black
        $drawLayer->setFillColor( new ImagickPixel( '#FFFFFF' ) );
        //and make the stroke transparent
        $drawLayer->setStrokeAlpha( 0 );

        //re-write the text on the image
        $this->target->getImage()->annotateImage( $drawLayer, $this->getX(), $this->getY(), 0, $this->getText() );
    }

    private function calculateFontSize( ImagickDraw $drawLayer, $rows = 1 )
    {
        $image = $this->target;

        // Create an array for the textwidth and textheight
        $textProperties = array( 'textWidth' => 0 );

        //make sure text is no wider than 78% of image size

        $textDesiredWidth    = intval( $image->getWidth() * .94 );
        $minTextDesiredWidth = intval( $image->getWidth() * .75 );

        //set the max and min font sizes based on the height and string length
        $maxFont = floor( $image->getHeight() * .070 ) * ( min( 1, ( $image->getHeight() * .33 ) / $this->getStringLength() ) );
        $minFont = floor( $image->getHeight() * .060 ) * ( min( 1, ( $image->getHeight() * .33 ) / $this->getStringLength() ) );

        // Increase the fontsize until we have reached our desired width
        while( $textProperties[ 'textWidth' ] <= $textDesiredWidth )
        {
            $drawLayer->setFontSize( $this->getFontSize() );
            $drawLayer->setTextKerning( min( 4, $this->getFontSize() / 24 ) );
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
    }
}