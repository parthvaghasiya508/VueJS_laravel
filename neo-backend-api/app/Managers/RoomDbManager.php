<?php

namespace App\Managers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;

class RoomDbManager {

    protected Client $client;
    private $accessToken = null;
    private $config;
    private $accessTokenRetry = false;

    public function __construct($config) {
        $this->config = $config;
        $this->client = new Client([
            'base_uri' => $this->config['roomdb_api']
        ]);
    }
    
    public function apiRequest(
            $method,
            $url,
            array $headers = [],
            array $form_options = []
    ): array {

        if (!$this->is_url($url)) {
            return ['status' => 'false', 'message' => 'URL is not valid.', 'data' => []];
        }

        $options = [];
        if ($headers) {
            $options['headers'] = $headers;
        }

        if (array_key_exists("form_params", $form_options)) {
            $options['form_params'] = $form_options["form_params"];
        }

        try {
            $response = $this->client->request($method, $url, $options);
            $body = $response->getBody();
            $message = 'Data found!';
            $status = 200;
            /** Extracted body data. **/
            $dataObj = json_decode($body);
            $data = isset($dataObj->accessToken)?$dataObj:$dataObj->result;
        } catch (\GuzzleHttp\Exception\ClientException $ce) {
            $status = $ce->getCode();
            $message = $ce->getMessage();
            $data = [];
        } catch (\GuzzleHttp\Exception\RequestException $re) {
            $status = $re->getCode();
            $message = $re->getMessage();
            $data = [];
        } catch (\GuzzleHttp\Exception\Exception $e) {
            $status = $e->getCode();
            $message = $e->getMessage();
            $data = [];
        }
        return ['status' => $status, 'message' => $message, 'data' => $data];
    }

    private function is_url($uri) {
        if (preg_match('/^(http|https):\\/\\/[a-z0-9_]+([\\-\\.]{1}[a-z_0-9]+)*\\.[_a-z]{2,5}' . '((:[0-9]{1,5})?\\/.*)?$/i', $uri)) {
            return $uri;
        } else {
            return false;
        }
    }

    public function setAccessToken(Request $request): void {
        $is_token_found = false;
        $this->request = $request;
        $this->accessToken = $this->request->session()->get('accessToken');

        if ($this->accessToken === null || $this->accessTokenRetry) {
            $response = $this->acquireAccessToken();
            $is_token_exists = strpos(json_encode($response), "accessToken") > 0 ? true : false;
            if($is_token_exists){
                $this->request->session()->put('accessToken', $response['data']->accessToken);
                $this->accessToken = $this->request->session()->get('accessToken');
            }else{
                $this->accessToken = null;
            }  
        }else{
            //echo("not reaching here: ".$this->accessToken);
        }
    }

    private function acquireAccessToken(): array {

        $url = $this->config["roomdb_api"] . 'suppliers/get-token';
        $options = [
            'form_params' => [
                'supplierId' => $this->config["roomdb_supplier_id"],
                'supplierSecret' => $this->config["roomdb_supplier_secret"]
            ]
        ];
        $response = $this->apiRequest(
                'POST',
                $url,
                [],
                $options
        );
        
        return $response;
    }

    public function setBearerTokenHeader() {
        return [
                'Authorization' => "Bearer {$this->accessToken}",
        ];
    }

}
