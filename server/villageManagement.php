<?php
class VillageManagement {
    private $villageArray = array();
    private $currentId;
    //public $socketInLobbyArray = array();

    //コンストラクタ
    function __construct() {
        outputLog('ENTER: construct of VillageManagement');
        $villageArray = array();

    }

    //村配列を取得
    public function getVillageArray() {
        return $this->villageArray;
    }

    //村を取得
    public function getVillage($villageId) {
        foreach ($this->villageArray as $i) {
            $id = $i->getId();
            if ($id == $villageId) {
                return $i;
            }
        }

        return null;
    }

    //村を削除
    public function removeVillage($villageId) {
        outputLog('ENTER: removeVillage, villageId: '. $villageId);
        $village = $this->getVillage($villageId);
        $foundVillage = array_search($village, $this->villageArray);
        unset($this->villageArray[$foundVillage]);
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
            if ($i->getName() == $name) {
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
            $village->addParticipant($socket);
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
                $village->addParticipant($socket);
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
            if (($i->getState() == 'PARTICIPATION') || ($i->getState() == 'WAITING')) {
                $flag = true;
                $passwordFlag = false;
                if ($i->getPassword() !== '') {
                    $passwordFlag = true;
                }
                $messageArray = array('type'=>'system', 'state'=>'LOBBY', 'message'=>'add', 'villageId'=>$i->getId(), 'villageName'=>$i->getName(), 'passwordFlag'=>$passwordFlag);
                sendMessage($messageArray, $socket);
            }
        }
        if ($flag == false) {
                $messageArray = array('type'=>'system', 'state'=>'LOBBY', 'message'=>'notExist');
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
            if ($village->removeParticipant($socket) !== false) {
                if ($village->getNumberOfParticipant() <= 0) {
                    $this->removeVillage($village->getId());
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
            if ($village->removeParticipant($socket) !== false) {
                if ($village->getNumberOfParticipant() <= 0) {
                    $this->removeVillage($village->getId());
                }
                else {
                    //他の参加者に通知
                    foreach ($village->getPlayerArray() as $i) {
                        $messageArray = array('type'=>'system', 'state'=>'WAITING', 'message'=>'del', 'attribute'=>$attribute, 'id'=>$id);
                        sendMessage($messageArray, $i->socket);
                    }
                    foreach ($village->getSpectatorArray() as $i) {
                        $messageArray = array('type'=>'system', 'state'=>'WAITING', 'message'=>'del', 'attribute'=>$attribute, 'id'=>$id);
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
                            $village->removeParticipant($i->socket);
                            $village->addParticipant($socket);
                            $i->socket = $socket;
                            $flag = true;
                            break;
                        }
                    }
                    break;
                case 'SPECTATOR':
                    foreach ($village->getSpectatorArray() as $i) {
                        if ($i->id == $id) {
                            $village->removeParticipant($i->socket);
                            $village->addParticipant($socket);
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
                    case 'SELECTION':
                        $this->goToSelectionFromConnection($socket, $villageId, $attribute, $id);
                        break;
                    case 'RESULT':
                        switch ($attribute) {
                            case 'PLAYER':
                                foreach ($village->getPlayerArray() as $i) {
                                    if ($i->id == $id) {
                                        if ($i->resultFlag == true) {
                                            $this->goToResultFromConnection($socket, $villageId, $attribute, $id);
                                        }
                                        else {
                                            $this->goToExecutionFromConnection($socket, $villageId, $attribute, $id);
                                        }
                                        break;
                                    }
                                }
                                break;
                            case 'SPECTATOR':
                                foreach ($village->getSpectatorArray() as $i) {
                                    if ($i->id == $id) {
                                        if ($i->resultFlag == true) {
                                            $this->goToResultFromConnection($socket, $villageId, $attribute, $id);
                                        }
                                        else {
                                            $this->goToExecutionFromConnection($socket, $villageId, $attribute, $id);
                                        }
                                        break;
                                    }
                                }
                                break;
                        }
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
            if ($i->getId() == $villageId) {
                $i->displayWaiting($socket, $attribute, $id);
            }
        }
    }

    //socketを行動画面に遷移
    public function goToActionFromConnection($socket, $villageId, $id) {
        outputLog('ENTER: goToActionFromConnection, villageId: '. $villageId. ', id: '. $id);
        foreach ($this->villageArray as $i) {
            if ($i->getId() == $villageId) {
                $i->displayAction($socket, $id);
            }
        }
    }

    //socketを通知画面に遷移
    public function goToNotificationFromConnection($socket, $villageId, $id) {
        outputLog('ENTER: goToNotificationFromConnection, villageId: '. $villageId. ', id: '. $id);
        foreach ($this->villageArray as $i) {
            if ($i->getId() == $villageId) {
                $i->displayNotification($socket, $id);
            }
        }
    }

    //socketを夜の画面に遷移
    public function goToNightFromConnection($socket, $villageId, $id) {
        outputLog('ENTER: goToNightFromConnection, villageId: '. $villageId. ', id: '. $id);
        foreach ($this->villageArray as $i) {
            if ($i->getId() == $villageId) {
                $i->displayNight($socket);
            }
        }
    }

    //socketを昼の画面に遷移
    public function goToDaytimeFromConnection($socket, $villageId, $attribute, $id) {
        outputLog('ENTER: goToDaytimeFromConnection, villageId: '. $villageId. ', attribute: '. $attribute. ', id: '. $id);
        foreach ($this->villageArray as $i) {
            if ($i->getId() == $villageId) {
                $i->displayDaytime($socket, $attribute, $id);
            }
        }
    }

    //socketを吊る人選択画面に遷移
    public function goToSelectionFromConnection($socket, $villageId, $attribute, $id) {
        outputLog('ENTER: goToSelectionFromConnection, villageId: '. $villageId. ', attribute: '. $attribute. ', id: '. $id);
        foreach ($this->villageArray as $i) {
            if ($i->getId() == $villageId) {
                $i->displaySelection($socket, $attribute, $id);
            }
        }
    }

    //socketを処刑画面に遷移
    public function goToExecutionFromConnection($socket, $villageId, $attribute, $id) {
        outputLog('ENTER: goToExecutionFromConnection, villageId: '. $villageId. ', attribute: '. $attribute. ', id: '. $id);
        foreach ($this->villageArray as $i) {
            if ($i->getId() == $villageId) {
                $i->displayExecution($socket, $attribute, $id);
            }
        }
    }

    //socketを結果発表画面に遷移
    public function goToResultFromConnection($socket, $villageId, $attribute, $id) {
        outputLog('ENTER: goToResultFromConnection, villageId: '. $villageId. ', attribute: '. $attribute. ', id: '. $id);
        foreach ($this->villageArray as $i) {
            if ($i->getId() == $villageId) {
                $i->displayResult($socket, $attribute, $id);
            }
        }
    }
}
?>
