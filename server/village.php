<?php
class Village {
    private $id;
    private $name;
    private $password;
    private $spectatorFlag;
    private $currentParticipantId;
    private $playerArray = array();
    private $spectatorArray = array();
    private $participantArray = array();
    private $numberOfParticipant;
    private $numberOfPositionArray = array();
    private $talkingTime;
    private $endingTime;
    private $state;
    private $winnerSide;
    private $fieldPosition1;
    private $fieldPosition2;
    private $resultOfFortunetellerArray = array();
    private $resultOfThiefArray = array();
    private $hangingPlayerArray = array();
    private $villageManagement;

    //コンストラクタ
    public function __construct(&$villageManagement, $id, $name, $password, $spectatorFlag) {
        outputLog('ENTER: construct of Village');
        $this->villageManagement = $villageManagement;
        $this->id = $id;
        $this->name = $name;
        $this->password = $password;
        $this->spectatorFlag = $spectatorFlag;
        $this->numberOfParticipant = 0;
        global $positionArray;
        foreach ($positionArray as $i) {
            $this->numberOfPositionArray[$i] = 0;
        }
        $this->talkingTime = 3;
        $this->state = 'PARTICIPATION';

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

    //プレイヤーを削除
    public function removePlayer($id) {
        outputLog('ENTER: removePlayer, id: '. $id);
        $player = $this->getPlayer($id);
        $foundPlayer = array_search($player, $this->playerArray);
        if ($foundPlayer !== false) {
            unset($this->playerArray[$foundPlayer]);
            return true;
        }
        return false;
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

    //観戦者を削除
    public function removeSpectator($id) {
        outputLog('ENTER: removeSpectator, id: '. $id);
        $spectator = $this->getSpectator($id);
        $foundSpectator = array_search($spectator, $this->spectatorArray);
        if ($foundSpectator !== false) {
            unset($this->spectatorArray[$foundSpectator]);
            return true;
        }
        return false;
    }

    //numberOfParticipantを取得
    public function getNumberOfParticipant() {
        return $this->numberOfParticipant;
    }

    //participantArrayに追加
    public function addParticipant($socket) {
        outputLog('ENTER: addParticipant');
        $this->participantArray[] = $socket;
        $this->numberOfParticipant++;
    }

    //participantArrayから削除
    public function removeParticipant($socket) {
        outputLog('ENTER: removeParticipant');
        $foundSocket = array_search($socket, $this->participantArray);
        if ($foundSocket !== false) {
            unset($this->participantArray[$foundSocket]);
            $this->numberOfParticipant--;
            return true;
        }
        return false;
    }

    //IDを取得
    public function getId() {
        return $this->id;
    }

    //名前を取得
    public function getName() {
        return $this->name;
    }

    //パスワードを取得
    public function getPassword() {
        return $this->password;
    }

    //観戦者ありなしを取得
    public function getSpectatorFlag() {
        return $this->spectatorFlag;
    }

    //プレイヤー配列を取得
    public function getPlayerArray() {
        return $this->playerArray;
    }

    //観戦者配列を取得
    public function getSpectatorArray() {
        return $this->spectatorArray;
    }

    //村の状態を取得
    public function getState() {
        return $this->state;
    }

    //残り時間を取得
    public function getRemaingTime() {
        $remainingTime = 0;
        $nowTime = new DateTime();
        if ($this->endingTime > $nowTime) {
            $remainingDate = $nowTime->diff($this->endingTime);
            $remainingTime = $remainingDate->i * 60 + $remainingDate->s;
        }
        return $remainingTime;
    }

    //プレイヤーに行動結果を通知
    public function sendResultOfAction($socket, $id, $state) {
        $player = $this->getPlayer($id);
        switch ($player->position) {
            case 'WEREWOLF':
                $buddyName = array();
                foreach ($this->playerArray as $i) {
                    if (($i->position == 'WEREWOLF') && ($i->id != $id)) {
                        $buddyName[] = $i->name;
                    }
                }
                if (empty($buddyName) == false) {
                    foreach ($buddyName as $i) {
                        $messageArray = array('type'=>'system', 'state'=>$state, 'message'=>'setBuddy', 'name'=>$i);
                        sendMessage($messageArray, $socket);
                    }
                }
                break;
            case 'FORTUNETELLER':
                if ($player->selectionId != -1) {
                    $selectionPlayer = $this->getPlayer($player->selectionId);
                    $messageArray = array('type'=>'system', 'state'=>$state, 'message'=>'setResult', 'name'=>$selectionPlayer->name, 'position'=>$selectionPlayer->position);
                    sendMessage($messageArray, $socket);
                }
                else {
                    $messageArray = array('type'=>'system', 'state'=>$state, 'message'=>'setResultOfField', 'position1'=>$this->fieldPosition1, 'position2'=>$this->fieldPosition2);
                    sendMessage($messageArray, $socket);
                }
                break;
            case 'THIEF':
                if ($player->selectionId != -1) {
                    $selectionPlayer = $this->getPlayer($player->selectionId);
                    $messageArray = array('type'=>'system', 'state'=>$state, 'message'=>'setResult', 'name'=>$selectionPlayer->name, 'position'=>$selectionPlayer->position);
                    sendMessage($messageArray, $socket);
                }
                break;
        }
    }


    ////Participation////
    //socketで「プレイヤーとして参加」をクリック
    public function clickParticipationAsPlayer($socket, $messageArray) {
        outputLog('ENTER: clickParticipationAsPlayer');
        $name = $messageArray->name;
        $this->participateInVillage($socket, 'PLAYER', $name);
    }

    //socketで「観戦者として参加」をクリック
    public function clickParticipationAsSpectator($socket, $messageArray) {
        outputLog('ENTER: clickParticipationAsSpectator');
        $name = $messageArray->name;
        $this->participateInVillage($socket, 'SPECTATOR', $name);
    }

    //待機画面に遷移
    public function goToWaitingFromParticipation($socket, $attribute, $id) {
        outputLog('ENTER: goToWaitingFromParticipation, attribute: '. $attribute. ', id: '. $id);
        $this->displayWaiting($socket, $attribute, $id);
    }

    //村参加画面を表示
    public function displayParticipation($socket, $villageId, $villageName, $spectatorFlag) {
        outputLog('ENTER: displayParticipation, villageId: '. $villageId. ', villageName: '. $villageName. ', spectatorFlag: '. $spectatorFlag);
        $messageArray = array('type'=>'system', 'state'=>'PARTICIPATION', 'message'=>'display', 'villageId'=>$villageId, 'villageName'=>$villageName, 'spectatorFlag'=>$spectatorFlag);
        sendMessage($messageArray, $socket);
    }

    //村に参加
    public function participateInVillage($socket, $attribute, $name) {
        outputLog('ENTER: participateInVillage, attribute: '. $attribute. ', name: '. $name);
        if (($this->state == 'PARTICIPATION') || ($this->state == 'WAITING')) {
            if (count($this->playerArray) >= 7) {
                $messageArray = array('type'=>'system', 'state'=>'PARTICIPATION', 'message'=>'exceedNumberOfPlayer');
                sendMessage($messageArray, $socket);
            }
            else {
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
                    $messageArray = array('type'=>'system', 'state'=>'PARTICIPATION', 'message'=>'reject');
                    sendMessage($messageArray, $socket);
                }
                else {
                    $this->state = 'WAITING';
                    //他の参加者に通知
                    foreach ($this->playerArray as $i) {
                        $messageArray = array('type'=>'system', 'state'=>'WAITING', 'message'=>'add', 'attribute'=>$attribute, 'id'=>$this->currentParticipantId, 'name'=>$name);
                        sendMessage($messageArray, $i->socket);
                    }
                    foreach ($this->spectatorArray as $i) {
                        $messageArray = array('type'=>'system', 'state'=>'WAITING', 'message'=>'add', 'attribute'=>$attribute, 'id'=>$this->currentParticipantId, 'name'=>$name);
                        sendMessage($messageArray, $i->socket);
                    }
                    switch ($attribute) {
                        case 'PLAYER':
                            //プレイヤーを作成
                            $player = new player($this->currentParticipantId, $name, $socket);
                            $this->playerArray[] = $player;
                            break;
                        case 'SPECTATOR':
                            //観戦者を作成
                            $spectator = new Spectator($this->currentParticipantId, $name, $socket);
                            $this->spectatorArray[] = $spectator;
                            break;
                    }
                    $this->goToWaitingFromParticipation($socket, $attribute, $this->currentParticipantId);
                    $this->currentParticipantId++;
                }
            }
        }
        else {
                $messageArray = array('type'=>'system', 'state'=>'PARTICIPATION', 'message'=>'alreadyStarted');
                sendMessage($messageArray, $socket);
        }
    }


    ////Waiting////
    //役職数がクリックされた
    public function clickNumberOfPosition($messageArray) {
        outputLog('ENTER: clickNumberOfPosition');
        $position = $messageArray->position;
        $number = $messageArray->number;
        $this->numberOfPositionArray[$position] = $number;
        //参加者に通知
        foreach ($this->playerArray as $i) {
            $messageArray = array('type'=>'system', 'state'=>'WAITING', 'message'=>'setNumberOfPosition', 'position'=>$position, 'number'=>$number);
            sendMessage($messageArray, $i->socket);
        }
        foreach ($this->spectatorArray as $i) {
            $messageArray = array('type'=>'system', 'state'=>'WAITING', 'message'=>'setNumberOfPosition', 'position'=>$position, 'number'=>$number);
            sendMessage($messageArray, $i->socket);
        }
    }

    //話し合い時間がクリックされた
    public function clickTalkingTime($messageArray) {
        outputLog('ENTER: clickTalkingTime');
        $time = $messageArray->time;
        $this->talkingTime = $time;
        //参加者に通知
        foreach ($this->playerArray as $i) {
            $messageArray = array('type'=>'system', 'state'=>'WAITING', 'message'=>'setTalkingTime', 'time'=>$time);
            sendMessage($messageArray, $i->socket);
        }
        foreach ($this->spectatorArray as $i) {
            $messageArray = array('type'=>'system', 'state'=>'WAITING', 'message'=>'setTalkingTime', 'time'=>$time);
            sendMessage($messageArray, $i->socket);
        }
    }

    //「ゲーム開始」がクリックされた
    public function clickGameStart($messageArray) {
        outputLog('ENTER: clickGameStart');
        $id = $messageArray->id;
        $player = $this->getPlayer($id);
        if ($player !== null) {
            $player->gameStartFlag = true;
            //参加者に通知
            foreach ($this->playerArray as $i) {
                $messageArray = array('type'=>'system', 'state'=>'WAITING', 'message'=>'setGameStart', 'id'=>$id);
                sendMessage($messageArray, $i->socket);
            }
            foreach ($this->spectatorArray as $i) {
                $messageArray = array('type'=>'system', 'state'=>'WAITING', 'message'=>'setGameStart', 'id'=>$id);
                sendMessage($messageArray, $i->socket);
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
        outputLog('ENTER: goToActionFromWaiting, id: '. $id);
        $this->displayAction($socket, $id);
    }

    //夜の画面に遷移
    public function goToNightFromWaiting($socket) {
        outputLog('ENTER: goToNightFromWaiting');
        $this->displayNight($socket);
    }

    //ゲームを開始
    public function startGame() {
        outputLog('ENTER: startGame');
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

        $this->state = 'NIGHT';
    }

    //待機画面を表示
    public function displayWaiting($socket, $attribute, $id) {
        outputLog('ENTER: displayWaiting, attribute: '. $attribute. ', id: '. $id);
        $messageArray = array('type'=>'system', 'state'=>'WAITING', 'message'=>'init', 'villageId'=>$this->id, 'villageName'=>$this->name, 'id'=>$id, 'attribute'=>$attribute);
        sendMessage($messageArray, $socket);
        foreach ($this->playerArray as $i) {
            $messageArray = array('type'=>'system', 'state'=>'WAITING', 'message'=>'add', 'attribute'=>'PLAYER', 'id'=>$i->id, 'name'=>$i->name);
            sendMessage($messageArray, $socket);
        }
        foreach ($this->spectatorArray as $i) {
            $messageArray = array('type'=>'system', 'state'=>'WAITING', 'message'=>'add', 'attribute'=>'SPECTATOR', 'id'=>$i->id, 'name'=>$i->name);
            sendMessage($messageArray, $socket);
        }
        global $positionArray;
        foreach ($positionArray as $i) {
            $messageArray = array('type'=>'system', 'state'=>'WAITING', 'message'=>'setNumberOfPosition', 'position'=> $i, 'number'=>$this->numberOfPositionArray[$i]);
            sendMessage($messageArray, $socket);
        }
        $messageArray = array('type'=>'system', 'state'=>'WAITING', 'message'=>'setTalkingTime', 'time'=> $this->talkingTime);
        sendMessage($messageArray, $socket);
        foreach ($this->playerArray as $i) {
            if ($i->gameStartFlag == true) {
                $messageArray = array('type'=>'system', 'state'=>'WAITING', 'message'=>'setGameStart', 'id'=>$i->id);
                sendMessage($messageArray, $socket);
            }
        }
        $messageArray = array('type'=>'system', 'state'=>'WAITING', 'message'=>'display');
        sendMessage($messageArray, $socket);
    }

    //中断により待機画面の表示
    public function displayWaitingByCessation($socket, $attribute, $id, $name) {
        outputLog('ENTER: displayWaitingByCessation, attribute: '. $attribute. ', id: '. $id. ', name: '. $name);
        $messageArray = array('type'=>'system', 'state'=>'WAITING', 'message'=>'displayByCessation', 'name'=>$name);
        sendMessage($messageArray, $socket);
        $this->displayWaiting($socket, $attribute, $id);
    }

    //待機画面の初期化
    public function initWaiting() {
        $this->id = $this->villageManagement->getCurrentId();
        $this->state = 'WAITING';
        foreach ($this->playerArray as $i) {
            $i->point = 0;
            $i->gameStartFlag = false;
            $i->actionFlag = false;
            $i->talksStartFlag = false;
            $i->talksEndFlag = false;
            $i->executionFlag = false;
            $i->resultFlag = false;
            $i->hangingNumber = 0;
        }
        foreach ($this->spectatorArray as $i) {
            $i->resultFlag = false;
        }
    }


    ////Action////
    //「次へ」がクリックされた
    public function clickNotification($messageArray) {
        outputLog('ENTER: clickNotification');
        $id = $messageArray->id;
        $player = $this->getPlayer($id);
        if ($player !== null) {
            $player->actionFlag = true;
            if ($player->position == 'FORTUNETELLER' || $player->position == 'THIEF') {
                $player->selectionId = $messageArray->selectionId;
                if ($player->position == 'FORTUNETELLER') {
                    $this->resultOfFortunetellerArray[] = array('id' => $id, 'selectionId' => $player->selectionId);
                    //観戦者に通知
                    foreach ($this->spectatorArray as $i) {
                        $messageArray = array('type'=>'system', 'state'=>'NIGHT', 'message'=>'setResultOfFortuneteller', 'id'=>$id, 'selectionId'=>$player->selectionId);
                        sendMessage($messageArray, $i->socket);
                    }
                }
                else if ($player->position == 'THIEF') {
                    $this->resultOfThiefArray[] = array('id' => $id, 'selectionId' => $player->selectionId);
                    //観戦者に通知
                    foreach ($this->spectatorArray as $i) {
                        $messageArray = array('type'=>'system', 'state'=>'NIGHT', 'message'=>'setResultOfThief', 'id'=>$id, 'selectionId'=>$player->selectionId);
                        sendMessage($messageArray, $i->socket);
                    }
                }
            }
            $this->goToNotificationFromAction($player->socket, $id);
        }
    }

    //「ゲーム終了」がクリックされた
    public function clickExitInAction($messageArray) {
        outputLog('ENTER: clickExitInAction');
        $id = $messageArray->id;
        $player = $this->getPlayer($id);
        if ($player !== null) {
            $this->initWaiting();
            foreach ($this->playerArray as $i) {
                $this->goToWaitingFromAction($i->socket, 'PLAYER', $i->id, $player->name);
            }
            foreach ($this->spectatorArray as $i) {
                $this->goToWaitingFromAction($i->socket, 'SPECTATOR', $i->id, $player->name);
            }
        }
    }

    //通知画面に遷移
    public function goToNotificationFromAction($socket, $id) {
        outputLog('ENTER: goToNotificationFromAction, id: '. $id);
        $this->displayNotification($socket, $id);
    }

    //待機画面に遷移
    public function goToWaitingFromAction($socket, $attribute, $id, $name) {
        outputLog('ENTER: goToWaitingFromAction, attribute: '. $attribute. ', id: '. $id. ', name: '. $name);
        $this->displayWaitingByCessation($socket, $attribute, $id, $name);
    }

    //行動画面を表示
    public function displayAction($socket, $id) {
        outputLog('ENTER: displayAction, id: '. $id);
        $player = $this->getPlayer($id);
        $messageArray = array('type'=>'system', 'state'=>'ACTION', 'message'=>'init', 'villageId'=>$this->id, 'id'=>$id, 'position'=>$player->position);
        sendMessage($messageArray, $socket);
        if ($player->position == 'FORTUNETELLER' || $player->position == 'THIEF') {
            foreach ($this->playerArray as $i) {
                if ($i->id != $id) {
                    $messageArray = array('type'=>'system', 'state'=>'ACTION', 'message'=>'setPlayer', 'id'=>$i->id, 'name'=>$i->name);
                    sendMessage($messageArray, $socket);
                }
            }
        }
        $messageArray = array('type'=>'system', 'state'=>'ACTION', 'message'=>'display');
        sendMessage($messageArray, $socket);
    }


    ////Notification////
    //「話し合い開始」がクリックされた
    public function clickTalksStart($messageArray) {
        outputLog('ENTER: clickTalksStart');
        $id = $messageArray->id;
        $player = $this->getPlayer($id);
        if ($player !== null) {
            $player->talksStartFlag = true;

            $sum = 0;
            foreach ($this->playerArray as $i) {
                if ($i->talksStartFlag == true) {
                    $sum++;
                }
            }
            if ($sum == count($this->playerArray)) {
                $this->state = 'DAYTIME';
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

    //「ゲーム終了」がクリックされた
    public function clickExitInNotification($messageArray) {
        outputLog('ENTER: clickExitInNotification');
        $id = $messageArray->id;
        $player = $this->getPlayer($id);
        if ($player !== null) {
            $this->initWaiting();
            foreach ($this->playerArray as $i) {
                $this->goToWaitingFromNotification($i->socket, 'PLAYER', $i->id, $player->name);
            }
            foreach ($this->spectatorArray as $i) {
                $this->goToWaitingFromNotification($i->socket, 'SPECTATOR', $i->id, $player->name);
            }
        }
    }

    //昼の画面に遷移
    public function goToDaytimeFromNotification($socket, $id) {
        outputLog('ENTER: goToDaytimeFromNotification, id: '. $id);
        $this->displayDaytime($socket, 'PLAYER', $id);
    }

    //待機画面に遷移
    public function goToWaitingFromNotification($socket, $attribute, $id, $name) {
        outputLog('ENTER: goToWaitingFromNotification, attribute: '. $attribute. ', id: '. $id. ', name: '. $name);
        $this->displayWaitingByCessation($socket, $attribute, $id, $name);
    }

    //通知画面を表示
    public function displayNotification($socket, $id) {
        outputLog('ENTER: displayNotification, id: '. $id);
        $player = $this->getPlayer($id);
        $messageArray = array('type'=>'system', 'state'=>'NOTIFICATION', 'message'=>'init', 'villageId'=>$this->id, 'id'=>$id, 'position'=>$player->position);
        sendMessage($messageArray, $socket);
        $this->sendResultOfAction($socket, $id, 'NOTIFICATION');
        $messageArray = array('type'=>'system', 'state'=>'NOTIFICATION', 'message'=>'display');
        sendMessage($messageArray, $socket);
    }


    ////Night////
    //昼の画面に遷移
    public function goToDaytimeFromNight($socket, $id) {
        outputLog('ENTER: goToDaytimeFromNight, id: '. $id);
        $this->displayDaytime($socket, 'SPECTATOR', $id);
    }

    //夜の画面を表示
    public function displayNight($socket) {
        outputLog('ENTER: displayNight');
        $messageArray = array('type'=>'system', 'state'=>'NIGHT', 'message'=>'init');
        sendMessage($messageArray, $socket);
        foreach ($this->playerArray as $i) {
            $messageArray = array('type'=>'system', 'state'=>'NIGHT', 'message'=>'setPositionOfPlayer', 'id'=>$i->id, 'name'=>$i->name, 'position'=>$i->position);
            sendMessage($messageArray, $socket);
            if ($i->position == 'FORTUNETELLER') {
                if ($i->actionFlag == true) {
                    $messageArray = array('type'=>'system', 'state'=>'NIGHT', 'message'=>'setResultOfFortuneteller', 'id'=>$i->id, 'selectionId'=>$i->selectionId);
                    sendMessage($messageArray, $socket);
                }
            }
            else if ($i->position == 'THIEF') {
                if ($i->actionFlag == true) {
                    $messageArray = array('type'=>'system', 'state'=>'NIGHT', 'message'=>'setResultOfThief', 'id'=>$i->id, 'selectionId'=>$i->selectionId);
                    sendMessage($messageArray, $socket);
                }
            }
        }
        $messageArray = array('type'=>'system', 'state'=>'NIGHT', 'message'=>'display');
        sendMessage($messageArray, $socket);
    }


    ////Daytime////
    //「話し合い延長」がクリックされた
    public function clickExtension($messageArray) {
        outputLog('ENTER: clickExtension');
        $this->endingTime = new DateTime('+1 minutes');
        $remainingTime = $this->getRemaingTime();
        //参加者に通知
        foreach ($this->playerArray as $i) {
            $messageArray = array('type'=>'system', 'state'=>'DAYTIME', 'message'=>'setRemainingTime', 'time'=>$remainingTime);
            sendMessage($messageArray, $i->socket);
        }
        foreach ($this->spectatorArray as $i) {
                $messageArray = array('type'=>'system', 'state'=>'DAYTIME', 'message'=>'setRemainingTime', 'time'=>$remainingTime);
                sendMessage($messageArray, $i->socket);
        }
    }

    //「話し合い終了」がクリックされた
    public function clickTalksEnd($messageArray) {
        outputLog('ENTER: clickTalksEnd');
        $id = $messageArray->id;
        $player = $this->getPlayer($id);
        if ($player !== null) {
            $player->talksEndFlag = true;
            //参加者に通知
            foreach ($this->playerArray as $i) {
                $messageArray = array('type'=>'system', 'state'=>'DAYTIME', 'message'=>'setTalksEnd', 'id'=>$id);
                sendMessage($messageArray, $i->socket);
            }
            foreach ($this->spectatorArray as $i) {
                $messageArray = array('type'=>'system', 'state'=>'DAYTIME', 'message'=>'setTalksEnd', 'id'=>$id);
                sendMessage($messageArray, $i->socket);
            }

            $sum = 0;
            foreach ($this->playerArray as $i) {
                if ($i->talksEndFlag == true) {
                    $sum++;
                }
            }
            if ($sum == count($this->playerArray)) {
                $this->state = 'SELECTION';
                foreach ($this->playerArray as $i) {
                    $this->goToSelectionFromDaytime($i->socket, 'PLAYER', $i->id);
                }
                foreach ($this->spectatorArray as $i) {
                    $this->goToSelectionFromDaytime($i->socket, 'SPECTATOR', $i->id);
                }
            }
        }
    }

    //「ゲーム終了」がクリックされた
    public function clickExitInDaytime($messageArray) {
        outputLog('ENTER: clickExitInDaytime');
        $id = $messageArray->id;
        $player = $this->getPlayer($id);
        if ($player !== null) {
            $this->initWaiting();
            foreach ($this->playerArray as $i) {
                $this->goToWaitingFromDaytime($i->socket, 'PLAYER', $i->id, $player->name);
            }
            foreach ($this->spectatorArray as $i) {
                $this->goToWaitingFromDaytime($i->socket, 'SPECTATOR', $i->id, $player->name);
            }
        }
    }

    //吊る人選択画面に遷移
    public function goToSelectionFromDaytime($socket, $attribute, $id) {
        outputLog('ENTER: goToSelectionFromDaytime, attribute: '. $attribute. ', id: '. $id);
        $this->displaySelection($socket, $attribute, $id);
    }

    //待機画面に遷移
    public function goToWaitingFromDaytime($socket, $attribute, $id, $name) {
        outputLog('ENTER: goToWaitingFromDaytime, attribute: '. $attribute. ', id: '. $id. ', name: '. $name);
        $this->displayWaitingByCessation($socket, $attribute, $id, $name);
    }

    //昼の画面を表示
    public function displayDaytime($socket, $attribute, $id) {
        outputLog('ENTER: displayDaytime, attribute: '. $attribute. ', id: '. $id);
        switch ($attribute) {
            case 'PLAYER':
                $player = $this->getPlayer($id);
                $messageArray = array('type'=>'system', 'state'=>'DAYTIME', 'message'=>'init', 'villageId'=>$this->id, 'attribute'=>$attribute, 'id'=>$id, 'position'=>$player->position);
                break;
            case 'SPECTATOR':
                $messageArray = array('type'=>'system', 'state'=>'DAYTIME', 'message'=>'init', 'villageId'=>$this->id, 'attribute'=>$attribute, 'id'=>$id);
                break;
        }
        sendMessage($messageArray, $socket);
        foreach ($this->playerArray as $i) {
            $messageArray = array('type'=>'system', 'state'=>'DAYTIME', 'message'=>'setPlayer', 'id'=>$i->id, 'name'=>$i->name);
            sendMessage($messageArray, $socket);
        }
        foreach ($this->spectatorArray as $i) {
            $messageArray = array('type'=>'system', 'state'=>'DAYTIME', 'message'=>'setSpectator', 'id'=>$i->id, 'name'=>$i->name);
            sendMessage($messageArray, $socket);
        }
        global $positionArray;
        foreach ($positionArray as $i) {
            $messageArray = array('type'=>'system', 'state'=>'DAYTIME', 'message'=>'setNumberOfPosition', 'position'=>$i, 'number'=>$this->numberOfPositionArray[$i]);
            sendMessage($messageArray, $socket);
        }
        $remainingTime = $this->getRemaingTime();
        $messageArray = array('type'=>'system', 'state'=>'DAYTIME', 'message'=>'setRemainingTime', 'time'=>$remainingTime);
        sendMessage($messageArray, $socket);
        foreach ($this->playerArray as $i) {
            if ($i->talksEndFlag == true) {
                $messageArray = array('type'=>'system', 'state'=>'DAYTIME', 'message'=>'talksEnd', 'id'=>$i->id);
                sendMessage($messageArray, $socket);
            }
        }
        switch ($attribute) {
            case 'PLAYER':
                    $this->sendResultOfAction($socket, $id, 'DAYTIME');
                break;
            case 'SPECTATOR':
                foreach ($this->playerArray as $i) {
                    $messageArray = array('type'=>'system', 'state'=>'DAYTIME', 'message'=>'setPositionOfPlayer', 'id'=>$i->id, 'position'=>$i->position);
                    sendMessage($messageArray, $socket);
                    if ($i->position == 'FORTUNETELLER') {
                        $messageArray = array('type'=>'system', 'state'=>'DAYTIME', 'message'=>'setResultOfFortuneteller', 'id'=>$i->id, 'selectionId'=>$i->selectionId);
                        sendMessage($messageArray, $socket);
                    }
                    else if ($i->position == 'THIEF') {
                        $messageArray = array('type'=>'system', 'state'=>'DAYTIME', 'message'=>'setResultOfThief', 'id'=>$i->id, 'selectionId'=>$i->selectionId);
                        sendMessage($messageArray, $socket);
                    }
                }
                break;
        }
        $messageArray = array('type'=>'system', 'state'=>'DAYTIME', 'message'=>'display');
        sendMessage($messageArray, $socket);
    }


    ////Selection////
    //「吊る」がクリックされた
    public function clickExecution($messageArray) {
        outputLog('ENTER: clickExecution');
        $id = $messageArray->id;
        $hangingId = $messageArray->hangingId;
        $player = $this->getPlayer($id);
        if ($player !== null) {
            $player->executionFlag = true;
            $player->hangingId = $hangingId;
            $hangingPlayer = $this->getPlayer($hangingId);
            if ($hangingPlayer !== null) {
                $hangingPlayer->hangingNumber++;
            }

            $sum = 0;
            foreach ($this->playerArray as $i) {
                if ($i->executionFlag == true) {
                    $sum++;
                }
            }
            if ($sum == count($this->playerArray)) {
                $this->judgeWinner();
                foreach ($this->playerArray as $i) {
                    $i->winnerOrLoser = $this->isWinner($i->position, $this->winnerSide);
                    $i->earningPoint = $this->getPoint($i->position, $this->winnerSide);
                    $i->point += $i->earningPoint;
                }
                $this->state = 'RESULT';
                foreach ($this->playerArray as $i) {
                    $this->goToExecutionFromSelection($i->socket, 'PLAYER', $i->id);
                }
                foreach ($this->spectatorArray as $i) {
                    $this->goToExecutionFromSelection($i->socket, 'SPECTATOR', $i->id);
                }
            }
        }
    }

    //「ゲーム終了」がクリックされた
    public function clickExitInSelection($messageArray) {
        outputLog('ENTER: clickExitInSelection');
        $id = $messageArray->id;
        $player = $this->getPlayer($id);
        if ($player !== null) {
            $this->initWaiting();
            foreach ($this->playerArray as $i) {
                $this->goToWaitingFromSelection($i->socket, 'PLAYER', $i->id, $player->name);
            }
            foreach ($this->spectatorArray as $i) {
                $this->goToWaitingFromSelection($i->socket, 'SPECTATOR', $i->id, $player->name);
            }
        }
    }

    //処刑画面に遷移
    public function goToExecutionFromSelection($socket, $attribute, $id) {
        outputLog('ENTER: goToExecutionFromSelection, attribute: '. $attribute. ', id: '. $id);
        $this->displayExecution($socket, $attribute, $id);
    }

    //待機画面に遷移
    public function goToWaitingFromSelection($socket, $attribute, $id, $name) {
        outputLog('ENTER: goToWaitingFromSelection, attribute: '. $attribute. ', id: '. $id. ', name: '. $name);
        $this->displayWaitingByCessation($socket, $attribute, $id, $name);
    }

    //吊る人選択画面を表示
    public function displaySelection($socket, $attribute, $id) {
        outputLog('ENTER: displaySelection, attribute: '. $attribute. ', id: '. $id);
        $messageArray = array('type'=>'system', 'state'=>'SELECTION', 'message'=>'init', 'villageId'=>$this->id, 'attribute'=>$attribute, 'id'=>$id);
        sendMessage($messageArray, $socket);
        if ($attribute == 'PLAYER') {
            foreach ($this->playerArray as $i) {
                if ($i->id != $id) {
                    $messageArray = array('type'=>'system', 'state'=>'SELECTION', 'message'=>'setPlayer', 'id'=>$i->id, 'name'=>$i->name);
                    sendMessage($messageArray, $socket);
                }
            }
        }
        $messageArray = array('type'=>'system', 'state'=>'SELECTION', 'message'=>'display');
        sendMessage($messageArray, $socket);
    }

    //勝者を判定
    public function judgeWinner() {
        outputLog('ENTER: judgeWinner');
        //怪盗が交換した後の役職に設定
        foreach ($this->resultOfThiefArray as $i) {
            if ($i['selectionId'] != -1) {
                $thief = $this->getPlayer($i['id']);
                $selection = $this->getPlayer($i['selectionId']);
                $swap = $selection->position;
                $selection->position = $thief->position;
                $thief->position = $swap;
            }
        }
        //最も多く指名されたプレイヤーを抜き出す
        $max = 0;
        foreach ($this->playerArray as $i) {
            if ($i->hangingNumber > $max) {
                $max = $i->hangingNumber;
            }
        }
        $this->hangingPlayerArray = array();
        foreach ($this->playerArray as $i) {
            if ($i->hangingNumber == $max) {
                $this->hangingPlayerArray[] = $i;
            }
        }
        //maxは1？
        if ($max == 1) {
            //プレイヤーの中に人狼はいる？
            $flag = false;
            foreach ($this->playerArray as $i) {
                if ($i->position == 'WEREWOLF') {
                    $flag = true;
                    break;
                }
            }
            //いる場合は人狼サイドの勝利
            if ($flag == true) {
                $this->winnerSide = 'WEREWOLF';
            }
            //いない場合は村人サイドの勝利
            else {
                $this->winnerSide = 'PEACE';
            }
            $this->hangingPlayerArray = array();
        }
        else {
            //最も多く指名されたプレイヤーの中にてるてるはいる？
            $flag = false;
            foreach ($this->hangingPlayerArray as $i) {
                if ($i->position == 'HANGING') {
                    $flag = true;
                    break;
                }
            }
            //いる場合はてるてるサイドの勝利
            if ($flag == true) {
                $this->winnerSide = 'HANGING';
            }
            else {
                //最も多く指名されたプレイヤーの中に人狼はいる？
                $flag = false;
                foreach ($this->hangingPlayerArray as $i) {
                    if ($i->position == 'WEREWOLF') {
                        $flag = true;
                        break;
                    }
                }
                //いる場合は村人サイドの勝利
                if ($flag == true) {
                    $this->winnerSide = 'VILLAGER';
                }
                //いない場合は人狼サイドの勝利
                else {
                    $this->winnerSide = 'WEREWOLF';
                }
            }
        }
    }

    //役職が勝者サイドか
    public function isWinner($position, $winnerSide) {
        outputLog('ENTER: isWinner, position: '. $position. ', winnerSide: '. $winnerSide);
        $flag = false;
        switch ($position) {
            case 'VILLAGER':
            case 'FORTUNETELLER':
            case 'THIEF':
                if (($winnerSide == 'VILLAGER') || ($winnerSide == 'PEACE')) {
                    $flag = true;
                }
                break;
            case 'WEREWOLF':
            case 'MADMAN':
                if ($winnerSide == 'WEREWOLF') {
                    $flag = true;
                }
                break;
            case 'HANGING':
                if ($winnerSide == 'HANGING') {
                    $flag = true;
                }
                break;
        }
        return $flag;
    }

    //ポイントを計算
    public function getPoint($position, $winnerSide) {
        outputLog('ENTER: getPoint, position: '. $position. ', winnerSide: '. $winnerSide);
        $point = 0;
        switch ($winnerSide) {
            case 'VILLAGER':
            case 'PEACE':
                switch ($position) {
                    case 'FORTUNETELLER':
                        $point = 2;
                        break;
                    case 'VILLAGER':
                    case 'THIEF':
                        $point = 1;
                        break;
                    case 'WEREWOLF':
                    case 'MADMAN':
                    case 'HANGING':
                        $point = 0;
                        break;
                }
                break;
            case 'WEREWOLF':
                switch ($position) {
                    case 'WEREWOLF':
                    case 'MADMAN':
                        $point = 2;
                        break;
                    case 'VILLAGER':
                    case 'THIEF':
                    case 'HANGING':
                        $point = 0;
                        break;
                    case 'FORTUNETELLER':
                        $point = -1;
                        break;
                }
                break;
            case 'HANGING':
                switch ($position) {
                    case 'HANGING':
                        $point = 3;
                        break;
                    case 'VILLAGER':
                    case 'WEREWOLF':
                    case 'FORTUNETELLER':
                    case 'THIEF':
                    case 'MADMAN':
                        $point = 0;
                        break;
                }
                break;
        }
        return $point;
    }


    ////Execution////
    //「結果発表へ」がクリックされた
    public function clickResult($socket, $messageArray) {
        outputLog('ENTER: clickResult');
        $id = $messageArray->id;
        $attribute = $messageArray->attribute;
        switch ($attribute) {
            case 'PLAYER':
                $player = $this->getPlayer($id);
                if ($player !== null) {
                    $player->resultFlag = true;
                }
                break;
            case 'SPECTATOR':
                $spectator = $this->getSpectator($id);
                if ($spectator !== null) {
                    $spectator->resultFlag = true;
                }
                break;
        }
        $this->goToResultFromExecution($socket, $attribute, $id);
    }

    //結果発表画面に遷移
    public function goToResultFromExecution($socket, $attribute, $id) {
        outputLog('ENTER: goToResultFromExecution, attribute: '. $attribute. ', id: '. $id);
        $this->displayResult($socket, $attribute, $id);
    }

    //処刑画面を表示
    public function displayExecution($socket, $attribute, $id) {
        outputLog('ENTER: displayExecution, attribute: '. $attribute. ', id: '. $id);
        $messageArray = array('type'=>'system', 'state'=>'EXECUTION', 'message'=>'init', 'villageId'=>$this->id, 'attribute'=>$attribute, 'id'=>$id);
        sendMessage($messageArray, $socket);
        foreach ($this->hangingPlayerArray as $i) {
            $messageArray = array('type'=>'system', 'state'=>'EXECUTION', 'message'=>'setHanging', 'id'=>$i->id, 'name'=>$i->name);
            sendMessage($messageArray, $socket);
        }
        foreach ($this->playerArray as $i) {
            $messageArray = array('type'=>'system', 'state'=>'EXECUTION', 'message'=>'setPlayer', 'id'=>$i->id, 'name'=>$i->name, 'hangingId'=>$i->hangingId);
            sendMessage($messageArray, $socket);
        }
        $messageArray = array('type'=>'system', 'state'=>'EXECUTION', 'message'=>'display');
        sendMessage($messageArray, $socket);
    }


    ////Result////
    //「次の夜へ」がクリックされた
    public function clickNextNight() {
        outputLog('ENTER: clickNextNight');
        $this->id = $this->villageManagement->getCurrentId();
        $this->resultOfFortunetellerArray = array();
        $this->resultOfThiefArray = array();
        $this->startGame();
        foreach ($this->playerArray as $i) {
            $i->actionFlag = false;
            $i->talksStartFlag = false;
            $i->talksEndFlag = false;
            $i->executionFlag = false;
            $i->resultFlag = false;
            $i->hangingNumber = 0;
            $this->goToActionFromResult($i->socket, $i->id);
        }
        foreach ($this->spectatorArray as $i) {
            $i->resultFlag = false;
            $this->goToNightFromResult($i->socket);
        }
    }

    //「終了」がクリックされた
    public function clickExitInResult() {
        outputLog('ENTER: clickExitInResult');
        $this->initWaiting();
        foreach ($this->playerArray as $i) {
            $this->goToWaitingFromResult($i->socket, 'PLAYER', $i->id);
        }
        foreach ($this->spectatorArray as $i) {
            $this->goToWaitingFromResult($i->socket, 'SPECTATOR', $i->id);
        }
    }

    //行動画面に遷移
    public function goToActionFromResult($socket, $id) {
        outputLog('ENTER: goToActionFromResult, id: '. $id);
        $this->displayAction($socket, $id);
    }

    //夜の画面に遷移
    public function goToNightFromResult($socket) {
        outputLog('ENTER: goToNightFromResult');
        $this->displayNight($socket);
    }

    //待機画面に遷移
    public function goToWaitingFromResult($socket, $attribute, $id) {
        outputLog('ENTER: goToWaitingFromResult, attribute: '. $attribute. ', id: '. $id);
        $this->displayWaiting($socket, $attribute, $id);
    }

    //結果発表画面を表示
    public function displayResult($socket, $attribute, $id) {
        outputLog('ENTER: displayResult, attribute: '. $attribute. ', id: '. $id);
        $messageArray = array('type'=>'system', 'state'=>'RESULT', 'message'=>'init', 'villageId'=>$this->id, 'attribute'=>$attribute, 'id'=>$id, 'side'=>$this->winnerSide);
        sendMessage($messageArray, $socket);
        if ($attribute == 'PLAYER') {
            $player = $this->getPlayer($id);
            $messageArray = array('type'=>'system', 'state'=>'RESULT', 'message'=>'setWinnerOrLoser', 'winnerOrLoser'=>$player->winnerOrLoser);
            sendMessage($messageArray, $socket);
        }
        foreach ($this->playerArray as $i) {
            $messageArray = array('type'=>'system', 'state'=>'RESULT', 'message'=>'setResultOfPlayer', 'id'=>$i->id, 'name'=>$i->name, 'position'=>$i->position, 'hangingId'=>$i->hangingId, 'point'=>$i->point, 'earningPoint'=>$i->earningPoint);
            sendMessage($messageArray, $socket);
        }
        foreach ($this->resultOfFortunetellerArray as $i) {
            $messageArray = array('type'=>'system', 'state'=>'RESULT', 'message'=>'setResultOfFortuneteller', 'id'=>$i['id'], 'selectionId'=>$i['selectionId']);
            sendMessage($messageArray, $socket);
        }
        foreach ($this->resultOfThiefArray as $i) {
            $messageArray = array('type'=>'system', 'state'=>'RESULT', 'message'=>'setResultOfThief', 'id'=>$i['id'], 'selectionId'=>$i['selectionId']);
            sendMessage($messageArray, $socket);
        }
        $messageArray = array('type'=>'system', 'state'=>'RESULT', 'message'=>'display');
        sendMessage($messageArray, $socket);
    }
}
?>
