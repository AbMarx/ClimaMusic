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

    public function __construct()
    { 
        $this->client_id = env("CLIENT_ID", null);
        $this->client_secret = env("CLIENT_SECRET", null);
        $this->spotify_token_url = env("SPOTIFY_TOKEN_URL", null);
        $this->spotify_recommendations_url = env("SPOTIFY_RECOMMENDATION_URL", null);
        $this->weather_key = env("WEATHER_KEY", null);
        $this->weather_endpoint = env("WEATHER_ENDPOINT", null);
        $this->token = $this->getSpotifyToken();

        $this->weatherConsultations = new weatherConsultations();
    }

    public function getMusicSuggestion(Request $request){
        if(isset($request->city)){
            $this->city = $request->city;
            
            $this->getWeatherByCityName();
            $this->getMusicByTemperature();

            if(count($this->musics) > 0){
                if($this->storeWeatherConsultations()){
                    return json_encode($this->musics);
                }
                else{
                    return $this->makeErrorReturn("3","Falha em operação de banco de dados","Erro ao gravar a sua solicitação em nossa base de dados.");
                }
            }
            else{
                return $this->makeErrorReturn("2","Response vazio ou nulo","Nenhuma música encontrada.");
            }
        }
        else{
            return $this->makeErrorReturn("1","Parâmetros requeridos não fornecidos","O parâmetro nome da cidade não fornecido.");
        }
    }

    public function makeErrorReturn($code,$message_status,$description){
        if(isset($code,$message_status,$description)){
            return  json_encode([
                        "error_code"=>$code,
                        "message_status"=>$message_status,
                        "description"=>$description
                    ]);
        }
    }

    public function getWeatherByCityName(){
        if(isset($this->weather_key,$this->weather_endpoint,$this->city)){
            $this->weather_endpoint = str_replace("{city}",$this->city,$this->weather_endpoint);
            $this->weather_endpoint = str_replace("{weather_key}",$this->weather_key,$this->weather_endpoint);

            $curlData = $this->getCurlResponse($this->weather_endpoint,false,false);

            if($curlData->main->temp){
                $this->city_temp = $curlData->main->temp;
            }
            else{
                return $this->makeErrorReturn("4","Erro na obtenção de dados externos","Erro ao adquirir a temperatura da cidade $this->city.");
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
            } 
            else{
                return $this->makeErrorReturn("4","Erro na obtenção de dados externos","Erro ao adquirir as músicas de acordo com a temperatura da cidade $this->city");
            }
        }
    }

    public function getMusicGenreByCityTemp(){
        if($this->city_temp > 25){
            $this->music_genre = "pop";
        }
        elseif($this->city_temp > 10 && $this->city_temp <= 25){
            $this->music_genre = "rock";
        }
        elseif($this->city_temp < 10){
            $this->music_genre = "classic";
        }
    }

    public function storeWeatherConsultations(){
        $this->weatherConsultations->city = urldecode($this->city);
        $this->weatherConsultations->key_consultation = $this->client_id;
        $this->weatherConsultations->city_temperature = $this->city_temp;
        $this->weatherConsultations->music_genre = $this->music_genre;
        $this->weatherConsultations->created_at = time();
        $this->weatherConsultations->updated_at = time();

        if($this->weatherConsultations->save()){
            return true;
        }

        return false;
    }
    
    public function getSpotifyToken(){
        $client = new \GuzzleHttp\Client();

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
            throw new \Exception('Erro ao gerar token do Spotify');
        }

        $response = json_decode($request->getBody());

        if($response && isset($response->access_token)){
            return $response->access_token;
        }
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

    public function getStatistics(){
        if(Cache::has('statistics')) {
            $statistics = Cache::store('file')->get('statistics');
            return json_encode($statistics);
        }

        $totalConsultsByCity = $this->execDbSelect("SELECT DISTINCT wc.city, COUNT(wc.city) AS total_consultations FROM weather_consultations AS wc GROUP BY wc.city ORDER BY total_consultations DESC");
        $mostHotestCity = $this->execDbSelect("SELECT city, MAX(wc.city_temperature) AS city_temperature FROM weather_consultations AS wc GROUP BY city ORDER BY city_temperature DESC LIMIT 1");
        $mostColdestCity = $this->execDbSelect("SELECT city, MIN(wc.city_temperature) AS city_temperature FROM weather_consultations AS wc GROUP BY city ORDER BY city_temperature ASC LIMIT 1");
        $musicGenresByCity = $this->execDbSelect("SELECT DISTINCT city, GROUP_CONCAT(DISTINCT music_genre) AS music_genres FROM `weather_consultations` GROUP BY city");

        if($musicGenresByCity){
            foreach($musicGenresByCity as $city){
                $city->music_genres = explode(",",$city->music_genres);
            }    
            
            $musicGenresByCity = $musicGenresByCity;
        }

        $totalMusicGenreByCity = $this->execDbSelect("SELECT city ,music_genre, COUNT(music_genre) AS total_music_genre FROM weather_consultations GROUP BY music_genre,city");
        $totalMusicGenre = $this->execDbSelect("SELECT music_genre, COUNT(music_genre) AS total_music_genre FROM weather_consultations GROUP BY music_genre");

        if(!Cache::has('statistics')) {
            $statistics = [
                "totalConsultsByCity"=>$totalConsultsByCity,
                "mostHotestCity"=>$mostHotestCity,
                "mostColdestCity"=>$mostColdestCity,
                "musicGenresByCity"=>$musicGenresByCity,
                "totalMusicGenreByCity"=>$totalMusicGenreByCity,
                "totalMusicGenre"=>$totalMusicGenre
            ];
            Cache::put('statistics', $statistics, 600);
            $statistics = Cache::store('file')->get('statistics');
        }      
    
        return json_encode($statistics);
    }

    public function execDbSelect($sql){
        if(isset($sql)){
            return $exec_query = DB::select($sql);
        }

        return false;
    }    
}
