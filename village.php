<?php
class Village {
    public $id;
    public $name;
    public $password;
    public $spectatorFlag;
    public $currentParticipantId;
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
        outputLog('ENTER construct of Village');
        $this->id = $id;
        $this->name = $name;
        $this->password = $password;
        $this->spectatorFlag = $spectatorFlag;
        global $positionArray;
        foreach ($positionArray as $i) {
            $this->numberOfPositionArray[$i] = 0;
        }
        $this->talkingTime = 3;
        $this->state = PARTICIPATION;

        $this->currentParticipantId = rand(0, 100);
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
        outputLog('ENTER clickParticipationAsPlayer');
        $name = $messageArray->name;
        $this->participateInVillage($socket, PLAYER, $name);
    }

    //socketで「観戦者として参加」をクリック
    public function clickParticipationAsSpectator($socket, $messageArray) {
        outputLog('ENTER clickParticipationAsSpectator');
        $name = $messageArray->name;
        $this->participateInVillage($socket, SPECTATOR, $name);
    }

    //待機画面に遷移
    public function goToWaitingFromParticipation($socket, $attribute, $id) {
        outputLog('ENTER goToWaitingFromParticipation, attribute: '. $attribute. ', id: '. $id);
        $this->displayWaiting($socket, $attribute, $id);
    }

    //村参加画面を表示
    public function displayParticipation($socket, $villageId, $villageName, $spectatorFlag) {
        outputLog('ENTER displayParticipation, villageId: '. $villageId. ', villageName: '. $villageName. ', spectatorFlag: '. $spectatorFlag);
        $txData = json_encode(array('type'=>'system', 'state'=>PARTICIPATION, 'message'=>'display', 'villageId'=>$villageId, 'villageName'=>$villageName, 'spectatorFlag'=>$spectatorFlag));
        sendMessage($txData, $socket);
    }

    //村に参加
    public function participateInVillage($socket, $attribute, $name) {
        outputLog('ENTER participateInVillage, attribute: '. $attribute. ', name: '. $name);
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
                $txData = json_encode(array('type'=>'system', 'state'=>PARTICIPATION, 'message'=>'reject'));
                sendMessage($txData, $socket);
            }
            else {
                $this->state = WAITING;
                foreach ($this->playerArray as $i) {
                    $txData = json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'add', 'attribute'=>$attribute, 'id'=>$this->currentParticipantId, 'name'=>$name));
                    sendMessage($txData, $i->socket);
                }
                foreach ($this->spectatorArray as $i) {
                    $txData = json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'add', 'attribute'=>$attribute, 'id'=>$this->currentParticipantId, 'name'=>$name));
                    sendMessage($txData, $i->socket);
                }
                switch ($attribute) {
                    case PLAYER:
                        //プレイヤーを作成
                        $player = new player($this->currentParticipantId, $name, $socket);
                        $this->playerArray[] = $player;
                        break;
                    case SPECTATOR:
                        //観戦者を作成
                        $spectator = new Spectator($this->currentParticipantId, $name, $socket);
                        $this->spectatorArray[] = $spectator;
                        break;
                }
                $this->goToWaitingFromParticipation($socket, $attribute, $this->currentParticipantId);
                $this->currentParticipantId++;
            }
        }
        else {
                $txData = json_encode(array('type'=>'system', 'state'=>PARTICIPATION, 'message'=>'alreadyStarted'));
                sendMessage($txData, $socket);
        }
    }


    ////Waiting////
    //役職数がクリックされた
    public function clickNumberOfPosition($messageArray) {
        outputLog('ENTER clickNumberOfPosition');
        $position = $messageArray->position;
        $number = $messageArray->number;
        $numberOfPositionArray[$position] = $number;
        foreach ($this->playerArray as $i) {
            $txData = json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'setNumberOfPosition', 'position'=>$position, 'number'=>$number));
            sendMessage($txData, $i->socket);
        }
        foreach ($this->spectatorArray as $i) {
            $txData = json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'setNumberOfPosition', 'position'=>$position, 'number'=>$number));
            sendMessage($txData, $i->socket);
        }
    }

    //話し合い時間がクリックされた
    public function clickTalkingTime($messageArray) {
        outputLog('ENTER clickTalkingTime');
        $time = $messageArray->time;
        $talkingTime = $time;
        foreach ($this->playerArray as $i) {
            $txData = json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'setTalkingTime', 'time'=>$time));
            sendMessage($txData, $i->socket);
        }
        foreach ($this->spectatorArray as $i) {
            $txData = json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'setTalkingTime', 'time'=>$time));
            sendMessage($txData, $i->socket);
        }
    }

    //「ゲーム開始」がクリックされた
    public function clickGameStart($messageArray) {
        outputLog('ENTER clickGameStart');
        $id = $messageArray->id;
        $player = $this->getPlayer($id);
        if ($player != null) {
            $player->gameStartFlag = true;
            foreach ($this->playerArray as $i) {
                $txData = json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'setGameStart', 'id'=>$id));
                sendMessage($txData, $i->socket);
            }
            foreach ($this->spectatorArray as $i) {
                $txData = json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'setGameStart', 'id'=>$id));
                sendMessage($txData, $i->socket);
            }

            $sum = 0;
            foreach ($this->playerArray as $i) {
                if ($i->gameStartFlag == true) {
                    $sum++;
                }
            }
            if ($sum == count($this->playerArray)) {
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
        outputLog('ENTER goToActionFromWaiting, id: '. $id);
        $this->displayAction($socket, $id);
    }

    //夜の画面に遷移
    public function goToNightFromWaiting($socket) {
        outputLog('ENTER goToNightFromWaiting');
        $this->displayNight($socket);
    }

    //ゲームを開始
    public function startGame() {
        outputLog('ENTER startGame');
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
        outputLog('ENTER displayWaiting, attribute: '. $attribute. ', id: '. $id);
        $txData = json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'init', 'villageId'=>$this->id, 'villageName'=>$this->name, 'id'=>$id, 'attribute'=>$attribute));
        sendMessage($txData, $socket);
        foreach ($this->playerArray as $i) {
            $txData = json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'add', 'attribute'=>PLAYER, 'id'=>$i->id, 'name'=>$i->name));
            sendMessage($txData, $socket);
        }
        foreach ($this->spectatorArray as $i) {
            $txData = json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'add', 'attribute'=>SPECTATOR, 'id'=>$i->id, 'name'=>$i->name));
            sendMessage($txData, $socket);
        }
        global $positionArray;
        foreach ($positionArray as $i) {
            $txData = json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'setNumberOfPosition', 'position'=> $i, 'number'=>$this->numberOfPositionArray[$i]));
            sendMessage($txData, $socket);
        }
        $txData = json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'setTalkingTime', 'time'=> $this->talkingTime));
        sendMessage($txData, $socket);
        foreach ($this->playerArray as $i) {
            if ($i->gameStartFlag == true) {
                $txData = json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'setGameStart', 'id'=>$i->id));
                sendMessage($txData, $socket);
            }
        }
        $txData = json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'display'));
        sendMessage($txData, $socket);
    }


    ////Action////
    //「次へ」がクリックされた
    public function clickNotification($messageArray) {
        outputLog('ENTER clickNotification');
        $id = $messageArray->id;
        $player = $this->getPlayer($id);
        if ($player != null) {
            $player->actionFlag = true;
            if ($player->position == FORTUNETELLER || $player->position == THIEF) {
                $selectionId = $messageArray->selectionId;
                if ($player->position == FORTUNETELLER) {
                    $this->resultOfFortunetellerArray[] = array('id' => $id, 'selectionId' => $selectionId);
                    foreach ($this->spectatorArray as $i) {
                        $txData = json_encode(array('type'=>'system', 'state'=>NIGHT, 'message'=>'setResultOfFortuneteller', 'id'=>$id, 'selectionId'=>$selectionId));
                        sendMessage($txData, $i->socket);
                    }
                }
                else if ($player->position == THIEF) {
                    $this->resultOfThiefArray[] = array('id' => $id, 'selectionId' => $selectionId);
                    foreach ($this->spectatorArray as $i) {
                        $txData = json_encode(array('type'=>'system', 'state'=>NIGHT, 'message'=>'setResultOfThief', 'id'=>$id, 'selectionId'=>$selectionId));
                        sendMessage($txData, $i->socket);
                    }
                }
            }
            $this->goToNotificationFromAction($player->socket, $id);
        }
    }

    //通知画面に遷移
    public function goToNotificationFromAction($socket, $id) {
        outputLog('ENTER goToNotificationFromAction, id: '. $id);
        $this->displayNotification($socket, $id);
    }

    //行動画面を表示
    public function displayAction($socket, $id) {
        outputLog('ENTER displayAction, id: '. $id);
        $player = $this->getPlayer($id);
        $txData = json_encode(array('type'=>'system', 'state'=>ACTION, 'message'=>'init', 'villageId'=>$this->id, 'id'=>$id, 'position'=>$player->position));
        sendMessage($txData, $socket);
        if ($player->position == FORTUNETELLER || $player->position == THIEF) {
            foreach ($this->playerArray as $i) {
                if ($i->id != $id) {
                    $txData = json_encode(array('type'=>'system', 'state'=>ACTION, 'message'=>'setPlayer', 'id'=>$i->id, 'name'=>$i->name));
                    sendMessage($txData, $socket);
                }
            }
        }
        $txData = json_encode(array('type'=>'system', 'state'=>ACTION, 'message'=>'display'));
        sendMessage($txData, $socket);
    }


    ////Notification////
    //「昼のフェーズへ」がクリックされた
    public function clickDaytime($messageArray) {
        outputLog('ENTER clickDaytime');
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
        outputLog('ENTER goToDaytimeFromNotification, id: '. $id);
        $this->displayDaytime($socket, PLAYER, $id);
    }

    //通知画面を表示
    public function displayNotification($socket, $id) {
        outputLog('ENTER displayNotification, id: '. $id);
        $player = $this->getPlayer($id);
        $txData = json_encode(array('type'=>'system', 'state'=>NOTIFICATION, 'message'=>'init', 'villageId'=>$this->id, 'id'=>$id, 'position'=>$player->position));
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
                        $txData = json_encode(array('type'=>'system', 'state'=>NOTIFICATION, 'message'=>'setBuddy', 'name'=>$i));
                        sendMessage($txData, $socket);
                    }
                }
                break;
            case FORTUNETELLER:
                if ($player->selectionId != -1) {
                    $selectionPlayer = $this->getPlayer($player->selectionId);
                    $txData = json_encode(array('type'=>'system', 'state'=>NOTIFICATION, 'message'=>'setResult', 'name'=>$selectionPlayer->name, 'position'=>$selectionPlayer->position));
                    sendMessage($txData, $socket);
                }
                else {
                    $txData = json_encode(array('type'=>'system', 'state'=>NOTIFICATION, 'message'=>'setResultOfField', 'position1'=>$this->fieldPosition1, 'position2'=>$this->fieldPosition2));
                    sendMessage($txData, $socket);
                }
                break;
            case THIEF:
                if ($player->selectionId != -1) {
                    $selectionPlayer = $this->getPlayer($player->selectionId);
                    $txData = json_encode(array('type'=>'system', 'state'=>NOTIFICATION, 'message'=>'setResult', 'name'=>$selectionPlayer->name, 'position'=>$selectionPlayer->position));
                    sendMessage($txData, $socket);
                }
                break;
        }
        $txData = json_encode(array('type'=>'system', 'state'=>NOTIFICATION, 'message'=>'display'));
        sendMessage($txData, $socket);
    }


    ////Night////
    //昼の画面に遷移
    public function goToDaytimeFromNight($socket, $id) {
        outputLog('ENTER goToDaytimeFromNight, id: '. $id);
        $this->displayDaytime($socket, SPECTATOR, $id);
    }

    //夜の画面を表示
    public function displayNight($socket) {
        outputLog('ENTER displayNight');
        $txData = json_encode(array('type'=>'system', 'state'=>NIGHT, 'message'=>'init'));
        sendMessage($txData, $socket);
        foreach ($this->playerArray as $i) {
            $txData = json_encode(array('type'=>'system', 'state'=>NIGHT, 'message'=>'setPositionOfPlayer', 'id'=>$i->id, 'name'=>$i->name, 'position'=>$i->position));
            sendMessage($txData, $socket);
            if ($i->position == FORTUNETELLER) {
                if ($i->actionFlag == true) {
                    $txData = json_encode(array('type'=>'system', 'state'=>NIGHT, 'message'=>'setResultOfFortuneteller', 'id'=>$i->id, 'selectionId'=>$i->selectionId));
                    sendMessage($txData, $socket);
                }
            }
            else if ($i->position == THIEF) {
                if ($i->actionFlag == true) {
                    $txData = json_encode(array('type'=>'system', 'state'=>NIGHT, 'message'=>'setResultOfThief', 'id'=>$i->id, 'selectionId'=>$i->selectionId));
                    sendMessage($txData, $socket);
                }
            }
        }
        $txData = json_encode(array('type'=>'system', 'state'=>NIGHT, 'message'=>'display'));
        sendMessage($txData, $socket);
    }


    ////Daytime////
    //「話し合い延長」がクリックされた
    public function clickExtension($messageArray) {
        outputLog('ENTER clickExtension');
        $endingTime = new DateTime('+1 minutes');
            foreach ($this->playerArray as $i) {
                $txData = json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'setTalkingTime', 'time'=>1));
                sendMessage($txData, $i->socket);
            }
        foreach ($this->spectatorArray as $i) {
                $txData = json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'setTalkingTime', 'time'=>1));
                sendMessage($txData, $i->socket);
        }
    }

    //「話し合い終了」がクリックされた
    public function clickTalksEnd($messageArray) {
        outputLog('ENTER clickTalksEnd');
        $id = $messageArray->id;
        $player = $this->getPlayer($id);
        if ($player != null) {
            $player->talksEndFlag = true;
            foreach ($this->playerArray as $i) {
                $txData = json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'setTalksEnd', 'id'=>$id));
                sendMessage($txData, $i->socket);
            }
            foreach ($this->spectatorArray as $i) {
                $txData = json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'setTalksEnd', 'id'=>$id));
                sendMessage($txData, $i->socket);
            }

            $sum = 0;
            foreach ($this->playerArray as $i) {
                if ($i->talksEndFlag == true) {
                    $sum++;
                }
            }
            if ($sum == count($this->playerArray)) {
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
        outputLog('ENTER goToExecutionFromDaytime, attribute: '. $attribute. ', id: '. $id);
        displayExecution($socket, $attribute, $id);
    }

    //昼の画面を表示
    public function displayDaytime($socket, $attribute, $id) {
        outputLog('ENTER displayDaytime, attribute: '. $attribute. ', id: '. $id);
        $txData = json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'init', 'villageId'=>$this->id, 'attribute'=>$attribute, 'id'=>$id));
        sendMessage($txData, $socket);
        foreach ($this->playerArray as $i) {
            $txData = json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'setPlayer', 'id'=>$i->id, 'name'=>$i->name));
            sendMessage($txData, $socket);
        }
        foreach ($this->spectatorArray as $i) {
            $txData = json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'setSpectator', 'id'=>$i->id, 'name'=>$i->name));
            sendMessage($txData, $socket);
        }
        global $positionArray;
        foreach ($positionArray as $i) {
            $txData = json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'setNumberOfPosition', 'position'=>$i, 'number'=>$this->numberOfPositionArray[$i]));
            sendMessage($txData, $socket);
        }
        $txData = json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'setTalkingTime', 'time'=>$this->talkingTime));
        sendMessage($txData, $socket);
        foreach ($this->playerArray as $i) {
            if ($i->talksEndFlag == true) {
                $txData = json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'talksEnd', 'id'=>$i->id));
                sendMessage($txData, $socket);
            }
        }
        if ($attribute == SPECTATOR) {
            foreach ($this->playerArray as $i) {
                $txData = json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'setPositionOfPlayer', 'id'=>$i->id, 'position'=>$i->position));
                sendMessage($txData, $socket);
                if ($i->position == FORTUNETELLER) {
                    $txData = json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'setResultOfFortuneteller', 'id'=>$i->id, 'selectionId'=>$i->selectionId));
                    sendMessage($txData, $socket);
                }
                else if ($i->position == THIEF) {
                    $txData = json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'setResultOfThief', 'id'=>$i->id, 'selectionId'=>$i->selectionId));
                    sendMessage($txData, $socket);
                }
            }
        }
        $txData = json_encode(array('type'=>'system', 'state'=>DAYTIME, 'message'=>'display'));
        sendMessage($txData, $socket);
    }


    ////Execution////
    //「結果発表へ」がクリックされた
    public function clickResult($messageArray) {
        outputLog('ENTER clickResult');
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
            if ($sum == count($this->playerArray)) {
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
        outputLog('ENTER goToResultFromExecution, attribute: '. $attribute);
        $this->displayResult($socket, $attribute);
    }

    //吊る人選択画面を表示
    public function displayExecution($socket, $attribute, $id) {
        outputLog('ENTER displayExecution, attribute: '. $attribute. ', id: '. $id);
        $txData = json_encode(array('type'=>'system', 'state'=>EXECUTION, 'message'=>'init', 'villageId'=>$this->id, 'attribute'=>$attribute, 'id'=>$id));
        sendMessage($txData, $socket);
        if ($attribute == PLAYER) {
            foreach ($this->playerArray as $i) {
                if ($i->id != $id) {
                    $txData = json_encode(array('type'=>'system', 'state'=>EXECUTION, 'message'=>'setPlayer', 'id'=>$i->id, 'name'=>$i->name));
                    sendMessage($txData, $socket);
                }
            }
        }
        $txData = json_encode(array('type'=>'system', 'state'=>EXECUTION, 'message'=>'display'));
        sendMessage($txData, $socket);
    }

    //勝者を判定
    public function judgeWinner() {
        outputLog('ENTER judgeWinner');
        //怪盗が交換した後の役職に設定
        foreach ($this->playerArray as $i) {
            if ($i->position == THIEF) {
                if ($i->selectionId != -1) {
                    foreach ($this->playerArray as $j) {
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
        foreach ($this->playerArray as $i) {
            if ($i->hangingNumber > $max) {
                $max = $i->hangingNumber;
            }
        }
        $maxPlayerArray = array();
        foreach ($this->playerArray as $i) {
            if ($i->hangingNumber == $max) {
                $maxPlayerArray[] = $i;
            }
        }

        //maxは1？
        if ($max == 1) {
            //プレイヤーの中に人狼はいる？
            $flag = false;
            foreach ($this->playerArray as $i) {
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
        outputLog('ENTER isWinner, position: '. $position. ', winnerSide: '. $winnerSide);
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
        outputLog('ENTER getPoint, position: '. $position. ', winnerSide: '. $winnerSide);
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
        outputLog('ENTER clickNextNight');
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
        outputLog('ENTER clickExit');
        $this->state = WAITING;
        foreach ($this->playerArray as $i) {
            $this->goToWaitingFromResult($i->socket, PLAYER, $i->id);
        }
        foreach ($this->spectatorArray as $i) {
            $this->goToWaitingFromResult($i->socket, SPECTATOR, $i->id);
        }
    }

    //行動画面に遷移
    public function goToActionFromResult($socket, $id) {
        outputLog('ENTER goToActionFromResult, id: '. $id);
        $this->displayAction($socket, $id);
    }

    //夜の画面に遷移
    public function goToNightFromResult($socket) {
        outputLog('ENTER goToNightFromResult');
        $this->displayNight($socket);
    }

    //待機画面に遷移
    public function goToWaitingFromResult($socket, $attribute, $id) {
        outputLog('ENTER goToWaitingFromResult, attribute: '. $attribute. ', id: '. $id);
        $this->displayWaiting($socket, $attribute, $id);
    }

    //結果発表画面を表示
    public function displayResult($socket, $attribute, $id) {
        outputLog('ENTER displayResult, attribute: '. $attribute. ', id: '. $id);
        $txData = json_encode(array('type'=>'system', 'state'=>RESULT, 'message'=>'init', 'villageId'=>$id, 'attribute'=>$attribute, 'side'=>$this->winnerSide));
        sendMessage($txData, $socket);
        if ($attribute == PLAYER) {
            $player = $this->getPlayer($id);
            $txData = json_encode(array('type'=>'system', 'state'=>RESULT, 'message'=>'setWinnerOrLoser', 'winnerOrLoser'=>$player->winnerOrLoser));
            sendMessage($txData, $socket);
        }
        foreach ($this->playerArray as $i) {
            $txData = json_encode(array('type'=>'system', 'state'=>RESULT, 'message'=>'setResultOfPlayer', 'id'=>$i->id, 'name'=>$i->name, 'position'=>$i->position, 'point'=>$i->point));
            sendMessage($txData, $socket);
        }
        foreach ($resultOfFortunetellerArray as $i) {
            $txData = json_encode(array('type'=>'system', 'state'=>RESULT, 'message'=>'setResultOfFortuneteller', 'id'=>$i->id, 'selectionId'=>$i->selectionId));
            sendMessage($txData, $socket);
        }
        foreach ($resultOfThiefArray as $i) {
            $txData = json_encode(array('type'=>'system', 'state'=>RESULT, 'message'=>'setResultOfThief', 'id'=>$i->id, 'selectionId'=>$i->selectionId));
            sendMessage($txData, $socket);
        }
        $txData = json_encode(array('type'=>'system', 'state'=>RESULT, 'message'=>'display'));
        sendMessage($txData, $socket);
    }
}
?>
