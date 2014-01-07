<?php

//set the output type
$output = 'file';

if( isset( $output ) && $output == 'file' )
{
    //set api key
    $apiKey = strtoupper( ( isset( $_GET[ 'apiKey' ] ) ) ? $_GET[ 'apiKey' ] : '' );

    if( !$apiKey )
        throw new \Exception( 'Puu.sh api key must be provided' );
}

//caption to write on the image
$caption = strtoupper( ( isset( $_GET[ 'caption' ] ) ) ? $_GET[ 'caption' ] : '' );

//location - top or bottom
$location = ( isset( $_GET[ 'location' ] ) ) ? $_GET[ 'location' ] : 'top';

//source image to overlay
$img = ( isset( $_GET[ 'img' ] ) ) ? $_GET[ 'img' ] : '';

if( !$img )
    throw new \Exception( 'No image url provided' );

//get the source width and height of the image url
list( $w, $h ) = getimagesize( $img );

//if the image doesn't exist, cannot continue
if( $w == 0 || $h == 0 )
    throw new \Exception( 'Could not load image: ' . $img );

//create the base image
$image = new Imagick( $img );

$draw = new ImagickDraw();
//set the font
$draw->setFont( './impact.ttf' );
$draw->setFontSize( 72 );
//set stroke colour to black
$draw->setStrokeColor( new ImagickPixel( "#000000" ) );
$draw->setStrokeAntialias( true );
$draw->setTextAntialias( true );
//north gravity is top center
$draw->setGravity( Imagick::GRAVITY_NORTH );

//set alignment to center
$draw->setTextAlignment( 2 );

$fontSize = 0;
$rows     = 1;

$imgSize          = $image->getImageGeometry();
$textDesiredWidth = intval( $imgSize[ 'width' ] * .90 );

list( $fontSize, $caption ) = getFontSize( $image, $draw, $caption );

$draw->setFontSize( $fontSize );
$captionRows = substr_count( $caption, "\n" ) + 1;

//set stroke based on font size
$strokeWidth = (int) min( array( 2, ceil( $fontSize / 12 ) ) );
$draw->setStrokeWidth( $strokeWidth );

//set the new font size
$draw->setFontSize( $fontSize );

//set font colour to black initially to create a smooth stroke
$draw->setFillColor( new ImagickPixel( "#000000" ) );
//and make the stroke transparent
$draw->setStrokeAlpha( 100 );

//center the text horizontally
$xPos = $w / 2;

//determine the vertical position based on the top / bottom location
switch( $location )
{
    case 'top':
        $yPos = $fontSize + 10;
        break;
    case 'bottom':
    default:
        $yPos = $h - ( $fontSize * $captionRows ) - 10;
        break;
}

//write the text on the image
$image->annotateImage( $draw, $xPos, $yPos, 0, $caption );

//now change the fill colour to black
$draw->setFillColor( new ImagickPixel( '#FFFFFF' ) );
//and make the stroke transparent
$draw->setStrokeAlpha( 0 );

//re-write the text on the image
$image->annotateImage( $draw, $xPos, $yPos, 0, $caption );
$image->setImageFormat( "jpg" );
$image->setCompression( Imagick::COMPRESSION_JPEG );
$image->setCompressionQuality( 70 );

if( isset( $output ) && $output == 'file' )
{
    $filename = '/tmp/' . microtime( true ) . '.jpg';

    //finally output the image
    $image->writeimage( $filename );

    echo json_encode( array( 'url' => uploadImage( $filename, $apiKey ) ) );

    //remove the file
    unlink( $filename );
}
else
{
    header( 'Content-type: image/jpg' );
    echo $image;
}

/**
 * @param Imagick     $image
 * @param ImagickDraw $draw
 * @param             $caption
 *
 * @return int
 */
function getFontSize( Imagick $image, ImagickDraw $draw, $caption, $rows = 1 )
{
    // Create an array for the textwidth and textheight
    $textProperties = array( 'textWidth' => 0 );

    //make sure text is no wider than 94% of image size
    $imgSize          = $image->getImageGeometry();
    $textDesiredWidth = intval( $imgSize[ 'width' ] * .88 );

    //set the max and min font sizes based on the height and string length
    $maxFont = floor( $imgSize[ 'height' ] * .065 );
    $minFont = floor( $imgSize[ 'height' ] * .045 ) * ( min( 1, ( $imgSize[ 'height' ] * .33 ) / strlen( $caption ) ) );

    // Set an initial value for the fontsize, will be increased in the loop below
    $fontSize = 0;

    // Increase the fontsize until we have reached our desired width
    while( $textProperties[ 'textWidth' ] <= $textDesiredWidth )
    {
        $draw->setFontSize( $fontSize );
        $textProperties = $image->queryFontMetrics( $draw, $caption );
        $fontSize++;

        if( $fontSize >= $maxFont )
            break;
    }

    //if the calculated font size is not within the threshold,
    // wrap the text and try again
    if( $fontSize < $minFont )
    {
        $wrapLength = intval( strlen( $caption ) / $rows );

        //remove the newlines
        $caption = str_replace( "\n", ' ', $caption );

        //re-wrap caption
        $caption = wordwrap( $caption, $wrapLength, "\n", true );

        return getFontSize( $image, $draw, $caption, $rows + 1 );
    }

    return array(
        $fontSize,
        $caption
    );
}

/**
 * @param $filename
 * @param $apiKey
 *
 * @return mixed
 */
function uploadImage( $filename, $apiKey )
{
    //set POST variables
    $url    = 'https://puush.me/api/up';
    $fields = array(
        'k' => urlencode( $apiKey ),
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
        return $response[ 1 ];

    //otherwise return error
    return $response[ 0 ];
}