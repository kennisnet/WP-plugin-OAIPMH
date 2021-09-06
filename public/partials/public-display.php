<?php
/**
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       https://www.kennisnet.nl
 * @since      1.0.0
 *
 * @package    wpoaipmh
 */
// @see https://github.com/picturae/OaiPmh

// Where $repository is an instance of \Picturae\OaiPmh\Interfaces\Repository
$repository = new \wpoaipmh\OaiPmh\Repository();

if( class_exists('\Laminas\Diactoros\ServerRequestFactory') ) {   
    $request = Laminas\Diactoros\ServerRequestFactory::fromGlobals();
} else {
    // Fallback, nocheck
    $request = Zend\Diactoros\ServerRequestFactory::fromGlobals();
}

$provider = new Picturae\OaiPmh\Provider( $repository, $request );
$response = $provider->getResponse();

// Send PSR 7 Response
$emit = false;
if( class_exists( '\Laminas\HttpHandlerRunner\Emitter\SapiEmitter' ) && method_exists('\Laminas\HttpHandlerRunner\Emitter\SapiEmitter', 'emit' ) ) {
    $emit = true;
    (new Laminas\HttpHandlerRunner\Emitter\SapiEmitter)->emit( $response );
} else {
    // Fallbacks
    // zendframework/zend-httphandlerrunner
    if( class_exists( '\Zend\HttpHandlerRunner\Emitter\SapiEmitter' ) && method_exists('\Zend\HttpHandlerRunner\Emitter\SapiEmitter', 'emit' ) ) {  
        $emit = true;
        (new Zend\HttpHandlerRunner\Emitter\SapiEmitter)->emit( $response );
    } else {
        if( class_exists( '\Zend\Diactoros\Response\SapiEmitter' ) && method_exists('\Zend\Diactoros\Response\SapiEmitter', 'emit' ) ) {
            // "zendframework/zend-diactoros": "1.8.6",
            $emit = true;
            (new Zend\Diactoros\Response\SapiEmitter())->emit( $response );        
        }
    }   
}

if( !$emit ) {
    throw new Exception( 'no SapiEmitter available', 500 );
}
