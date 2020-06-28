<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/
$router->get('/', function(){
    return redirect("http://documentation.climamusic.kinghost.net");
});

$router->get('api/weather/{city}', 'weatherConsultationsController@getMusicSuggestion');
$router->get('api/statistics', 'weatherConsultationsController@getCityMetrics');