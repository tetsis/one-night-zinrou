<?php
class Village {
    public $id;
    public $name;
    public $password;
    public $spectatorFlag;
    public $playerArray = array();
    public $spectatorArray = array();
    public $participantArray = array();
    public $numberOfParticipant = 0;
    public $numberOfPositionArray = array();
    public $talkingTime = 0;
    public $endingTime;
    public $state;
    public $winnerSide;
    public $fieldPosition1;
    public $fieldPosition2;
    public $resultOfFortunetellerArray = array();
    public $resultOfThiefArray = array();

    //コンストラクタ
    public function __construct($id, $name, $password, $spectatorFlag) {
        require_once('player.php');
        require_once('spectator.php');
        $this->id = $id;
        $this->name = $name;
        $this->password = $password;
        $this->spectatorFlag = $spectatorFlag;
        global $positionArray;
        foreach ($positionArray as $i) {
            $this->numberOfPositionArray[$i] = 0;
        }
        $this->state = PARTICIPATION;

        $this->currentPlayerId = rand(0, 100);
        $this->currentSpectatorId = rand(0, 100);
    }

    //プレイヤー情報を取得
    public function getPlayer($playerId) {
        foreach ($this->playerArray as $i) {
            $id = $i->id;
            if ($id == $playerId) {
                return $i;
            }
        }

        return null;
    }

    //観戦者情報を取得
    public function getSpectator($spectatorId) {
        foreach ($this->spectatorArray as $i) {
            $id = $i->id;
            if ($id == $spectatorId) {
                return $i;
            }
        }

        return null;
    }


    ////Participation////
    //socketで「プレイヤーとして参加」をクリック
    public function clickParticipationAsPlayer($socket, $messageArray) {
        $name = $messageArray->name;
        $this->participateInVillage($socket, PLAYER, $name);
    }

    //socketで「観戦者として参加」をクリック
    public function clickParticipationAsSpectator($socket, $messageArray) {
        $name = $messageArray->name;
        $this->participateInVillage($socket, SPECTATOR, $name);
    }

    //待機画面に遷移
    public function goToWaitingFromParticipation($socket, $attribute, $id) {
        $this->displayWaiting($socket, $attribute, $id);
    }

    //村参加画面を表示
    public function displayParticipation($socket, $villageId, $villageName, $spectatorFlag) {
        $txData = mask(json_encode(array('type'=>'system', 'state'=>PARTICIPATION, 'message'=>'display', 'villageId'=>$villageId, '$villageName'=>'$villageName', 'spectatorFlag'=>$spectatorFlag)));
        sendMessage($txData, $socket);
    }

    //村に参加
    public function participateInVillage($socket, $attribute, $name) {
        if (($this->state == PARTICIPATION) || ($this->state == WAITING)) {
            $flag = false;
            foreach ($this->playerArray as $i) {
                if ($i->name == $name) {
                    $flag = true;
                }
            }
            foreach ($this->spectatorArray as $i) {
                if ($i->name == $name) {
                    $flag = true;
                }
            }
            if ($flag == true) {
                $txData = mask(json_encode(array('type'=>'system', 'state'=>PARTICIPATION, 'message'=>'reject')));
                sendMessage($txData, $socket);
            }
            else {
                $this->state = WAITING;
                switch ($attribute) {
                    case PLAYER:
                        //プレイヤーを作成
                        $player = new player($this->currentplayerid, $name, $socket);
                        $this->playerArray[] = $player;
                        $this->currentplayerid++;
                        $this->goToWaitingFromParticipation($socket, $attribute, $this->currentPlayerId);
                    case SPECTATOR:
                        //観戦者を作成
                        $player = new Spectator($this->currentSpectatorId, $name, $socket);
                        $this->spectatorArray[] = $spectator;
                        $this->currentSpectatorId++;
                        $this->goToWaitingFromParticipation($socket, $attribute, $this->currentSpectatorId);
                }
            }
        }
        else {
                $txData = mask(json_encode(array('type'=>'system', 'state'=>PARTICIPATION, 'message'=>'alreadyStarted')));
                sendMessage($txData, $socket);
        }
    }


    ////Waiting////
    //役職数がクリックされた
    public function clickNumberOfPosition($messageArray) {
        $position = $messageArray->position;
        $number = $messageArray->number;
        $numberOfPositionArray[$position] = $number;
        foreach ($this->playerArray as $i) {
            $txData = mask(json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'setNumberOfPosition', 'position'=>$position, 'number'=>$number)));
            sendMessage($txData, $i->socket);
        }
        foreach ($this->spectatorArray as $i) {
            $txData = mask(json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'setNumberOfPosition', 'position'=>$position, 'number'=>$number)));
            sendMessage($txData, $i->socket);
        }
    }

    //話し合い時間がクリックされた
    public function clickTalkingTime($messageArray) {
        $time = $messageArray->time;
        $talkingTime = $time;
        foreach ($this->playerArray as $i) {
            $txData = mask(json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'setTalkingTime', 'time'=>$time)));
            sendMessage($txData, $i->socket);
        }
        foreach ($this->spectatorArray as $i) {
            $txData = mask(json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'setTalkingTime', 'time'=>$time)));
            sendMessage($txData, $i->socket);
        }
    }

    //「ゲーム開始」がクリックされた
    public function clickGameStart($messageArray) {
        $id = $messageArray->id;
        $player = getPlayer($id);
        if ($player != null) {
            $player->gameStartFlag = true;
            foreach ($this->playerArray as $i) {
                $txData = mask(json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'gameStart', 'id'=>$id)));
                sendMessage($txData, $i->socket);
            }
            foreach ($this->spectatorArray as $i) {
                $txData = mask(json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'gameStart', 'id'=>$id)));
                sendMessage($txData, $i->socket);
            }

            foreach ($this->playerArray as $i) {
                $sum = 0;
                if ($i->gameStartFlag == true) {
                    $sum++;
                }
            }
            if ($sum == count($playerArray)) {
                $this->startGame();
                foreach ($this->playerArray as $i) {
                    $this->goToActionFromWaiting($i->socket, $i->id);
                }
                foreach ($this->spectatorArray as $i) {
                    $this->goToNightFromWaiting($i->socket);
                }
            }
        }
    }

    //行動画面に遷移
    public function goToActionFromWaiting($socket, $id) {
        $this->displayAction($socket, $id);
    }

    //夜の画面に遷移
    public function goToNightFromWaiting($socket) {
        $this->displayAction($socket);
    }

    //ゲームを開始
    public function startGame() {
        $currentPositionArray = array();
        global $positionArray;
        foreach ($positionArray as $i) {
            for ($j = 0; $j < $this->numberOfPositionArray[$i]); $j++) {
                $currentPositionArray[] = $i;
            }
        }
        shuffle($currentPositionArray);
        foreach ($this->playerArray as $i) {
            $i->position = array_shift($currentPositionArray);
        }
        $this->fieldPosition1 = array_shift($currentPositionArray);
        $this->fieldPosition2 = array_shift($currentPositionArray);

        $this->state = NIGHT;
    }

    //待機画面を表示
    public function displayWaiting($socket, $attribute, $id) {
        global $positionArray;
        $txData = mask(json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'init')));
        sendMessage($txData, $socket);
        foreach ($this->playerArray as $i) {
            $txData = mask(json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'add', 'attribute'=>PLAYER, 'id'=>$i->id, 'name'=>$i->name)));
            sendMessage($txData, $socket);
        }
        foreach ($this->spectatorArray as $i) {
            $txData = mask(json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'add', 'attribute'=>SPECTATOR, 'id'=>$i->id, 'name'=>$i->name)));
            sendMessage($txData, $socket);
        }
        foreach ($this->positionArray as $i) {
            $txData = mask(json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'setNumberOfPosition', 'position'=> $i, 'id'=>$this->numberOfPositionArray[$i])));
            sendMessage($txData, $socket);
        }
        $txData = mask(json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'setTalkingTime', 'time'=> $this->talkingTime)));
        sendMessage($txData, $socket);
        foreach ($this->playerArray as $i) {
            if ($i->gameStartFlag == true) {
                $txData = mask(json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'gameStart', 'id'=>$i->id)));
                sendMessage($txData, $socket);
            }
        }
    }


    ////Action////
    //「次へ」がクリックされた
    public function clickNotification($messageArray) {
        $id = $messageArray->id;
        $player = getPlayer($id);
        if ($player != null) {
            $player->actionFlag = true;
            if ($player->position == FORTUNETELLER || $player->position == THIEF) {
                $selectionId = $messageArray->selectionId;
                if ($player->position == FORTUNETELLER) {
                    resultOfFortunetellerArray[] = array('id' => $id, 'selectionId' => $selectionId);
                    foreach ($this->spectatorArray as $i) {
                        $txData = mask(json_encode(array('type'=>'system', 'state'=>NIGHT, 'message'=>'setResultOfFortuneteller', 'id'=>$id, 'selectionId'=>$selectionId)));
                        sendMessage($txData, $i->socket);
                    }
                }
                else if ($player->position == THIEF) {
                    resultOfThiefArray[] = array('id' => $id, 'selectionId' => $selectionId);
                    foreach ($this->spectatorArray as $i) {
                        $txData = mask(json_encode(array('type'=>'system', 'state'=>NIGHT, 'message'=>'setResultOfThief', 'id'=>$id, 'selectionId'=>$selectionId)));
                        sendMessage($txData, $i->socket);
                    }
                }
            }
            $this->goToNotificationFromAction($player->socket, $id);
        }
    }

    //通知画面に遷移
    public function goToNotificationFromAction($socket, $id) {
        $this->displayNotification($socket, $id);
    }

    //行動画面を表示
    public function displayAction($socket, $id) {
        $player = getPlayer($id);
        $txData = mask(json_encode(array('type'=>'system', 'state'=>ACTION, 'message'=>'init')));
        sendMessage($txData, $socket);
        if ($player->position == FORTUNETELLER || $player->position == THIEF) {
            foreach ($playerArray as $i) {
                if ($i->id != $id) {
                    $txData = mask(json_encode(array('type'=>'system', 'state'=>ACTION, 'message'=>'setPlayer', 'id'=>$i->id, 'name'=>$i->name)));
                    sendMessage($txData, $socket);
                }
            }
        }
        $txData = mask(json_encode(array('type'=>'system', 'state'=>ACTION, 'message'=>'display', 'villageId'=>$this->villageId, 'id'=>$id, 'position'=>$player->position)));
        sendMessage($txData, $socket);
    }


    ////Notification////
    //「昼のフェーズへ」がクリックされた
    public function clickDaytime($messageArray) {
        $id = $messageArray->id;
        $player = getPlayer($id);
        if ($player != null) {
            $player->daytimeFlag = true;
            foreach ($this->playerArray as $i) {
                $sum = 0;
                if ($i->daytimeFlag == true) {
                    $sum++;
                }
            }
            if ($sum == count($playerArray)) {
                $this->state = DAYTIME;
                $this->endingTime = new DateTime('+'. $this->talkingTime. ' minutes')
                foreach ($this->playerArray as $i) {
                    $this->goToDaytimeFromNotification($i->socket, $i->id);
                }
                foreach ($this->spectatorArray as $i) {
                    $this->goToDaytimeFromNight($i->socket);
                }
            }
        }
    }

    //昼の画面に遷移
    public function goToDaytimeFromNotification($socket, $id) {
        $this->displayDaytime($socket, PLAYER, $id);
    }

    //通知画面を表示
    public function displayNotification($socket, $id) {
        $player = getPlayer($id);
        $txData = mask(json_encode(array('type'=>'system', 'state'=>NOTIFICATION, 'message'=>'init')));
        sendMessage($txData, $socket);
        switch ($player->position) {
            case WEREWOLF:
                $buddyName = array();
                foreach ($this->playerArray as $i) {
                    if (($player->position == WEREWOLF) && ($player->id != $id)) {
                        $buddyName[] = $player->name;
                    }
                }
                if (empty($buddyName) == false) {
                    foreach ($buddyName as $i) {
                        $txData = mask(json_encode(array('type'=>'system', 'state'=>NOTIFICATION, 'message'=>'setBuddy', 'name'=>$i)));
                        sendMessage($txData, $socket);
                    }
                }
                else {
                    $txData = mask(json_encode(array('type'=>'system', 'state'=>NOTIFICATION, 'message'=>'setBuddy', 'name'=>null)));
                    sendMessage($txData, $socket);
                }
                break;
            case FORTUNETELLER:
                if ($player->selectionId != -1) {
                    $selectionPlayer = getPlayer($player->selectionId);
                    $txData = mask(json_encode(array('type'=>'system', 'state'=>NOTIFICATION, 'message'=>'setResult', 'name'=>$selectionPlayer->name, 'position'=>$selectionPlayer->position)));
                    sendMessage($txData, $socket);
                }
                else {
                    $txData = mask(json_encode(array('type'=>'system', 'state'=>NOTIFICATION, 'message'=>'setResultOfField', 'position1'=>$this->fieldPosition1, 'position2'=>$this->fieldPosition2)));
                    sendMessage($txData, $socket);
                }
                break;
            case THIEF:
                if ($player->selectionId != -1) {
                    $selectionPlayer = getPlayer($player->selectionId);
                    $txData = mask(json_encode(array('type'=>'system', 'state'=>NOTIFICATION, 'message'=>'setResult', 'name'=>$selectionPlayer->name, 'position'=>$selectionPlayer->position)));
                    sendMessage($txData, $socket);
                }
                else {
                    $txData = mask(json_encode(array('type'=>'system', 'state'=>NOTIFICATION, 'message'=>'setNotSwapped')));
                    sendMessage($txData, $socket);
                }
                break;
        }
        $txData = mask(json_encode(array('type'=>'system', 'state'=>NOTIFICATION, 'message'=>'display', 'villageId'=>$this->villageId, 'id'=>$id, 'position'=>$player->position)));
        sendMessage($txData, $socket);
    }


    ////Night////
    //昼の画面に遷移
    public function goToDaytimeFromNight($socket) {
        $this->displayDaytime($socket, SPECTATOR, $id);
    }

    //夜の画面を表示
    public function displayNight($socket) {
        $txData = mask(json_encode(array('type'=>'system', 'state'=>NIGHT, 'message'=>'init')));
        sendMessage($txData, $socket);
        foreach ($this->playerArray as $i) {
            $txData = mask(json_encode(array('type'=>'system', 'state'=>NIGHT, 'message'=>'setPositionOfPlayer', 'id'=>$i->id, 'name'=>$i->name, 'position'=>$i->position)));
            sendMessage($txData, $socket);
            if ($i->position == FORTUNETELLER) {
                if ($i->actionFlag == true) {
                    $txData = mask(json_encode(array('type'=>'system', 'state'=>NIGHT, 'message'=>'setResultOfFortuneteller', 'id'=>$i->id, 'selectionId'=>$i->selectionId)));
                    sendMessage($txData, $socket);
                }
            }
            else if ($i->position == THIEF) {
                if ($i->actionFlag == true) {
                    $txData = mask(json_encode(array('type'=>'system', 'state'=>NIGHT, 'message'=>'setResultOfThief', 'id'=>$i->id, 'selectionId'=>$i->selectionId)));
                    sendMessage($txData, $socket);
                }
            }
        }
        $txData = mask(json_encode(array('type'=>'system', 'state'=>NIGHT, 'message'=>'display')));
        sendMessage($txData, $socket);
    }


    ////Daytime////
    //「話し合い延長」がクリックされた
    public function clickExtension($messageArray) {
        $endingTime = new DateTime('+1 minutes')
            foreach ($this->playerArray as $i) {
                $txData = mask(json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'setTalkingTime', 'time'=>1)));
                sendMessage($txData, $i->socket);
            }
        foreach ($this->spectatorArray as $i) {
                $txData = mask(json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'setTalkingTime', 'time'=>1)));
                sendMessage($txData, $i->socket);
        }
    }

    //「話し合い終了」がクリックされた
    public function clickTalksEnd($messageArray) {
        $id = $messageArray->id;
        $player = getPlayer($id);
        if ($player != null) {
            $player->talksEndFlag = true;
            foreach ($this->playerArray as $i) {
                $txData = mask(json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'setTalksEnd', 'id'=>$id)));
                sendMessage($txData, $i->socket);
            }
            foreach ($this->spectatorArray as $i) {
                $txData = mask(json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'setTalksEnd', 'id'=>$id)));
                sendMessage($txData, $i->socket);
            }

            foreach ($this->playerArray as $i) {
                $sum = 0;
                if ($i->talksEndFlag == true) {
                    $sum++;
                }
            }
            if ($sum == count($playerArray)) {
                $this->state = EXECUTION;
                foreach ($this->playerArray as $i) {
                    $this->goToExecutionFromDaytime($i->socket, PLAYER, $i->id);
                }
                foreach ($this->spectatorArray as $i) {
                    $this->goToExecutionFromDaytime($i->socket, SPECTATOR, $i-id);
                }
            }
        }
    }

    //吊る人選択画面に遷移
    public function goToExecutionFromDaytime($socket, $attribute, $id) {
        displayExecution($socket, $attribute, $id);
    }

    //昼の画面を表示
    public function displayDaytime($socket, $attribute, $id) {
        $txData = mask(json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'init')));
        sendMessage($txData, $socket);
        foreach ($this->playerArray as $i) {
            $txData = mask(json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'setPlayer', 'id'=>$i->id, 'name'=>$i->name)));
            sendMessage($txData, $socket);
        }
        foreach ($this->spectatorArray as $i) {
            $txData = mask(json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'setSpectator', 'id'=>$i->id, 'name'=>$i->name)));
            sendMessage($txData, $socket);
        }
        global $positionArray;
        foreach ($positionArray as $i) {
            $txData = mask(json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'setNumberOfPosition', 'position'=>$i, 'number'=>$this->numberOfPositionArray[$i])));
            sendMessage($txData, $socket);
        }
        $txData = mask(json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'setTalkingTime', 'time'=>$this->talkingTime)));
        sendMessage($txData, $socket);
        foreach ($this->playerArray as $i) {
            if ($i->gameStartFlag == true) {
                $txData = mask(json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'talksEnd', 'id'=>$i->id)));
                sendMessage($txData, $socket);
            }
        }
        if ($attribute == SPECTATOR) {
            //ここから
        }
    }


    ////Execution////
    //「結果発表へ」がクリックされた
    public function clickResult($messageArray) {
    }

    //結果発表画面に遷移
    public function goToResultFromExecution($socket, $attribute, $id) {
    }

    //吊る人選択画面を表示
    public function displayExecution($socket, $attribute, $id) {
    }

    //勝者を判定
    public function judgeWinner() {
    }

    //役職が勝者サイドか
    public function isWinner($position, $winnerSide) {
    }

    //ポイントを計算
    public function getPointOfPosition($position, $winnerSide) {
    }


    ////Result////
    //「次の夜へ」がクリックされた
    public function clickNextNight() {
    }

    //「終了」がクリックされた
    public function clickExit() {
    }

    //行動画面に遷移
    public function goToActionFromResult($socket, $id) {
    }

    //夜の画面に遷移
    public function goToNightFromResult($socket, $id) {
    }

    //待機画面に遷移
    public function goToWaitingFromResult($socket, $attribute, $id) {
    }

    //結果発表画面を表示
    public function displayResult($socket, $attribute) {
    }
}

?>
