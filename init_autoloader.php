<?php

// Composer autoloading
if( file_exists( 'vendor/autoload.php' ) )
{
    include 'vendor/autoload.php';
}

function __autoload( $pClassName )
{
    require_once( 'src/' . str_replace( "\\", "/", $pClassName . '.php' ) );
}

//$autoLoader = new \Aura\Autoload\Loader;
//$autoLoader->register();
//$autoLoader->addPrefix( 'Scraper', 'src/Scraper' );
