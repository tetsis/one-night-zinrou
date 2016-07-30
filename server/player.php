<?php
class Player {
    public $id;
    public $name;
    public $socket;
    public $point;
    public $earningPoint;
    public $position;
    public $gameStartFlag;
    public $actionFlag;
    public $daytimeFlag;
    public $talksEndFlag;
    public $executionFlag;
    public $resultFlag;
    public $winnerOrLoser;
    public $selectionId;
    public $hangingId;
    public $hangingNumber;

    //コンストラクタ
    public function __construct($id, $name, $socket) {
        $this->id = $id;
        $this->name = $name;
        $this->socket = $socket;
        $this->point = 0;
        $this->earningPoint = 0;
        $this->position = -1;
        $this->gameStartFlag = false;
        $this->actionFlag = false;
        $this->talksStartFlag = false;
        $this->talksEndFlag = false;
        $this->executionFlag = false;
        $this->resultFlag = false;
        $this->winnerOrLoser = -1;
        $this->selectionId = -1;
        $this->hangingId = -1;
        $this->hangingNumber = 0;
    }
}
?>
