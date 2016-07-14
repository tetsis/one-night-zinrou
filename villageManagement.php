<?php
class VillageManagement {
    public $villageArray = array();
    public $currentId;
    //public $socketInLobbyArray = array();

    //コンストラクタ
    function __construct() {
        outputLog('ENTER: construct of VillageManagement');
        $villageArray = array();

    }

    //村情報を取得
    public function getVillage($villageId) {
        foreach ($this->villageArray as $i) {
            $id = $i->id;
            if ($id == $villageId) {
                return $i;
            }
        }

        return null;
    }

    //現在の村IDを取得する関数
    public function getCurrentId() {
        $fp = fopen('zinrou.conf', 'r');
        if ($fp) {
            if (flock($fp, LOCK_SH)) {
                $str = fgets($fp);
                $this->currentId = intval($str);
                flock($fp, LOCK_UN);
            }
            else {
                outputLog('ERROR: Cannot Read zinrou.conf');
            }
        }
        fclose($fp);
        $this->currentId++;
        $fp = fopen('zinrou.conf', 'w');
        if ($fp) {
            if (flock($fp, LOCK_EX)) {
                fputs($fp, $this->currentId);
                flock($fp, LOCK_UN);
            }
            else {
                outputLog('ERROR: Cannot Write zinrou.conf');
            }
        }
        fclose($fp);

        return $this->currentId;
    }


    ////Top////
    //socketで「村を作成する」をクリック
    public function clickMaking($socket) {
        outputLog('ENTER: clickMaking');
        $this->goToMakingFromTop($socket);
    }

    //socketで「村に参加する」をクリック
    public function clickLobby($socket) {
        outputLog('ENTER: clickLobby');
        $this->goToLobbyFromTop($socket);
    }

    //socketを村作成画面に遷移
    public function goToMakingFromTop($socket) {
        outputLog('ENTER: goToMakingFromTop');
        $messageArray = array('type'=>'system', 'state'=>'MAKING', 'message'=>'display');
        sendMessage($messageArray, $socket);
    }

    //socketをロビー画面に遷移
    public function goToLobbyFromTop($socket) {
        outputLog('ENTER: goToLobbyFromTop');
        $messageArray = array('type'=>'system', 'state'=>'LOBBY', 'message'=>'display');
        sendMessage($messageArray, $socket);
        $this->updateVillageList($socket);
    }

    //socketにトップ画面を表示
    public function displayTop($socket) {
        outputLog('ENTER: displayTop');
        $messageArray = array('type'=>'system', 'state'=>'TOP', 'message'=>'display');
        sendMessage($messageArray, $socket);
    }


    ////Making////
    //socketで「決定」をクリック
    public function clickDecideInMaking($socket, $messageArray) {
        outputLog('ENTER: clickDecideInMaking');
        $name = $messageArray->name;
        $password = $messageArray->password;
        $spectatorFlag = $messageArray->spectatorFlag;

        $flag = false;
        foreach ($this->villageArray as $i) {
            if ($i->name == $name) {
                $messageArray = array('type'=>'system', 'state'=>'MAKING', 'message'=>'reject');
                sendMessage($messageArray, $socket);
                $flag = true;
                break;
            }
        }
        if ($flag == false) {
            $id = $this->getCurrentId();
            //村を作成する
            $village = new Village($this, $id, $name, $password, $spectatorFlag);
            $village->addParticipantArray($socket);
            $this->villageArray[] = $village;
            $this->goToParticipationFromMaking($socket, $id, $name, $spectatorFlag);
        }
    }

    //socketで「戻る」をクリック
    public function clickBackInMaking($socket) {
        outputLog('ENTER: clickBackInMaking');
        $this->goToTopFromMaking($socket);
    }

    //socketを村参加画面に遷移
    public function goToParticipationFromMaking($socket, $villageId, $villageName, $spectatorFlag) {
        outputLog('ENTER: goToParticipationFromMaking, villageId: '. $villageId. ', villageName: '. $villageName. ', spectatorFlag: '. $spectatorFlag);
        $village = $this->getVillage($villageId);
        $village->displayParticipation($socket, $villageId, $villageName, $spectatorFlag);
    }

    //socketをトップ画面に遷移
    public function goToTopFromMaking($socket) {
        outputLog('ENTER: goToTopFromMaking');
        $this->displayTop($socket);
    }


    ////Lobby////
    //socketで「更新」をクリック
    public function clickUpdate($socket) {
        outputLog('ENTER: clickUpdate');
        $this->updateVillageList($socket);
    }

    //socketで「決定」をクリック
    public function clickDecideInLobby($socket, $messageArray) {
        outputLog('ENTER: clickDecideInLobby');
        $villageId = $messageArray->villageId;
        $password = $messageArray->password;
        $village = $this->getVillage($villageId);
        if ($village !== null) {
            $correctFlag = true;
            $villagePassword = $village->getPassword();
            if ($villagePassword != '') {
                if ($villagePassword !== $password) {
                    $correctFlag = false;
                }
            }
            if ($correctFlag == true) {
                $village->addParticipantArray($socket);
                $this->goToParticipationFromLobby($socket, $villageId, $village->getName(), $village->getSpectatorFlag());
            }
            else {
                $messageArray = array('type'=>'system', 'state'=>'LOBBY', 'message'=>'reject');
                sendMessage($messageArray, $socket);
            }
        }
        else {
                $messageArray = array('type'=>'system', 'state'=>'LOBBY', 'message'=>'delete');
                sendMessage($messageArray, $socket);
        }
    }

    //socketで「戻る」をクリック
    public function clickBackInLobby($socket) {
        outputLog('ENTER: clickBackInLobby');
        $this->goToTopFromLobby($socket);
    }

    //socketを村参加画面に遷移
    public function goToParticipationFromLobby($socket, $villageId, $villageName, $spectatorFlag) {
        outputLog('ENTER: goToParticipationFromLobby, villageId: '. $villageId. ', villageName: '. $villageName. ', spectatorFlag: '. $spectatorFlag);
        $village = $this->getVillage($villageId);
        $village->displayParticipation($socket, $villageId, $villageName, $spectatorFlag);
    }

    //socketをトップ画面に遷移
    public function goToTopFromLobby($socket) {
        outputLog('ENTER: goToTopFromLobby');
        $this->displayTop($socket);
    }

    //socketに村情報のリストを更新する
    public function updateVillageList($socket) {
        outputLog('ENTER: updateVillageList');
        $flag = false;
        foreach ($this->villageArray as $i) {
            if (($i->state == 'PARTICIPATION') || ($i->state == 'WAITING')) {
                $flag = true;
                $passwordFlag = false;
                if ($i->password !== '') {
                    $passwordFlag = true;
                }
                $messageArray = array('type'=>'system', 'state'=>'LOBBY', 'message'=>'add', 'villageId'=>$i->id, 'villageName'=>$i->name, 'passwordFlag'=>$passwordFlag);
                sendMessage($messageArray, $socket);
            }
        }
        if ($flag == false) {
                $messageArray = array('type'=>'system', 'state'=>'LOBBY', 'message'=>'notExit');
                sendMessage($messageArray, $socket);
        }
    }


    ////Participation////
    //socketで「戻る」をクリック
    public function clickBackInParticipation($socket, $messageArray) {
        outputLog('ENTER: clickBackInParticipation');
        $villageId = $messageArray->villageId;
        $village = $this->getVillage($villageId);
        if ($village !== null) {
            if ($village->removeParticipantArray($socket) !== false) {
                if ($village->getNumberOfParticipant() <= 0) {
                    $foundVillage = array_search($village, $this->villageArray);
                    unset($this->villageArray[$foundVillage]);
                }
            }
        }
        $this->goToTopFromParticipation($socket);
    }

    //socketをトップ画面に遷移
    public function goToTopFromParticipation($socket) {
        outputLog('ENTER: goToTopFromParticipation');
        $this->displayTop($socket);
    }


    ////Waiting////
    //socketで「戻る」をクリック
    public function clickBackInWaiting($socket, $messageArray) {
        outputLog('ENTER: clickBackInWaiting');
        $villageId = $messageArray->villageId;
        $attribute = $messageArray->attribute;
        $id = $messageArray->id;
        $village = $this->getVillage($villageId);
        if ($village !== null) {
            switch ($attribute) {
                case 'PLAYER':
                    $village->removePlayer($id);
                    break;
                case 'SPECTATOR':
                    $village->removeSpectator($id);
                    break;
            }
            if ($village->removeParticipantArray($socket) !== false) {
                if ($village->getNumberOfParticipant() <= 0) {
                    $foundVillage = array_search($village, $this->villageArray);
                    unset($this->villageArray[$foundVillage]);
                }
                else {
                    //他の参加者に通知
                    foreach ($village->getPlayerArray() as $i) {
                        $messageArray = array('type'=>'system', 'state'=>'WAITING', 'message'=>'del', 'attribute'=>'PLAYER', 'id'=>$id);
                        sendMessage($messageArray, $i->socket);
                    }
                    foreach ($village->getSpectatorArray() as $i) {
                        $messageArray = array('type'=>'system', 'state'=>'WAITING', 'message'=>'del', 'attribute'=>'SPECTATOR', 'id'=>$id);
                        sendMessage($messageArray, $i->socket);
                    }
                }
            }
        }
        $this->goToTopFromWaiting($socket);
    }

    //socketをトップ画面に遷移
    public function goToTopFromWaiting($socket) {
        outputLog('ENTER: goToTopFromWaiting');
        $this->displayTop($socket);
    }


    ////Connection////
    //socketにデータを要求
    public function queryData($socket) {
        outputLog('ENTER: queryData');
        $messageArray = array('type'=>'system', 'state'=>'CONNECTION', 'message'=>'query');
        sendMessage($messageArray, $socket);
    }

    //socketからデータの応答
    public function replyData($socket, $messageArray) {
        outputLog('ENTER: replyData');
        $villageId = $messageArray->villageId;
        $attribute = $messageArray->attribute;
        $id = $messageArray->id;

        $village = $this->getVillage($villageId);
        if ($village !== null) {
            $flag = false;
            switch ($attribute) {
                case 'PLAYER':
                    foreach ($village->getPlayerArray() as $i) {
                        if ($i->id == $id) {
                            $village->removeParticipantArray($i->socket);
                            $village->addParticipantArray($socket);
                            $i->socket = $socket;
                            $flag = true;
                            break;
                        }
                    }
                    break;
                case 'SPECTATOR':
                    foreach ($village->getSpectatorArray() as $i) {
                        if ($i->id == $id) {
                            $village->removeParticipantArray($i->socket);
                            $village->addParticipantArray($socket);
                            $i->socket = $socket;
                            $flag = true;
                            break;
                        }
                    }
                    break;
            }
            if ($flag == true) {
                switch ($village->getState()) {
                    case 'WAITING':
                        $this->goToWaitingFromConnection($socket, $villageId, $attribute, $id);
                        break;
                    case 'NIGHT':
                        switch ($attribute) {
                            case 'PLAYER':
                                foreach ($village->getPlayerArray() as $i) {
                                    if ($i->id == $id) {
                                        if ($i->actionFlag == true) {
                                            $this->goToNotificationFromConnection($socket, $villageId, $id);
                                        }
                                        else {
                                            $this->goToActionFromConnection($socket, $villageId, $id);
                                        }
                                        break;
                                    }
                                }
                                break;
                            case 'SPECTATOR':
                                $this->goToNightFromConnection($socket, $villageId, $id);
                                break;
                        }
                        break;
                    case 'DAYTIME':
                        $this->goToDaytimeFromConnection($socket, $villageId, $attribute, $id);
                        break;
                    case 'EXECUTION':
                        $this->goToExecutionFromConnection($socket, $villageId, $attribute, $id);
                        break;
                    case 'RESULT':
                        $this->goToResultFromConnection($socket, $villageId, $attribute, $id);
                        break;
                }
            }
            else {
                $this->goToTopFromConnection($socket);
            }
        }
        else {
            $this->goToTopFromConnection($socket);
        }
    }

    //socketのデータはない
    public function noneData($socket) {
        outputLog('ENTER: noneData');
        $this->goToTopFromConnection($socket);
    }

    //データを消去
    public function deleteData($socket) {
        outputLog('ENTER: deleteData');
        $messageArray = array('type'=>'system', 'state'=>'CONNECTION', 'message'=>'delete');
        sendMessage($messageArray, $socket);
    }

    //socketをトップ画面に遷移
    public function goToTopFromConnection($socket) {
        outputLog('ENTER: goToTopFromConnection');
        $this->deleteData($socket);
        $this->displayTop($socket);
    }

    //socketを待機画面に遷移
    public function goToWaitingFromConnection($socket, $villageId, $attribute, $id) {
        outputLog('ENTER: goToWaitingFromConnection, villageId: '. $villageId. ', attribute: '. $attribute. ', id: '. $id);
        foreach ($this->villageArray as $i) {
            if ($i->id == $villageId) {
                $i->displayWaiting($socket, $attribute, $id);
            }
        }
    }

    //socketを行動画面に遷移
    public function goToActionFromConnection($socket, $villageId, $id) {
        outputLog('ENTER: goToActionFromConnection, villageId: '. $villageId. ', id: '. $id);
        foreach ($this->villageArray as $i) {
            if ($i->id == $villageId) {
                $i->displayAction($socket, $id);
            }
        }
    }

    //socketを通知画面に遷移
    public function goToNotificationFromConnection($socket, $villageId, $id) {
        outputLog('ENTER: goToNotificationFromConnection, villageId: '. $villageId. ', id: '. $id);
        foreach ($this->villageArray as $i) {
            if ($i->id == $villageId) {
                $i->displayNotification($socket, $id);
            }
        }
    }

    //socketを夜の画面に遷移
    public function goToNightFromConnection($socket, $villageId, $id) {
        outputLog('ENTER: goToNightFromConnection, villageId: '. $villageId. ', id: '. $id);
        foreach ($this->villageArray as $i) {
            if ($i->id == $villageId) {
                $i->displayNight($socket);
            }
        }
    }

    //socketを昼の画面に遷移
    public function goToDaytimeFromConnection($socket, $villageId, $attribute, $id) {
        outputLog('ENTER: goToDaytimeFromConnection, villageId: '. $villageId. ', attribute: '. $attribute. ', id: '. $id);
        foreach ($this->villageArray as $i) {
            if ($i->id == $villageId) {
                $i->displayDaytime($socket, $attribute, $id);
            }
        }
    }

    //socketを吊る人選択画面に遷移
    public function goToExecutionFromConnection($socket, $villageId, $attribute, $id) {
        outputLog('ENTER: goToExecutionFromConnection, villageId: '. $villageId. ', attribute: '. $attribute. ', id: '. $id);
        foreach ($this->villageArray as $i) {
            if ($i->id == $villageId) {
                $i->displayExecution($socket, $attribute, $id);
            }
        }
    }

    //socketを結果発表画面に遷移
    public function goToResultFromConnection($socket, $villageId, $attribute, $id) {
        outputLog('ENTER: goToResultFromConnection, villageId: '. $villageId. ', attribute: '. $attribute. ', id: '. $id);
        foreach ($this->villageArray as $i) {
            if ($i->id == $villageId) {
                $i->displayResult($socket, $attribute, $id);
            }
        }
    }
}
?>
