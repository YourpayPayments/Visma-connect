<?php
class VismaConnect
{
    private $visma_client_id;
    private $visma_secret;
    private $access_token;
    private $refresh_token;
    private $auth_code;
    private $return_uri;
    private $scope;

    function __construct($return_uri)
    {
        $this->visma_client_id = $_ENV["visma_client_id"];
        $this->visma_secret = $_ENV["visma_secret"];

        $this->return_uri = $return_uri;
    }
    public function update_token($type,$code) {

        switch ($type){
            case "access_token":
                $this->access_token = $code;
                break;
            case "refresh_token":
                $this->refresh_token = $code;
                break;
            case "code":
                $this->auth_code = $code;
                break;
        }
    }
    public function Connect() {
        $response = $this->request($this->auth_code,"authorization_code","code");
        $this->access_token = $response->access_token;
        $this->refresh_token = $response->refresh_token;
        return $response;
    }
    public function Refresh() {
        $response = $this->request($this->refresh_token,"refresh_token","refresh_token");
        $this->access_token = $response->access_token;
        $this->refresh_token = $response->refresh_token;
        return $response;
    }
    public function UpdateScope($append_to_scope) {
        $scope = explode("+",$this->scope);
        $txt_scope = "";
        foreach($scope as $value)
            $txt_scope .= $value . "+";
        $this->scope = rtrim($append_to_scope."+".$txt_scope,"+");
    }
    public function requestUrl($response_code) {
        $nonce = $response_code;
        $state = $response_code;
        $url = $url = "https://connect.visma.com/connect/authorize?client_id=$this->visma_client_id&";
        $url .= "redirect_uri=$this->return_uri&response_type=code&nbsp;id_token&nbsp;token&scope=$this->scope&response_mode=form_post&nonce=test&state=$state";

        return (object)[
            "url"=>$url,
            "state"=>$nonce
        ];
    }
    private function request($token,$grant_type = "authorization_code", $type = "code") {
        $url = "https://connect.visma.com/connect/token";

        $fields=[
            "grant_type"=>$grant_type,
            $type=>$token,
            "redirect_uri"=>$this->return_uri,
            "response_type"=>"code",
            "client_id"=>$this->visma_client_id,
        ];

        $headers = array(
            'User-Agent: YourpayConnectClass',
            'cache-control: no-cache',
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic '.base64_encode($this->visma_client_id . ":" . $this->visma_secret),
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec ($ch);
        curl_close ($ch);

        return json_decode($server_output);
    }
}