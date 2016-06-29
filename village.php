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
        $txData = mask(json_encode(array('type'=>'system', 'state'=>PARTICIPATION, 'message'=>'display', 'villageId'=>$villageId, 'villageName'=>$villageName, 'spectatorFlag'=>$spectatorFlag)));
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
        $player = $this->getPlayer($id);
        if ($player != null) {
            $player->gameStartFlag = true;
            foreach ($this->playerArray as $i) {
                $txData = mask(json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'setGameStart', 'id'=>$id)));
                sendMessage($txData, $i->socket);
            }
            foreach ($this->spectatorArray as $i) {
                $txData = mask(json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'setGameStart', 'id'=>$id)));
                sendMessage($txData, $i->socket);
            }

            $sum = 0;
            foreach ($this->playerArray as $i) {
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
        $this->displayNight($socket);
    }

    //ゲームを開始
    public function startGame() {
        $currentPositionArray = array();
        global $positionArray;
        foreach ($positionArray as $i) {
            for ($j = 0; $j < $this->numberOfPositionArray[$i]; $j++) {
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
                $txData = mask(json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'setGameStart', 'id'=>$i->id)));
                sendMessage($txData, $socket);
            }
        }
        $txData = mask(json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'display', 'villageId'=>$this->villageId, 'villageName'=>$this->villageName, 'id'=>$id, 'position'=>$player->position)));
        sendMessage($txData, $socket);
    }


    ////Action////
    //「次へ」がクリックされた
    public function clickNotification($messageArray) {
        $id = $messageArray->id;
        $player = $this->getPlayer($id);
        if ($player != null) {
            $player->actionFlag = true;
            if ($player->position == FORTUNETELLER || $player->position == THIEF) {
                $selectionId = $messageArray->selectionId;
                if ($player->position == FORTUNETELLER) {
                    $this->resultOfFortunetellerArray[] = array('id' => $id, 'selectionId' => $selectionId);
                    foreach ($this->spectatorArray as $i) {
                        $txData = mask(json_encode(array('type'=>'system', 'state'=>NIGHT, 'message'=>'setResultOfFortuneteller', 'id'=>$id, 'selectionId'=>$selectionId)));
                        sendMessage($txData, $i->socket);
                    }
                }
                else if ($player->position == THIEF) {
                    $this->resultOfThiefArray[] = array('id' => $id, 'selectionId' => $selectionId);
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
        $player = $this->getPlayer($id);
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
        $player = $this->getPlayer($id);
        if ($player != null) {
            $player->daytimeFlag = true;

            $sum = 0;
            foreach ($this->playerArray as $i) {
                if ($i->daytimeFlag == true) {
                    $sum++;
                }
            }
            if ($sum == count($$this->playerArray)) {
                $this->state = DAYTIME;
                $this->endingTime = new DateTime('+'. $this->talkingTime. ' minutes');
                foreach ($this->playerArray as $i) {
                    $this->goToDaytimeFromNotification($i->socket, $i->id);
                }
                foreach ($this->spectatorArray as $i) {
                    $this->goToDaytimeFromNight($i->socket, $i->id);
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
        $player = $this->getPlayer($id);
        $txData = mask(json_encode(array('type'=>'system', 'state'=>NOTIFICATION, 'message'=>'init')));
        sendMessage($txData, $socket);
        switch ($player->position) {
            case WEREWOLF:
                $buddyName = array();
                foreach ($this->playerArray as $i) {
                    if (($i->position == WEREWOLF) && ($i->id != $id)) {
                        $buddyName[] = $i->name;
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
                    $selectionPlayer = $this->getPlayer($player->selectionId);
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
                    $selectionPlayer = $this->getPlayer($player->selectionId);
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
    public function goToDaytimeFromNight($socket, $id) {
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
        $endingTime = new DateTime('+1 minutes');
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
        $player = $this->getPlayer($id);
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

            $sum = 0;
            foreach ($this->playerArray as $i) {
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
            if ($i->talksEndFlag == true) {
                $txData = mask(json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'talksEnd', 'id'=>$i->id)));
                sendMessage($txData, $socket);
            }
        }
        if ($attribute == SPECTATOR) {
            foreach ($this->playerArray as $i) {
                $txData = mask(json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'setPositionOfPlayer', 'id'=>$i->id, 'position'=>$i->position)));
                sendMessage($txData, $socket);
                if ($i->position == FORTUNETELLER) {
                    $txData = mask(json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'setResultOfFortuneteller', 'id'=>$i->id, 'selectionId'=>$i->selectionId)));
                    sendMessage($txData, $socket);
                }
                else if ($i->position == THIEF) {
                    $txData = mask(json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'setResultOfThief', 'id'=>$i->id, 'selectionId'=>$i->selectionId)));
                    sendMessage($txData, $socket);
                }
            }
        }
        $txData = mask(json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'display', 'villageId'=>$this->villageId, 'attribute'=>$attribute, 'id'=>$id)));
        sendMessage($txData, $socket);
    }


    ////Execution////
    //「結果発表へ」がクリックされた
    public function clickResult($messageArray) {
        $id = $messageArray->id;
        $hangingId = $messageArray->hangingId;
        $player = $this->getPlayer($id);
        if ($player != null) {
            $player->resultFlag = true;
            $player->hangingId = hangingId;
            $player->hangingNumber++;

            $sum = 0;
            foreach ($this->playerArray as $i) {
                if ($i->resultFlag == true) {
                    $sum++;
                }
            }
            if ($sum == count($playerArray)) {
                $this->judgeWinner();
                foreach ($this->playerArray as $i) {
                    $i->winnerOrLoser = isWinner($i->position, $this->winnerSide);
                    $i->point = getPoint($i->position, $this->winnerSide);
                }
                $this->state = RESULT;
                foreach ($this->playerArray as $i) {
                    $this->goToResultFromExecution($i->socket, PLAYER);
                }
                foreach ($this->spectatorArray as $i) {
                    $this->goToResultFromExecution($i->socket, SPECTATOR);
                }
            }
        }
    }

    //結果発表画面に遷移
    public function goToResultFromExecution($socket, $attribute) {
        $this->displayResult($socket, $attribute);
    }

    //吊る人選択画面を表示
    public function displayExecution($socket, $attribute, $id) {
        $txData = mask(json_encode(array('type'=>'system', 'state'=>EXECUTION, 'message'=>'init')));
        sendMessage($txData, $socket);
        if ($attribute == PLAYER) {
            foreach ($this->playerArray as $i) {
                if ($i->id != $id) {
                    $txData = mask(json_encode(array('type'=>'system', 'state'=>EXECUTION, 'message'=>'setPlayer', 'id'=>$i->id, 'name'=>$i->name)));
                    sendMessage($txData, $socket);
                }
            }
        }
        $txData = mask(json_encode(array('type'=>'system', 'state'=>EXECUTION, 'message'=>'display', 'villageId'=>$this->villageId, 'attribute'=>$attribute, 'id'=>$id)));
        sendMessage($txData, $socket);
    }

    //勝者を判定
    public function judgeWinner() {
        //怪盗が交換した後の役職に設定
        foreach ($playerArray as $i) {
            if ($i->position == THIEF) {
                if ($i->selectionId != -1) {
                    foreach ($playerArray as $j) {
                        $swap = $j->position;
                        $j->position = $i->position;
                        $i->position = $swap;
                        break;
                    }
                }
            }
        }
        //最も多く指名されたプレイヤーを抜き出す
        $hangingArray = array();
        $max = 0;
        foreach ($playerArray as $i) {
            if ($i->hangingNumber > $max) {
                $max = $i->hangingNumber;
            }
        }
        $maxPlayerArray = array();
        foreach ($playerArray as $i) {
            if ($i->hangingNumber == $max) {
                $maxPlayerArray[] = $i;
            }
        }

        //maxは1？
        if ($max == 1) {
            //プレイヤーの中に人狼はいる？
            $flag = false;
            foreach ($playerArray as $i) {
                if ($i->position == WEREWOLF) {
                    $flag = true;
                    break;
                }
            }
            //いる場合は人狼サイドの勝利
            if ($flag == true) {
                $this->winnerSide = WEREWOLF;
            }
            //いない場合は村人サイドの勝利
            else {
                $this->winnerSide = VILLAGER;
            }
        }
        else {
            //最も多く指名されたプレイヤーの中にてるてるはいる？
            $flag = false;
            foreach ($maxPlayerArray as $i) {
                if ($i->position == HANGING) {
                    $flag = true;
                    break;
                }
            }
            //いる場合はてるてるサイドの勝利
            if ($flag == true) {
                $this->winnerSide = HANGING;
            }
            else {
                //最も多く指名されたプレイヤーの中に人狼はいる？
                $flag = false;
                foreach ($maxPlayerArray as $i) {
                    if ($i->position == WEREWOLF) {
                        $flag = true;
                        break;
                    }
                }
                //いる場合は村人サイドの勝利
                if ($flag == true) {
                    $this->winnerSide = VILLAGER;
                }
                //いない場合は人狼サイドの勝利
                else {
                    $this->winnerSide = WEREWOLF;
                }
            }
        }
    }

    //役職が勝者サイドか
    public function isWinner($position, $winnerSide) {
        $flag = false;
        switch ($position) {
            case VILLAGER:
            case FORTUNETELLER:
            case THIEF:
                if ($winnerSide == VILLAGER) {
                    $flag = true;
                }
                break;
            case WEREWOLF:
            case MADMAN:
                if ($winnerSide == WEREWOLF) {
                    $flag = true;
                }
                break;
            case HANGING:
                if ($winnerSide == HANGING) {
                    $flag = true;
                }
                break;
        }
        return $flag;
    }

    //ポイントを計算
    public function getPoint($position, $winnerSide) {
        $point = 0;
        switch ($winnerSide) {
            case VILLAGER:
                switch ($position) {
                    case FORTUNETELLER:
                        $point = 2;
                        break;
                    case VILLAGER:
                    case THIEF:
                        $point = 1;
                        break;
                    case WEREWOLF:
                    case MADMAN:
                    case HANGING:
                        $point = 0;
                        break;
                }
                break;
            case WEREWOLF:
                switch ($position) {
                    case WEREWOLF:
                    case MADMAN:
                        $point = 2;
                        break;
                    case VILLAGER:
                    case THIEF:
                    case HANGING:
                        $point = 0;
                        break;
                    case FORTUNETELLER:
                        $point = -1;
                        break;
                }
                break;
            case HANGING:
                switch ($position) {
                    case HANGING:
                        $point = 3;
                        break;
                    case VILLAGER:
                    case WEREWOLF:
                    case FORTUNETELLER:
                    case THIEF:
                    case MADMAN:
                        $point = 0;
                        break;
                }
                break;
        }
        return $point;
    }


    ////Result////
    //「次の夜へ」がクリックされた
    public function clickNextNight() {
        $this->startGame();
        foreach ($this->playerArray as $i) {
            $this->goToActionFromResult($i->socket, $i->id);
        }
        foreach ($this->spectatorArray as $i) {
            $this->goToNightFromResult($i->socket);
        }
    }

    //「終了」がクリックされた
    public function clickExit() {
        $this->state = WAITING;
        foreach ($playerArray as $i) {
            $this->goToWaitingFromResult($i->socket, PLAYER, $i->id);
        }
        foreach ($spectatorArray as $i) {
            $this->goToWaitingFromResult($i->socket, SPECTATOR, $i->id);
        }
    }

    //行動画面に遷移
    public function goToActionFromResult($socket, $id) {
        $this->displayAction($socket, $id);
    }

    //夜の画面に遷移
    public function goToNightFromResult($socket) {
        $this->displayNight($socket);
    }

    //待機画面に遷移
    public function goToWaitingFromResult($socket, $attribute, $id) {
        $this->displayWaiting($socket, $attribute, $id);
    }

    //結果発表画面を表示
    public function displayResult($socket, $attribute, $id) {
        $txData = mask(json_encode(array('type'=>'system', 'state'=>RESULT, 'message'=>'init')));
        sendMessage($txData, $socket);
        if ($attribute == PLAYER) {
            $player = $this->getPlayer($id);
            $txData = mask(json_encode(array('type'=>'system', 'state'=>RESULT, 'message'=>'setWinnerOrLoser', 'winnerOrLoser'=>$player->winnerOrLoser)));
            sendMessage($txData, $socket);
        }
        foreach ($playerArray as $i) {
            $txData = mask(json_encode(array('type'=>'system', 'state'=>RESULT, 'message'=>'setResultOfPlayer', 'id'=>$i->id, 'name'=>$i->name, 'position'=>$i->position, 'point'=>$i->point)));
            sendMessage($txData, $socket);
        }
        foreach ($resultOfFortunetellerArray as $i) {
            $txData = mask(json_encode(array('type'=>'system', 'state'=>RESULT, 'message'=>'setResultOfFortuneteller', 'id'=>$i->id, 'selectionId'=>$i->selectionId)));
            sendMessage($txData, $socket);
        }
        foreach ($resultOfThiefArray as $i) {
            $txData = mask(json_encode(array('type'=>'system', 'state'=>RESULT, 'message'=>'setResultOfThief', 'id'=>$i->id, 'selectionId'=>$i->selectionId)));
            sendMessage($txData, $socket);
        }
        $txData = mask(json_encode(array('type'=>'system', 'state'=>RESULT, 'message'=>'display', 'villageId'=>$villageId, 'attribute'=>$attribute, 'side'=>$this->winnerSide)));
        sendMessage($txData, $socket);
    }
}
?>
