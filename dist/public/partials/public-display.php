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

$request = Zend\Diactoros\ServerRequestFactory::fromGlobals();
$provider = new Picturae\OaiPmh\Provider($repository, $request);
$response = $provider->getResponse();

//var_dump((new Psr7)->modify_request($response, array('body' => time)));

// Send PSR 7 Response
(new Zend\HttpHandlerRunner\Emitter\SapiEmitter)->emit($response);
