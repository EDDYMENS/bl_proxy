<?PHP 

class Proxy 
{
    public function forward()
    {
        if(!isset($_SERVER['HTTP_X_SANDBOX'])) {
            throw new \Exception('Please set your whitelabel as a header under the key `X-sandbox`');
        }
        $URL     = $_SERVER['HTTP_X_SANDBOX'];
        $method  = $_SERVER['REQUEST_METHOD'];
        $headers = getallheaders();
        $payload = $_REQUEST;
        $headers = $this->flattenHeaders($headers);
        return $this->requestProcessor($URL, $method, $payload, $headers);
    }

    private function requestProcessor($URL, $method, $payload = [], $headers = [])
    {
        $finalURL = $URL;
        if(isset($_SERVER['REQUEST_URI'])) {
            $finalURL = $URL.$_SERVER['REQUEST_URI'];
        }
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => $finalURL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_HEADER         => 1, ]
        );
        $response = curl_exec($curl);
        list($headers, $payload) = explode("\r\n\r\n", $response, 2);
        $err = curl_error($curl);
        if ($err) {
            throw new \Exception('Unable to perform CURL request '.$err);
        }
        curl_close($curl);
        return $payload;
    }
    private function flattenHeaders($rawHeaders)
    {
        unset($rawHeaders['Host']);
        $headers = [];
        foreach($rawHeaders as $index =>  $header) {
            $headers[] = "$index: $header";
        }
        return $headers;
    }
}

echo (new Proxy())->forward();