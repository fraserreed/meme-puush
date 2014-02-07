<?php

function __autoload( $pClassName )
{
    require_once( '../src/' . str_replace( "\\", "/", $pClassName . '.php' ) );
}

use MemePuush\Image;

if( !empty( $_GET ) )
{
    try
    {
        //capture inputs

        //output type
        $output = strtolower( ( isset( $_GET[ 'output' ] ) ) ? $_GET[ 'output' ] : 'puush' );

        //set api key
        $apiKey = strtoupper( ( isset( $_GET[ 'apiKey' ] ) ) ? $_GET[ 'apiKey' ] : '' );

        //top caption to write on the image
        $t1 = ( isset( $_GET[ 'topCaption' ] ) ) ? $_GET[ 'topCaption' ] : '';

        //bottom caption to write on the image
        $t2 = isset( $_GET[ 'bottomCaption' ] ) ? $_GET[ 'bottomCaption' ] : '';

        //source image to overlay
        $img = ( isset( $_GET[ 'img' ] ) ) ? $_GET[ 'img' ] : '';

        if( $output == 'puush' && !$apiKey )
            throw new \Exception( 'Puu.sh api key must be provided' );

        if( !$t1 && !$t2 )
            throw new \Exception( 'No caption provided' );

        if( !$img )
            throw new \Exception( 'No image url provided' );

        $image = new Image( $img );

        //if the image doesn't exist, cannot continue
        if( !$image )
            throw new \Exception( 'Could not load image: ' . $img );

        $image->setOutputFormat( $output, $apiKey );
        $image->setTopCaption( $t1 );
        $image->setBottomCaption( $t2 );

        $outputContent = $image->output();

        switch( $output )
        {
            case 'screen':
                header( 'Content-type: image/jpg' );
                echo file_get_contents( $outputContent );
                break;

            case 'file':
                echo $outputContent;
                break;

            case 'puush':
                echo json_encode( array( 'url' => $outputContent ) );
                break;
        }
    }
    catch( \Exception $e )
    {
        echo 'Error: ' . $e->getMessage();
    }
}
else
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
                <input id="output" type="radio" name="output" value="puush" <?php echo ( empty( $output ) || $output == 'puush' ) ? 'checked' : ''?>/> Puu.sh<br/>
                <input type="radio" name="output" value="file" <?php echo ( $output == 'file' ) ? 'checked' : ''?>/> File<br/>
                <input type="radio" name="output" value="screen" <?php echo ( $output == 'screen' ) ? 'checked' : ''?>/> Screen
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
            <td><label for="topCaption">Top Caption:</label></td>
            <td><input id="topCaption" type="text" name="topCaption" value="<?php echo isset( $t1 ) ? $t1 : ''; ?>"/></td>
        </tr>
        <tr class="text">
            <td><label for="bottomCaption">Bottom Caption:</label></td>
            <td><input id="bottomCaption" type="text" name="bottomCaption" value="<?php echo isset( $t2 ) ? $t2 : ''; ?>"/></td>
        </tr>

    </table>
    <button type="submit">Create Meme</button>
</form>

</body>
</html>


<?php
}