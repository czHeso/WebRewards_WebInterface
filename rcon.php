<?php
class Rcon {
    private $host;
    private $port;
    private $password;
    private $socket;

    public function __construct($host, $port, $password) {
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
    }

    public function connect() {
        $this->socket = fsockopen($this->host, $this->port, $errno, $errstr, 3);
        if (!$this->socket) {
            return false;
        }
        $this->send_packet(3, $this->password);
        $response = $this->get_response();
        return $response['id'] == 1;
    }

    public function send_command($command) {
        $this->send_packet(2, $command);
        $response = $this->get_response();
        return $response['body'];
    }

    private function send_packet($type, $body) {
        $packet = pack("VV", 1, $type) . $body . "\x00\x00";
        $packet = pack("V", strlen($packet)) . $packet;
        fwrite($this->socket, $packet);
    }

    private function get_response() {
        $size = fread($this->socket, 4);
        if (strlen($size) < 4) {
            return false;
        }
        $size = unpack("V", $size)[1];
        $packet = fread($this->socket, $size);
        return unpack("Vid/Vtype/a*body", $packet);
    }

    public function disconnect() {
        fclose($this->socket);
    }
}
?>
