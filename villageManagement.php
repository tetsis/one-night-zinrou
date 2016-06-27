<?php
class VillageManagement {
    public $villageArray = array();
    public $currentId;
    //public $socketInLobbyArray = array();

    //コンストラクタ
    function __construct() {
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


    ////Top////
    //socketで「村を作成する」をクリック
    public function clickMaking($socket) {
        $this->goToMakingFromTop($socket);
    }

    //socketで「村に参加する」をクリック
    public function clickLobby($socket) {
        $this->goToLobbyFromTop($socket);
    }

    //socketを村作成画面に遷移
    public function goToMakingFromTop($socket) {
        $txData = mask(json_encode(array('type'=>'system', 'state'=>MAKING, 'message'=>'display')));
        sendMessage($txData, $socket);
    }

    //socketをロビー画面に遷移
    public function goToLobbyFromTop($socket) {
        $this->updateVillageList($socket);
        $txData = mask(json_encode(array('type'=>'system', 'state'=>LOBBY, 'message'=>'display')));
        sendMessage($txData, $socket);
    }

    //socketにトップ画面を表示
    public function displayTop($socket) {
        $txData = mask(json_encode(array('type'=>'system', 'state'=>TOP, 'message'=>'display')));
        sendMessage($txData, $socket);
    }


    ////Making////
    //socketで「決定」をクリック
    public function clickDecideInMaking($socket, $messageArray) {
        $name = $messageArray->name;
        $password = $messageArray->password;
        $spectatorFlag = $messageArray->spectatorFlag;

        $flag = false;
        foreach ($this->villageArray as $i) {
            if ($i->name == $name) {
                $txData = mask(json_encode(array('type'=>'system', 'state'=>MAKING, 'message'=>'reject')));
                sendMessage($txData, $socket);
                $flag = true;
                break;
            }
        }
        if ($flag == false) {
            $fp = fopen('zinrou.conf', 'r');
            if ($fp) {
                if (flock($fp, LOCK_SH)) {
                    $str = fgets($fp);
                    $this->currentId = intval($str);
                    flock($fp, LOCK_UN);
                }
                else {
                    echo "error: zinrou.confが読み込めません";
                }
            }
            fclose($fp);

            $this->currentId++;

            $fp = fopen('zinrou.conf', 'w');
            if ($fp) {
                if (flock($fp, LOCK_EX)) {
                    fputs($fp, "$currentId");
                    flock($fp, LOCK_UN);
                }
                else {
                    echo "error: zinrou.confに書き込みません";
                }
            }

            //村を作成する
            $village = new Village($currentId, $name, $password, $spectatorFlag);
            $village->participantArray[] = $socket;
            $village->numberOfParticipant = 1;
            $this->villageArray[] = $village;
            $this->goToParticipationFromMaking($socket, $currentId, $name, $spectatorFlag);
        }
    }

    //socketで「戻る」をクリック
    public function clickBackInMaking($socket) {
        $this->goToTopFromMaking($socket);
    }

    //socketを村参加画面に遷移
    public function goToParticipationFromMaking($socket, $villageId, $villageName, $spectatorFlag) {
        $village = $this->getVillage($villageId);
        $village->displayParticipation($socket, $villageId, $villageName, $spectatorFlag);
    }

    //socketをトップ画面に遷移
    public function goToTopFromMaking($socket) {
        $this->displayTop($socket);
    }


    ////Lobby////
    //socketで「更新」をクリック
    public function clickUpdate($socket) {
        $this->updateVillageList($socket);
    }

    //socketで「決定」をクリック
    public function clickDecideInLobby($socket, $messageArray) {
        $villageId = $messageArray->villageId;
        $password = $messageArray->password;
        $village = $this->getVillage($villageId);
        if ($village != null) {
            $correctFlag = true;
            if ($village->password != '') {
                if ($village->password !== $password) {
                    $correctFlag = false;
                }
            }
            if ($correctFlag == true) {
                $village->participantArray[] = $socket;
                $village->numberOfParticipant++;
                $village->goToParticipationFromLobby($socket, $villageId, $villageName, $spectatorFlag);
            }
            else {
                $txData = mask(json_encode(array('type'=>'system', 'state'=>LOBBY, 'message'=>'reject')));
                sendMessage($txData, $socket);
            }
        }
        else {
                $txData = mask(json_encode(array('type'=>'system', 'state'=>LOBBY, 'message'=>'delete')));
                sendMessage($txData, $socket);
        }
    }

    //socketで「戻る」をクリック
    public function clickBackInLobby($socket) {
        $this->goToTopFromLobby($socket);
    }

    //socketを村参加画面に遷移
    public function goToParticipationFromLobby($socket, $villageId, $villageName, $spectatorFlag) {
        $village = $this->getVillage($villageId);
        $village->displayParticipation($socket, $villageId, $villageName, $spectatorFlag);
    }

    //socketをトップ画面に遷移
    public function goToTopFromLobby($socket) {
        $this->displayTop($socket);
    }

    //socketに村情報のリストを更新する
    public function updateVillageList($socket) {
        foreach ($this->villageArray as $i) {
            if (($i->state == PARTICIPATION) || ($i->state == WAITING)) {
                $passwordFlag = false;
                if ($i->password !== '') {
                    $passwordFlag = true;
                }
                $txData = mask(json_encode(array('type'=>'system', 'state'=>LOBBY, 'message'=>'add', 'villageId'=>$i->id, 'villageName'=>$i->name, 'passwordFlag'=>$passwordFlag)));
                sendMessage($txData, $socket);
            }
        }
    }


    ////Participation////
    //socketで「戻る」をクリック
    public function clickBackInParticipation($socket, $messageArray) {
        $village = getVillage($villageId);
        if ($village != null) {
            $foundSocket = array_search($socket, $village->participantArray);
            if ($foundSocket != false) {
                unset($village->participantArray[$foundSocket]);
                $village->numberOfParticipant--;
                if ($village->numberOfParticipant == 0) {
                    echo "$this->villageArray";
                    $foundVillage = array_search($village, $this->villageArray);
                    unset($this->villageArray[$foundVillage]);
                    echo "$this->villageArray";
                }
            }
            $this->goToTopFromParticipation($socket);
        }
    }

    //socketをトップ画面に遷移
    public function goToTopFromParticipation($socket) {
        $this->displayTop($socket);
    }


    ////Waiting////
    //socketで「戻る」をクリック
    public function clickBackInWaiting($socket, $messageArray) {
        $villageId = $messageArray->villageId;
        $attribute = $messageArray->attribute;
        $id = $messageArray->id;
        $village = $this->getVillage($villageId);
        if ($village != null) {
            switch ($attribute) {
                case PLAYER:
                    $player = $village->getPlayer($id);
                    $foundPlayer = array_search($player, $village->playerArray);
                    if ($foundPlayer != false) {
                        unset($village->playerArray[$foundPlayer]);
                    }
                    break;
                case SPECTATOR:
                    $spectator = $village->getSpectator($id);
                    $foundSpectator = array_search($spectator, $village->spectatorArray);
                    if ($foundSpectator != false) {
                        unset($village->spectatorArray[$foundSpectator]);
                    }
                    break;
            }
            $foundSocket = array_search($socket, $village->participantArray);
            if ($foundSocket != false) {
                unset($village->participantArray[$foundSocket]);
                $village->numberOfParticipant--;
                if ($village->numberOfParticipant == 0) {
                    echo "$this->villageArray";
                    $foundVillage = array_search($village, $this->villageArray);
                    unset($this->villageArray[$foundVillage]);
                    echo "$this->villageArray";
                }
                else {
                    foreach ($village->playerArray as $i) {
                        $txData = mask(json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'del', 'attribute'=>PLAYER, 'id'=>$i->id)));
                        sendMessage($txData, $socket);
                    }
                    foreach ($village->spectatorArray as $i) {
                        $txData = mask(json_encode(array('type'=>'system', 'state'=>WAITING, 'message'=>'del', 'attribute'=>SPECTATOR, 'id'=>$i->id)));
                        sendMessage($txData, $socket);
                    }
                }
                $this->goToTopFromWaiting($socket);
            }
        }
    }

    //socketをトップ画面に遷移
    public function goToTopFromWaiting($socket) {
        $this->displayTop($socket);
    }


    ////Connection////
    //socketにデータを要求
    public function queryData($socket) {
        $txData = mask(json_encode(array('type'=>'system', 'state'=>CONNECTION, 'message'=>'query')));
        sendMessage($txData, $socket);
    }

    //socketからデータの応答
    public function replyData($socket, $messageArray) {
        $villageId = $messageArray->villageId;
        $attribute = $messageArray->attribute;
        $id = $messageArray->id;

        $village = $this->getVillage($villageId);
        if ($village != null) {
            $flag = false;
            switch ($attribute) {
                case PLAYER:
                    foreach ($village->playerArray as $i) {
                        if ($i->id == $id) {
                            $i->socket = $socket;
                            $flag = true;
                            break;
                        }
                    }
                    break;
                case SPECTATOR:
                    foreach ($village->spectatorArray as $i) {
                        if ($i->id == $id) {
                            $i->socket = $socket;
                            $flag = true;
                            break;
                        }
                    }
                    break;
            }
            if ($flag == true) {
                switch ($village->state) {
                    case WAITING:
                        $this->goToWaitingFromConnection($socket, $villageId, $attribute, $id);
                        break;
                    case NIGHT:
                        switch ($attribute) {
                            case PLAYER:
                                foreach ($village->playerArray as $i) {
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
                            case SPECTATOR:
                                $this->goToNightFromConnection($socket, $villageId, $id);
                                break;
                        }
                        break;
                    case DAYTIME:
                        $this->goToDaytimeFromConnection($socket, $villageId, $attribute, $id);
                        break;
                    case EXECUTION:
                        $this->goToExecutionFromConnection($socket, $villageId, $attribute, $id);
                        break;
                    case RESUT:
                        $this->goToResultFromConnection($socket, $villageId, $attribute);
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
        $this->goToTopFromConnection($socket);
    }

    //データを消去
    public function deleteData($socket) {
        $txData = mask(json_encode(array('type'=>'system', 'state'=>CONNECTION, 'message'=>'delete')));
        sendMessage($txData, $socket);
    }

    //socketをトップ画面に遷移
    public function goToTopFromConnection($socket) {
        $this->deleteData($socket);
        $this->displayTop();
    }

    //socketを待機画面に遷移
    public function goToWaitingFromConnection($socket, $villageId, $attribute, $id) {
        foreach ($this->villageArray as $i) {
            if ($i->id == $villageId) {
                //$i->displayWaiting();
            }
        }
    }

    //socketを行動画面に遷移
    public function goToActionFromConnection($socket, $villageId, $id) {
        foreach ($this->villageArray as $i) {
            if ($i->id == $villageId) {
                //$i->displayAction();
            }
        }
    }

    //socketを通知画面に遷移
    public function goToNotificationFromConnection($socket, $villageId, $id) {
        foreach ($this->villageArray as $i) {
            if ($i->id == $villageId) {
                //$i->displayNotification();
            }
        }
    }

    //socketを夜の画面に遷移
    public function goToNightFromConnection($socket, $villageId, $id) {
        foreach ($this->villageArray as $i) {
            if ($i->id == $villageId) {
                //$i->displayNight();
            }
        }
    }

    //socketを昼の画面に遷移
    public function goToDaytimeFromConnection($socket, $villageId, $attribute, $id) {
        foreach ($this->villageArray as $i) {
            if ($i->id == $villageId) {
                //$i->displayDaytime();
            }
        }
    }

    //socketを吊る人選択画面に遷移
    public function goToExecutionFromConnection($socket, $villageId, $attribute, $id) {
        foreach ($this->villageArray as $i) {
            if ($i->id == $villageId) {
                //$i->displayExecution();
            }
        }
    }

    //socketを結果発表画面に遷移
    public function goToResultFromConnection($socket, $villageId, $attribute) {
        foreach ($this->villageArray as $i) {
            if ($i->id == $villageId) {
                //$i->displayResult();
            }
        }
    }
}
?>
