<?php
class Spectator {
    public $id;
    public $name;
    public $socket;

    //コンストラクタ
    public function __construct($id, $name, $socket) {
        $this->id = $id;
        $this->name = $name;
        $this->socket = $socket;
    }
}
?>
