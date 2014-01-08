<?php

try
{

    //output type
    $output = strtolower( ( isset( $_GET[ 'output' ] ) ) ? $_GET[ 'output' ] : 'file' );

    //set api key
    $apiKey = strtoupper( ( isset( $_GET[ 'apiKey' ] ) ) ? $_GET[ 'apiKey' ] : '' );

    //caption to write on the image
    $caption = strtoupper( ( isset( $_GET[ 'caption' ] ) ) ? $_GET[ 'caption' ] : '' );

    //location - top or bottom
    $location = ( isset( $_GET[ 'location' ] ) ) ? $_GET[ 'location' ] : 'top';

    //source image to overlay
    $img = ( isset( $_GET[ 'img' ] ) ) ? $_GET[ 'img' ] : '';

    if( $output == 'file' && !$apiKey )
        throw new \Exception( 'Puu.sh api key must be provided' );

    if( !$caption )
        throw new \Exception( 'No caption provided' );

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

    //get the target font size
    list( $fontSize, $caption, $textProperties ) = getFontSize( $image, $draw, $caption );

    $draw->setFontSize( $fontSize );
    $captionRows = substr_count( $caption, "\n" ) + 1;

    //set stroke based on font size
    $strokeWidth = (int) min( array( 5, ceil( $fontSize / 12 ) ) );
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
            if( $captionRows == 1 )
                $yPos = $h - $textProperties[ 'boundingBox' ][ 'y2' ];
            else
                $yPos = $h - $textProperties[ 'textHeight' ];
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
}
catch( \Exception $e )
{
    ?>
<html>
<head>
    <title>Meme Generator</title>
    <style>
        .text input {
            width: 400px;
        }
    </style>
</head>
<body>

<form action="index.php">
    <table>
        <tr>
            <td><label for="output">Output Type:</label></td>
            <td>
                <input id="output" type="radio" name="output" value="file" <?php echo ( $output == 'file' ) ? 'checked' : ''?>/> File<br/>
                <input type="radio" name="output" value="screen" <?php echo ( $output != 'file' ) ? 'checked' : ''?>/> Screen
            </td>
        </tr>
        <tr class="text">
            <td><label for="apiKey">puu.sh Api Key:</label></td>
            <td><input id="apiKey" type="text" name="apiKey" value="<?php echo isset( $apiKey ) ? $apiKey : ''; ?>"/></td>
        </tr>
        <tr class="text">
            <td><label for="img">Url:</label></td>
            <td><input id="img" type="text" name="img" value="<?php echo isset( $img ) ? $img : ''; ?>"/></td>
        </tr>
        <tr class="text">
            <td><label for="caption">Caption:</label></td>
            <td><input id="caption" type="text" name="caption" value="<?php echo isset( $caption ) ? $caption : ''; ?>"/></td>
        </tr>
        <tr>
            <td><label for="location">Location:</label></td>
            <td>
                <input id="location" type="radio" name="location" value="top" <?php echo ( $location == 'top' ) ? 'checked' : ''?>/> Top<br/>
                <input type="radio" name="location" value="bottom" <?php echo ( $location != 'top' ) ? 'checked' : ''?>/> Bottom
            </td>
        </tr>

    </table>
    <button type="submit">Create Meme</button>
</form>

Error: <?php echo $e->getMessage(); ?>

</body>
</html>


<?php
}

/**
 * @param Imagick     $image
 * @param ImagickDraw $draw
 * @param             $caption
 * @param int         $rows
 *
 * @return array
 */
function getFontSize( Imagick $image, ImagickDraw $draw, $caption, $rows = 1 )
{
    // Create an array for the textwidth and textheight
    $textProperties = array( 'textWidth' => 0 );

    //make sure text is no wider than 94% of image size
    $imgSize             = $image->getImageGeometry();
    $textDesiredWidth    = intval( $imgSize[ 'width' ] * .88 );
    $minTextDesiredWidth = intval( $imgSize[ 'width' ] * .60 );

    //set the max and min font sizes based on the height and string length
    $maxFont = floor( $imgSize[ 'height' ] * .065 ) * ( min( 1, ( $imgSize[ 'height' ] * .33 ) / strlen( $caption ) ) );
    $minFont = floor( $imgSize[ 'height' ] * .055 ) * ( min( 1, ( $imgSize[ 'height' ] * .33 ) / strlen( $caption ) ) );

    // Set an initial value for the fontsize, will be increased in the loop below
    $fontSize = 0;

    // Increase the fontsize until we have reached our desired width
    while( $textProperties[ 'textWidth' ] <= $textDesiredWidth )
    {
        $draw->setFontSize( $fontSize );
        $draw->setTextKerning( min( 4, $fontSize / 24 ) );
        $textProperties = $image->queryFontMetrics( $draw, $caption );
        $fontSize++;

        //set a threshold so the font doesn't get too big for short strings
        if( $fontSize >= ( $maxFont * 2 ) )
            break;

        //try to fill the horizontal space
        if( $fontSize >= $maxFont && $textProperties[ 'textWidth' ] >= $minTextDesiredWidth )
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
        $caption,
        $textProperties
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