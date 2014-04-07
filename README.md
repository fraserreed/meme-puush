meme-puush
==========

[![Build Status](https://secure.travis-ci.org/fraserreed/meme-puush.png?branch=master)](http://travis-ci.org/fraserreed/meme-puush)
[![Coverage Status](https://coveralls.io/repos/fraserreed/meme-puush/badge.png?branch=master)](https://coveralls.io/r/fraserreed/meme-puush?branch=master)

Meme generator using Imagick.  Either output local file path, to screen or upload to Puu.sh using the Puu.sh API.  Puu.sh API key required (http://puu.sh)

#### Installation

Install this package in your application using [composer](http://composer.org).

In the require section, add the following dependency:
```
"fraserreed/meme-puush": "~1.0"
```

#### Usage

First initialize the image object with an accessible URL to the image:

```
use MemePuush\Image;

$image = new Image( $img );
```

If the image path doesn't exist, an exception will be thrown when trying to output the meme image.

Next set the output format.  The options are available in class constants of the `Image` class:  `Image::PUUSH` (will upload the result using the Puu.sh api key), `Image::FILE` (will store the result locally and provide the output filename) and `Image::SCREEN` (will output the result to the browser).

```
$image->setOutputFormat( Image::PUUSH, '<puu.sh_api_key>' );
```

The puu.sh api key is not required if storing the result locally or outputting to the screen.
```
$image->setOutputFormat( Image::FILE );
```

Set the caption string, for the top caption or the bottom caption (or both):
```
$image->setTopCaption( "top caption text" );
$image->setBottomCaption( "bottom caption text" );
```

Finally output the result.  What you do with the response is dependent on the format of the output:
```
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
```

#### Example

A (really ugly) example UI for creating memes using the three available methods can be seen in `public/index.php`