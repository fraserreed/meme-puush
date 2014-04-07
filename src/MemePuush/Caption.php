<?php

namespace MemePuush;


use Imagick;
use ImagickDraw;
use ImagickPixel;

class Caption
{
    /**
     * @var Imagick
     */
    protected $target;

    /**
     * @var ImagickDraw
     */
    protected $draw;

    /**
     * @var ImagickPixel
     */
    protected $pixel;

    /**
     * @var int
     */
    protected $gravity;

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
     * @var string
     */
    protected $fontPath = 'fonts/impact.ttf';

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
     * @var int
     */
    protected $multiplier = 4;
    /**
     * @var bool
     */
    protected $debug = false;

    public function __construct( Image $image, $text, $location = 'top' )
    {
        $this->target = $image;
        $this->setText( $text );
        $this->setLocation( $location );

        //set border params based on location
        if( $this->location == 'top' )
        {
            $this->boundingBox = array(
                'x1'     => 2,
                'x2'     => (int) ( $this->target->getWidth() - 2 ),
                'x'      => (int) ( $this->target->getWidth() / 2 ),
                'width'  => (int) $this->target->getWidth(),
                'y1'     => 2,
                'y2'     => (int) ( ( $this->target->getHeight() / $this->multiplier ) - 2 ),
                'y'      => 10,
                'height' => (int) ( $this->target->getHeight() / $this->multiplier )

            );
        }
        else
        {
            $this->boundingBox = array(
                'x1'     => 2,
                'x2'     => (int) ( $this->target->getWidth() - 2 ),
                'x'      => (int) ( $this->target->getWidth() / 2 ),
                'width'  => (int) $this->target->getWidth(),
                'y1'     => (int) ( $this->target->getHeight() - ( $this->target->getHeight() / $this->multiplier ) - 2 ),
                'y2'     => (int) ( $this->target->getHeight() - 2 ),
                'y'      => (int) ( $this->target->getHeight() - ( $this->target->getHeight() / $this->multiplier ) - 2 ),
                'height' => (int) ( $this->target->getHeight() / $this->multiplier )
            );
        }
    }

    public function setDebug( $debug )
    {
        $this->debug = $debug;
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
     * @param $location
     */
    private function setLocation( $location )
    {
        $this->location = $location;
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
     * @param string $fontPath
     */
    public function setFontPath( $fontPath )
    {
        $this->fontPath = $fontPath;
    }

    /**
     * @return string
     */
    public function getFontPath()
    {
        return $this->fontPath;
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
    public function getFontSize()
    {
        return (int) $this->fontSize;
    }

    /**
     * @return array
     */
    public function getBoundingBox()
    {
        return $this->boundingBox;
    }

    /**
     * @param \ImagickDraw $draw
     */
    public function setDraw( ImagickDraw $draw )
    {
        $this->draw = $draw;
    }

    /**
     * @return \ImagickDraw
     */
    private function getDraw()
    {
        if( !$this->draw )
            $this->draw = new ImagickDraw();

        return $this->draw;
    }

    /**
     * @param \ImagickPixel $pixel
     */
    public function setPixel( ImagickPixel $pixel )
    {
        $this->pixel = $pixel;
    }

    /**
     * @param $color
     *
     * @return \ImagickPixel
     */
    private function getPixel( $color )
    {
        if( !$this->pixel )
            $this->pixel = new ImagickPixel( $color );

        return $this->pixel;
    }

    /**
     * @param int $gravity
     */
    public function setGravity( $gravity )
    {
        $this->gravity = $gravity;
    }

    /**
     * @return int
     */
    private function getGravity()
    {
        if( !$this->gravity )
            $this->gravity = Imagick::GRAVITY_NORTH;

        return $this->gravity;
    }

    /**
     * @return ImagickDraw
     */
    public function getDrawLayer()
    {
        //initialize the draw layer
        $drawLayer = $this->getDraw();

        //set the font
        $drawLayer->setFont( $this->getFontPath() );

        //set stroke colour to black
        $drawLayer->setStrokeColor( $this->getPixel( "#000000" ) );
        $drawLayer->setStrokeAntialias( true );
        $drawLayer->setTextAntialias( true );

        //north gravity is top center
        $drawLayer->setGravity( $this->getGravity() );

        //set alignment to center
        $drawLayer->setTextAlignment( 2 );

        //set text spacing
        $drawLayer->setTextKerning( 0.75 );

        //set font colour to black initially to create a smooth stroke
        $drawLayer->setFillColor( $this->getPixel( "#000000" ) );

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

        // Increase the fontsize until we have reached our desired width
        while( $textProperties[ 'textWidth' ] <= $textDesiredWidth )
        {
            $drawLayer->setFontSize( $this->getFontSize() );

            //set a threshold so the font doesn't get too big for short strings
            if( $this->getFontSize() >= ( $maxFont * 2 ) )
                break;

            $textProperties = $this->getTextProperties( $drawLayer );

            $addFont = ( $textProperties[ 'textWidth' ] > 0 ) ? ceil( $minTextDesiredWidth / $textProperties[ 'textWidth' ] ) : 1;

            $this->setFontSize( $this->getFontSize() + $addFont );

            //try to fill the horizontal space
            if( $this->getFontSize() >= $maxFont && $textProperties[ 'textWidth' ] >= $minTextDesiredWidth )
            {
                //if it overfills, set it one font size smaller
                $this->setFontSize( $this->getFontSize() - 1 );
                break;
            }
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

        if( $textProperties[ 'textWidth' ] >= $textDesiredWidth )
        {
            //if it overfills, set it one font size smaller
            $this->setFontSize( $this->getFontSize() - 1 );
        }

        //if bounding box is too high, make font size smaller until it fits
        while( $textProperties[ 'textHeight' ] >= $boundingBox[ 'height' ] + ( $this->getFontSize() - 10 ) )
        {
            $this->setFontSize( $this->getFontSize() - 1 );
            $drawLayer->setFontSize( $this->getFontSize() );
            $textProperties = $this->getTextProperties( $drawLayer );
        }
    }

    /**
     * @param \ImagickDraw $drawLayer
     *
     * @return array
     */
    private function getTextProperties( ImagickDraw $drawLayer )
    {
        $image = $this->target->getImage();

        return $image->queryFontMetrics( $drawLayer, $this->getText() );
    }
}