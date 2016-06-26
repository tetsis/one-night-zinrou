<?php
class Player {
    public $id;
    public $name;
    public $socket;
    public $point;
    public $position;
    public $gameStartFlag;
    public $actionFlag;
    public $daytimeFlag;
    public $talksEndFlag;
    public $resultFlag;
    public $winnerOrLoser;
    public $selectionId;

    //コンストラクタ
    public function __construct($id, $name, $socket) {
        $this->id = $id;
        $this->name = $name;
        $this->socket = $socket;
        $this->point = 0;
        $this->position = -1;
        $this->gameStartFlag = false;
        $this->actionFlag = false;
        $this->daytimeFlag = false;
        $this->talksEndFlag = false;
        $this->resultFlag = false;
        $this->winnerOrLoser = -1;
        $this->selectionId = -1;
    }
}
?>
