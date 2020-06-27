<?php

namespace App\Http\Controllers;
use App\weatherConsultations;
use Illuminate\Http\Request;
use DB;
USE cache;

class weatherConsultationsController extends Controller
{
    private $weatherConsultations;
    private $larafy;
    private $token;
    private $musics = [];
    private $city_temp;
    private $city;
    private $music_genre;
    private $spotify_token_url;
    private $spotify_recommendations_url;
    private $client_id;
    private $cliente_secret;
    private $weather_key;
    private $weather_endpoint;
    private $city_metrics;
    
    public function __construct()
    { 
        $this->client_id = env("CLIENT_ID", false);
        $this->client_secret = env("CLIENT_SECRET", false);
        $this->spotify_token_url = env("SPOTIFY_TOKEN_URL", false);
        $this->spotify_recommendations_url = env("SPOTIFY_RECOMMENDATION_URL", false);
        $this->weather_key = env("WEATHER_KEY", false);
        $this->weather_endpoint = env("WEATHER_ENDPOINT", false);
        $this->token = $this->getSpotifyToken();
        $this->city_metrics = array();
        
        if(Cache::get('city_metrics')){
            $this->city_metrics =  Cache::get('city_metrics',false);
        }
        $this->weatherConsultations = new weatherConsultations();
    }

    public function getMusicSuggestion(Request $request){
        if(isset($request->city)){
            $this->city = rawurldecode($request->city);

            if($this->getWeatherByCityName()){
                if(isset($this->token, $this->spotify_token_url,$this->spotify_recommendations_url) && $this->spotify_token_url && $this->spotify_recommendations_url){
                    if($this->getMusicByTemperature()){
                        $this->setCityMetrics();
                        return response()->json(["status"=>200, "data"=>$this->musics], 200,['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'], JSON_UNESCAPED_UNICODE);                    
                    }
                    else{
                        return $this->makeErrorReturn("404","Response vazio ou nulo","Nenhuma música encontrada.");
                    }
                }
                else{
                    return $this->makeErrorReturn("401","Erro na obtenção de dados externos","Erro ao obter dados necessários para integrar com a API do Spotify.");
                }
            }            
        }
        else{
            return $this->makeErrorReturn("400","Parâmetros requeridos não fornecidos","O parâmetro nome da cidade não fornecido.");
        }
    }

    public function makeErrorReturn($code,$message_status,$description){
        if(isset($code,$message_status,$description)){            
            return response()->json([
                                        "status"=>$code,
                                        "message_status"=>$message_status,
                                        "description"=>$description
                                    ], $code,['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'], JSON_UNESCAPED_UNICODE);                    
        }
    }

    public function getWeatherByCityName(){
        if(isset($this->weather_key,$this->weather_endpoint,$this->city)){
            $cached_city_temp = Cache::get($this->city);
            if(isset($cached_city_temp) && $cached_city_temp){
                $this->city_temp = Cache::get($this->city);
                return true;
            } 
            
            $this->weather_endpoint = str_replace("{city}",$this->city,$this->weather_endpoint);
            $this->weather_endpoint = str_replace("{weather_key}",$this->weather_key,$this->weather_endpoint);

            $curlData = $this->getCurlResponse($this->weather_endpoint,false,false);

            if($curlData->main->temp){
                if(isset($curlData->main->temp)){
                    $this->city_temp = $curlData->main->temp;

                    if(isset($this->city_temp)){
                        if(!Cache::get($this->city)){
                            Cache::put($this->city, $this->city_temp, 600);
                        } 
                    }

                    return true;
                }
                return false;
            }
            else{
                return $this->makeErrorReturn("404","Erro na obtenção de dados externos","Erro ao adquirir a temperatura da cidade $this->city.");
            }
        }
    }

    public function getMusicByTemperature(){
        if(isset($this->city_temp)){       
            $this->getMusicGenreByCityTemp();
        
            $this->spotify_recommendations_url = str_replace("{genre}", $this->music_genre,$this->spotify_recommendations_url);
            $curlData = $this->getCurlResponse($this->spotify_recommendations_url,$this->token,"Bearer");

            if($curlData){
                foreach($curlData->tracks as $recommendation){
                    array_push($this->musics,$recommendation->name);
                }

                return true;
            } 
            else{
                return $this->makeErrorReturn("400","Erro na obtenção de dados externos","Erro ao adquirir as músicas de acordo com a temperatura da cidade $this->city");
            }
        }
    }

    public function getMusicGenreByCityTemp(){
        if($this->city_temp > 25){
            $this->music_genre = "pop";
        }
        elseif($this->city_temp >= 10 && $this->city_temp <= 25){
            $this->music_genre = "rock";
        }
        elseif($this->city_temp < 10){
            $this->music_genre = "classic";
        }
    }

    public function setCityMetrics(){
        if(isset($this->city)){
            if(!in_array($this->city,$this->city_metrics)){
                array_push($this->city_metrics,$this->city);
                Cache::forever('city_metrics', $this->city_metrics);
            } 
        }

        return false;
    }

    public function getCityMetrics(){
        return response()->json(["status"=>200, "data"=>$this->city_metrics], 200,['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'], JSON_UNESCAPED_UNICODE);
    }
    
    public function getSpotifyToken(){
        $client = new \GuzzleHttp\Client();
        if(isset($this->client_id,$this->client_secret)){
            try {
                $request = $client->request('POST', $this->spotify_token_url, [
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'Accepts' => 'application/json',
                        'Authorization' => 'Basic '.base64_encode($this->client_id.":".$this->client_secret),
                    ],
                    'form_params' => [
                        'grant_type' => 'client_credentials',
                    ],
                ]);
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                return false;
            }

            $response = json_decode($request->getBody());

            if($response && isset($response->access_token)){
                return $response->access_token;
            }
        }
        
        return false;
    }

    public function getCurlResponse($endpoint, $authorization, $authorization_type){
        if(isset($endpoint)){
            if(isset($authorization) && $authorization){
                $token = "Authorization: $authorization_type $this->token";
            }
            else{$token = "";}

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/json",
                $token
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if (!$err) {
                $response = json_decode($response);

                if($response){
                    return $response;
                }
                
            } 
            return false;
        }
    }
}
