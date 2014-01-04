<?php
//set api key
$apiKey = strtoupper( ( isset( $_GET[ 'apiKey' ] ) ) ? $_GET[ 'apiKey' ] : '' );

if( !$apiKey )
    throw new \Exception( 'Puu.sh api key must be provided' );

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

//set the max and min font sizes
$maxFont = 72;
$minFont = 36;

//initially the font size is the max
$fontSize = $maxFont;

//create the base image
$image = new Imagick( $img );

$draw = new ImagickDraw();
//set the font
$draw->setFont( './impact.ttf' );
$draw->setFontSize( $fontSize );
//set stroke colour to black
$draw->setStrokeColor( new ImagickPixel( "#000000" ) );
$draw->setStrokeAntialias( true );
$draw->setTextAntialias( true );
//north gravity is top center
$draw->setGravity( Imagick::GRAVITY_NORTH );

//set alignment to center
$draw->setTextAlignment( 2 );

//make sure text is no wider than 94% of image size
$imgSize  = $image->getImageGeometry();
$maxWidth = intval( $imgSize[ 'width' ] * .94 );

//get the font metrics for the initial caption
$metrics = $image->queryFontMetrics( $draw, $caption );
//determine the font ratio and the width ratio
$fontRatio = $fontSize / $minFont;
$textRatio = $metrics[ 'textWidth' ] / $maxWidth;

// if the textWidth/maxLength ratio is greater than the font ratio split the text
if( $textRatio > $fontRatio )
{
    $wrapLength = intval( strlen( $caption ) * .55 );
    $caption    = wordwrap( $caption, $wrapLength, "\n", true );

    // make it a bit smaller since it will be multiline
    $fontSize = floor( $fontSize * .8 );
    $draw->setFontSize( $fontSize );
    //get new metrics
    $metrics = $image->queryFontMetrics( $draw, $caption );
}

// See if additional shrinking is needed
$textRatio = $metrics[ 'textWidth' ] / $maxWidth;
if( $textRatio > 1 )
    $fontSize = floor( $fontSize * ( 1 / $textRatio ) );

//set stroke based on font size
$strokeWidth = (int) max( array( 1, ceil( $fontSize / 12 ) ) );
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
        $yPos = $h - ( 20 * ( $maxFont / $fontSize ) );
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

$filename = '/tmp/' . microtime( true ) . '.jpg';

//finally output the image
$image->setImageFormat( "jpg" );
$image->setCompression( Imagick::COMPRESSION_JPEG );
$image->setCompressionQuality( 70 );
$image->writeimage( $filename );

echo uploadImage( $filename, $apiKey );

//remove the file
unlink( $filename );

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