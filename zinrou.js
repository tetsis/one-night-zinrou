var STATE = {
    'CONNECTION': 0,
    'TOP': 1,
    'MAKING': 2,
    'LOBBY': 3,
    'PARTICIPATION': 4,
    'WAITING': 5,
    'CONFIGURE': 6,
    'ACTION': 7
    'NOTIFICATION': 8
    'NIGHT': 9,
    'DAYTIME': 10,
    'EXECUTION': 11,
    'RESULT': 12
};

var POSITION = {
    'VILLAGER': 0,
    'WEREWOLF': 1,
    'FORTUNETELLER': 2,
    'THIEF': 3,
    'MADMAN': 4,
    'HANGING': 5
};

var ATTRIBUTE = {
    'PLAYER': 0,
    'SPECTATOR': 1
};

var state;
var spectatorFlag;
var selectedVillageId;
var selectedVillagePasswordFlag;
var villageId;
var villageName;
var attribute;
var id;
var position;
var playerArray;
var resultOfFortunetellerArray;
var resultOfThiefArray;
var numberOfPlayer;
var numberOfPositionArray;
var talkingTime;
var remaingTime;
var selectionId;
var selectionName;
var selectionPosition;
var buddyNameArray;
var positionArray;

playerArray = [];
resultOfFortunetellerArray = [];
resultOfThiefArray = [];
numberOfPositionArray = [];
buddyNameArray = [];
positionArray = [
    POSITION.VILLAGER,
    POSITION.WEREWOLF,
    POSITION.FORTUNETELLER,
    POSITION.THIEF,
    POSITION.MADMAN,
    POSITION.HANGING
];

//汎用関数
//stateの画面を表示
function displayState(state) {
    document.getElementById('top').style.display = 'none';
    document.getElementById('making').style.display = 'none';
    document.getElementById('lobby').style.display = 'none';
    document.getElementById('participation').style.display = 'none';
    document.getElementById('waiting').style.display = 'none';
    document.getElementById('action').style.display = 'none';
    document.getElementById('notification').style.display = 'none';
    document.getElementById('night').style.display = 'none';
    document.getElementById('daytime').style.display = 'none';
    document.getElementById('execution').style.display = 'none';
    document.getElementById('result').style.display = 'none';
    switch (state) {
        case STATE.TOP:
            document.getElementById('top').style.display = 'block';
            break;
        case STATE.MAKING:
            document.getElementById('making').style.display = 'block';
            break;
        case STATE.LOBBY:
            document.getElementById('lobby').style.display = 'block';
            break;
        case STATE.PARTICIPATION:
            document.getElementById('participation').style.display = 'block';
            break;
        case STATE.WAITING:
            document.getElementById('waiting').style.display = 'block';
            break;
        case STATE.ACTION:
            document.getElementById('action').style.display = 'block';
            break;
        case STATE.NOTIFICATION:
            document.getElementById('notification').style.display = 'block';
            break;
        case STATE.NIGHT:
            document.getElementById('night').style.display = 'block';
            break;
        case STATE.DAYTIME:
            document.getElementById('daytime').style.display = 'block';
            break;
        case STATE.EXECUTION:
            document.getElementById('execution').style.display = 'block';
            break;
        case STATE.RESULT:
            document.getElementById('result').style.display = 'block';
            break;
    }
}

//役職IDから役職名（日本語）を取得
function getPositionNameInJapanese(position) {
    var positionString = '';
    switch (position) {
        case POSITION.VILLAGER:
            positionString = '村人';
            break;
        case POSITION.WEREWOLF:
            positionString = '人狼';
            break;
        case POSITION.FORTUNETELLER:
            positionString = '占い師';
            break;
        case POSITION.THIEF:
            positionString = '怪盗';
            break;
        case POSITION.MADMAN:
            positionString = '狂人';
            break;
        case POSITION.HANGING:
            positionString = 'てるてる';
            break;
    }

    return positionString;
}

//役職IDから役職名（英語）を取得
function getPositionNameInEnglish(position) {
    var positionString = '';
    switch (position) {
        case POSITION.VILLAGER:
            positionString = 'Villager';
            break;
        case POSITION.WEREWOLF:
            positionString = 'Werewolf';
            break;
        case POSITION.FORTUNETELLER:
            positionString = 'Fortuneteller';
            break;
        case POSITION.THIEF:
            positionString = 'Thief';
            break;
        case POSITION.MADMAN:
            positionString = 'Madman';
            break;
        case POSITION.HANGING:
            positionString = 'Hanging';
            break;
    }

    return positionString;
}

//プレイヤーIDからプレイヤー名を取得
function getPlayer(id) {
    for (var i = 0; i < playerArray.length; i++) {
        if (playerArray[i].id == id) {
            return playerArray[i];
        }
    }

    return null;
}

//ロード時の処理
window.addEventListener('load',
    function (event) {
        //var wsUri = "ws://www.tetsis.com:9000/server.php";
        var wsUri = "ws://www.tetsis-net:9000/server.php";
        websocket = new WebSocket(wsUri);

        websocket.onopen = function(ev) {};

        //#### Message received from server?
        websocket.onmessage = function(ev) {
            var messageArray = JSON.parse(ev.data); //PHP sends Json data
            var type = messageArray['type'];
            if (type == 'system') {
                state = messageArray['state'];
                var message = messageArray['message'];
                switch(state) {
                    case STATE.CONNECTION:
                        if (message == 'queryData') {
                            queryData();
                        }
                        else if (message == 'deleteData') {
                            deleteData();
                        }
                        break;
                    case STATE.TOP:
                        if (message == 'display') {
                            displayTop();
                        }
                        break;
                    case STATE.MAKING:
                        if (message == 'display') {
                            displayMaking();
                        }
                        else if (message == 'reject') {
                            rejectVillageName();
                        }
                        break;
                    case STATE.LOBBY:
                        if (message == 'display') {
                            displayLobby();
                        }
                        else if (message == 'add') {
                            addVillage(messageArray);
                        }
                        else if (message == 'delete') {
                            deleteVillage();
                        }
                        else if (message == 'reject') {
                            rejectPassword();
                        }
                        break;
                    case STATE.PARTICIPATION:
                        if (message == 'display') {
                            displayParticipation(messageArray);
                        }
                        else if (message == 'reject') {
                            rejectName();
                        }
                        else if (message == 'alreadyStarted') {
                            alreadyStarted();
                        }
                        break;
                    case STATE.WAITING:
                        if (message == 'init') {
                            initInWaiting(messageArray);
                        }
                        else if (message == 'display') {
                            displayWaiting();
                        }
                        else if (message == 'add') {
                            addParticipant(messageArray);
                        }
                        else if (message == 'del') {
                            delParticipant(messageArray);
                        }
                        else if (message == 'setNumberOfPosition') {
                            setNumberOfPositionInWaiting(messageArray);
                        }
                        else if (message == 'setTalkingTime') {
                            setTalkingTimeInWaiting(messageArray);
                        }
                        else if (message == 'gameStart') {
                            setGameStart(messageArray);
                        }
                        break;
                    case STATE.ACTION:
                        if (message == 'init') {
                            initInAction(messageArray);
                        }
                        else if (message == 'display') {
                            displayAction();
                        }
                        else if (message == 'setPlayer') {
                            setPlayerInAction(messageArray);
                        }
                        break;
                    case STATE.NOTIFICATION:
                        if (message == 'init') {
                            initInNotification(messageArray);
                        }
                        else if (message == 'display') {
                            displayNotification();
                        }
                        else if (message == 'setResult') {
                            setResult(messageArray);
                        }
                        else if (message == 'setResultOfField') {
                            setResultOfField(messageArray);
                        }
                        else if (message == 'setBuddy') {
                            setBuddy(messageArray);
                        }
                        break;
                    case STATE.NIGHT:
                        if (message == 'init') {
                            initInNight();
                        }
                        else if (message == 'display') {
                            displayNight();
                        }
                        else if (message == 'setPositionOfPlayer') {
                            setPositionOfPlayerInNight(messageArray);
                        }
                        else if (message == 'setResultOfFortuneteller') {
                            setResultOfFortunetellerInNight(messageArray);
                        }
                        else if (message == 'setResultOfThief') {
                            setResultOfThiefInNight(messageArray);
                        }
                        break;
                    case STATE.DAYTIME:
                        if (message == 'init') {
                            initInDaytime(messageArray);
                        }
                        else if (message == 'display') {
                            displayDaytime();
                        }
                        else if (message == 'setPlayer') {
                            setPlayerInDaytime(messageArray);
                        }
                        else if (message == 'setSpectator') {
                            setSpectatorInDaytime(messageArray);
                        }
                        else if (message == 'setNumberOfPosition') {
                            setNumberOfPositionInDaytime(messageArray);
                        }
                        else if (message == 'setTalkingTime') {
                            setTalkingTimeInDaytime(messageArray);
                        }
                        else if (message == 'setTalksEnd') {
                            setTalksEnd(messageArray);
                        }
                        else if (message == 'setPositionOfPlayer') {
                            setPositionOfPlayerInDaytime(messageArray);
                        }
                        else if (message == 'setResultOfFortuneteller') {
                            setResultOfFortunetellerInDaytime(messageArray);
                        }
                        else if (message == 'setResultOfThief') {
                            setResultOfThiefInDaytime(messageArray);
                        }
                        break;
                    case STATE.EXECUTION:
                        if (message == 'init') {
                            initInExecution(messageArray);
                        }
                        else if (message == 'display') {
                            displayExecution();
                        }
                        else if (message == 'setPlayer') {
                            setPlayerInExecution(messageArray);
                        }
                        break;
                    case STATE.RESULT:
                        if (message == 'init') {
                            initInResult(messageArray);
                        }
                        else if (message == 'display') {
                            displayResult();
                        }
                        else if (message == 'setWinnerOrLoser') {
                            setWinnerOrLoser(messageArray);
                        }
                        else if (message == 'setResultOfPlayer') {
                            setResultOfPlayerInResult(messageArray);
                        }
                        else if (message == 'setResultOfFortuneteller') {
                            setResultOfFortunetellerInResult(messageArray);
                        }
                        else if (message == 'setResultOfThief') {
                            setResultOfThiefInResult(messageArray);
                        }
                        break;
                }
            }
        };

        ////Top////
        document.getElementById('btn_lobby').addEventListener('click', clickLobby, false);
        document.getElementById('btn_making').addEventListener('click', clickMaking, false);

        ////Making////
        document.getElementById('btn_spectatorYes').addEventListener('click', clickSpectatorYes, false);
        document.getElementById('btn_spectatorNo').addEventListener('click', clickSpectatorNo, false);
        document.getElementById('btn_decideInMaking').addEventListener('click', clickDecideInMaking, false);
        document.getElementById('btn_backInMaking').addEventListener('click', clickBackInMaking, false);

        ////Lobby////
        document.getElementById('btn_updateInLobby').addEventListener('click', clickUpdateInLobby, false);
        document.getElementById('btn_decideInLobby').addEventListener('click', clickDecideInLobby, false);
        document.getElementById('btn_backInLobby').addEventListener('click', clickBackInLobby, false);

        ////Participation////
        document.getElementById('btn_participationAsPlayer').addEventListener('click', clickParticipationAsPlayer, false);
        document.getElementById('btn_participationAsSpectator').addEventListener('click', clickParticipationAsSpectator, false);
        document.getElementById('btn_backInParticipation').addEventListener('click', clickBackInParticipation, false);

        ////Waiting////
        document.getElementById('btn_decrementOfNumberOfVillager').addEventListener('click', function(){clickNumberOfPosition(false, POSITION.VILLAGER)}, false);
        document.getElementById('btn_incrementOfNumberOfVillager').addEventListener('click', function(){clickNumberOfPosition(true, POSITION.VILLAGER)}, false);
        document.getElementById('btn_decrementOfNumberOfWerewolf').addEventListener('click', function(){clickNumberOfPosition(false, POSITION.WEREWOLF)}, false);
        document.getElementById('btn_incrementOfNumberOfWerewolf').addEventListener('click', function(){clickNumberOfPosition(true, POSITION.WEREWOLF)}, false);
        document.getElementById('btn_decrementOfNumberOfFortuneteller').addEventListener('click', function(){clickNumberOfPosition(false, POSITION.FORTUNETELLER)}, false);
        document.getElementById('btn_incrementOfNumberOfFortuneteller').addEventListener('click', function(){clickNumberOfPosition(true, POSITION.FORTUNETELLER)}, false);
        document.getElementById('btn_decrementOfNumberOfThief').addEventListener('click', function(){clickNumberOfPosition(false, POSITION.THIEF)}, false);
        document.getElementById('btn_incrementOfNumberOfThief').addEventListener('click', function(){clickNumberOfPosition(true, POSITION.THIEF)}, false);
        document.getElementById('btn_decrementOfNumberOfMadman').addEventListener('click', function(){clickNumberOfPosition(false, POSITION.MADMAN)}, false);
        document.getElementById('btn_incrementOfNumberOfMadman').addEventListener('click', function(){clickNumberOfPosition(true, POSITION.MADMAN)}, false);
        document.getElementById('btn_decrementOfNumberOfHanging').addEventListener('click', function(){clickNumberOfPosition(false, POSITION.HANGING)}, false);
        document.getElementById('btn_incrementOfNumberOfHanging').addEventListener('click', function(){clickNumberOfPosition(true, POSITION.HANGING)}, false);
        document.getElementById('btn_decrementOfTalkingTime').addEventListener('click', function(){clickTalkingTime(false)}, false);
        document.getElementById('btn_incrementOfTalkingTime').addEventListener('click', function(){clickTalkingTime(true)}, false);
        document.getElementById('btn_gameStart').addEventListener('click', clickGameStart, false);
        document.getElementById('btn_backInWaiting').addEventListener('click', clickBackInWaiting, false);

        ////Action////
        document.getElementById('btn_OK').addEventListener('click', clickOK, false);
        document.getElementById('btn_notification').addEventListener('click', clickNotification, false);

        ////Notification////
        document.getElementById('btn_daytime').addEventListener('click', clickDaytime, false);

        ////Daytime////
        document.getElementById('btn_extension').addEventListener('click', clickExtension, false);
        document.getElementById('btn_talksEnd').addEventListener('click', clickTalksEnd, false);
        document.getElementById('btn_confirmation').addEventListener('click', clickConfirmation, false);

        ////Execution////
        document.getElementById('btn_result').addEventListener('click', clickResult, false);

        //Result////
        document.getElementById('btn_nextNight').addEventListener('click', clickNextNight, false);
        document.getElementById('btn_exitInResult').addEventListener('click', clickExitInResult, false);
    }
, false);



//クリック関数
////Top////
//「村に参加」をクリック
function clickLobby() {
    document.getElementById('btn_lobby').disabled = true;
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: STATE.TOP,
        message: 'lobby'
    };
    websocket.send(JSON.stringify(messageArray));
}

//「村を作成」をクリック
function clickMaking() {
    document.getElementById('btn_making').disabled = true;
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: STATE.TOP,
        message: 'making'
    };
    websocket.send(JSON.stringify(messageArray));
}


////Making////
//観戦者ありをクリック
function clickSpectatorYes() {
    selectButton('btn_spectatorYes');
    notSelectButton('btn_spectatorNo');
    spectatorFlag = true;
}

//観戦者なしをクリック
function clickSpectatorNo() {
    selectButton('btn_spectatorNo');
    notSelectButton('btn_spectatorYes');
    spectatorFlag = false;
}

//「決定」をクリック
function clickDecideInMaking() {
    var name = document.getElementById('txt_villageName').value;
    var password = document.getElementById('txt_villagePassword').value;
    if (name == "") {
        alert('名前を入力してください');
    }
    else {
        document.getElementById('btn_decideInMaking').disabled = true;
        //サーバに送信
        var messageArray = {
            type: 'system',
            state: STATE.MAKING,
            message: 'decide',
            name: name,
            password: password,
            spectatorFlag: spectatorFlag
        };
        websocket.send(JSON.stringify(messageArray));
    }
}

//「戻る」をクリック
function clickBackInMaking() {
    document.getElementById('btn_backInMaking').disabled = true;
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: STATE.MAKING,
        message: 'back'
    };
    websocket.send(JSON.stringify(messageArray));
}


////Lobby////
//村をクリック
function clickSelectionInLobby(id, flag) {
    var buttonId;
    if (selectedVillageId != -1) {
        buttonId = 'btn_village' + selectedVillageId;
        notSelectButton(buttonId);
    }
    selectedVillageId = id;
    selectedVillagePasswordFlag = flag;

    buttonId = 'btn_village' + selectedVillageId;
    selectButton(buttonId);
}

//「更新」をクリック
function clickUpdateInLobby() {
    document.getElementById('btn_updateInLobby').disabled = true;
    document.getElementById('btn_decideInLobby').disabled = true;
    document.getElementById('box_villageList').textContent = null;
    selectedVillageId = -1;
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: STATE.LOBBY,
        message: 'update'
    };
    websocket.send(JSON.stringify(messageArray));
}

//「決定」をクリック
function clickDecideInLobby() {
    if (selectedVillageId == -1) {
        alert('参加する村を選択してください');
    }
    else {
        var password = '';
        if (selectedVillagePasswordFlag == true) {
            password = prompt('パスワードを入力してください');
        }
        if (password != null) {
            //ボタンの設定
            document.getElementById('btn_decideInLobby').disabled = true;
            //サーバに送信
            var messageArray = {
                type: 'system',
                state: STATE.LOBBY,
                message: 'decide',
                villageId: selectedVillageId,
                password: password
            };
            websocket.send(JSON.stringify(messageArray));
        }
    }
}

//「戻る」をクリック
function clickBackInLobby() {
    document.getElementById('btn_backInLobby').disabled = true;
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: STATE.LOBBY,
        message: 'back'
    };
    websocket.send(JSON.stringify(messageArray));
}


////Waiting////
//役職の人数をクリック
function clickNumberOfPosition(position, incrementOrDecrement) {
    var buttonId;
    var positionString = getPositionNameInEnglish(position);
    if (incrementOrDecrement == true) {
        buttonId = 'btn_incrementOf' + positionString;
        document.getElementById(buttonId).disabled = true;
        numberOfPositionArray[position]++;
    }
    else {
        buttonId = 'btn_decrementOf' + positionString;
        document.getElementById(buttonId).disabled = true;
        numberOfPositionArray[position]--;
    }
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: STATE.WAITING,
        message: 'setNumberOfPosition',
        villageId: villageId,
        position: position,
        number: numberOfPositionArray[position]
    };
    websocket.send(JSON.stringify(messageArray));
}

//話し合い時間をクリック
function clickTalkingTime(incrementOrDecrement) {
    if (incrementOrDecrement == true) {
        document.getElementById('btn_incrementOfTalkingTime').disabled = true;
        talkingTime++;
    }
    else {
        document.getElementById('btn_decrementOfTalkingTime').disabled = true;
        talkingTime--;
    }
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: STATE.WAITING,
        message: 'setTalkingTime',
        villageId: villageId,
        talkingTime: talkingTime
    };
    websocket.send(JSON.stringify(messageArray));
}

//「ゲーム開始」をクリック
function clickGameStart() {
    var sum = 0;
    for (var i = 0; i < positionArray.length; i++) {
        sum += numberOfPositionArray[positionArray[i]];
    }
    if (sum != (numberOfPlayer + 2)) {
        alert('役職人数の配分を行ってください');
    }
    else if (talkingTime == 0) {
        alert('役職人数の配分を行ってください');
    }
    else {
        document.getElementById('btn_gameStart').disabled = true;
        //サーバに送信
        var messageArray = {
            type: 'system',
            state: STATE.WAITING,
            message: 'gameStart',
            villageId: villageId,
            id: id
        };
        websocket.send(JSON.stringify(messageArray));
    }
}

//「戻る」をクリック
function clickBackInWaiting() {
    document.getElementById('btn_backInWaiting').disabled = true;
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: STATE.WAITING,
        message: 'back',
        villageId: villageId,
        attribute: attribute,
        id: id
    };
    websocket.send(JSON.stringify(messageArray));
}


////Action////
//「OK」をクリック
function clickOK() {
    document.getElementById('btn_notification').disabled = false;
}

//選択プレイヤーをクリック
function clickSelectionInAction(selectionId) {
    var buttonId;
    if (this.selectionId != -1) {
        buttonId = 'btn_player' + this.selectionId + 'InAction';
        notSelectButton(buttonId);
    }
    this.selectionId = selectionId;
    buttonId = 'btn_player' + this.selectionId + 'InAction';
    selectButton(buttonId);
    document.getElementById('btn_notification').disabled = false;
}

//「次へ」をクリック
function clickNotification() {
    if ((position == POSITION.FORTUNETELLER) || (position == POSITION.THIEF) && selectionId == -1) {
        alert('プレイヤーを選択してください');
    }
    else {
        document.getElementById('btn_notification').disabled = true;
        //サーバに送信
        var messageArray = {
            type: 'system',
            state: STATE.ACTION,
            message: 'notification',
            villageId: villageId,
            id: id,
            selectionId: selectionId
        };
        websocket.send(JSON.stringify(messageArray));
    }
}


////Notification////
//「昼のフェーズへ」をクリック
function clickDaytime() {
    document.getElementById('btn_daytime').disabled = true;
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: STATE.NOTIFICATION,
        message: 'daytime',
        villageId: villageId,
        id: id
    };
    websocket.send(JSON.stringify(messageArray));
}


////Daytime////
//「延長」をクリック
function clickExtension() {
    document.getElementById('btn_extension').disabled = true;
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: STATE.DAYTIME,
        message: 'extension',
        villageId: villageId,
        id: id
    };
    websocket.send(JSON.stringify(messageArray));
}

//「話し合い終了」をクリック
function clickTalksEnd() {
    document.getElementById('btn_talksEnd').disabled = true;
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: STATE.DAYTIME,
        message: 'talksEnd',
        villageId: villageId,
        id: id
    };
    websocket.send(JSON.stringify(messageArray));
}

//「役職確認」をクリック
function clickConfirmation() {
    var popupString = '役職確認\n';
    for (var i = 0; i < playerArray.length; i++) {
        popupString += playerArray[i].name + 'の役職は' + playerArray[i].position + 'です\n';
    }
    popupString += '\n';
    popupString += '占い結果\n';
    for (var i = 1; i < resultOfFortunetellerArray.length; i++) {
        var fortunetellerName = getPlayer(resultOfFortunetellerArray[i].id).name;
        if (resultOfFortunetellerArray[i].selectionId == -1) {
            popupString += fortunetellerName + 'は場の2枚を占いました\n';
        }
        else {
            selectionName = getPlayer(resultOfFortunetellerArray[i].selectionId).name;
            popupString += fortunetellerName + 'は' + selectionName + 'を占いました\n';
        }
    }
    popupString += '\n';
    popupString += '交換結果\n';
    for (var i = 1; i < resultOfThiefArray.length; i++) {
        var thiefName = getPlayer(resultOfThiefArray[i].id).name;
        if (resultOfThiefArray[i].selectionId == -1) {
            popupString += thiefName + 'は誰とも交換しませんでした\n';
        }
        else {
            selectionName = getPlayer(resultOfThiefArray[i].selectionId).name;
            popupString += thiefName + 'は' + selectionName + 'と役職を交換しました\n';
        }
    }
    alert(popupString);
}


////Execution////
//吊るプレイヤーをクリック
function clickSelectionInExecution(selectionId) {
    var buttonId;
    if (this.selectionId != -1) {
        buttonId = 'btn_player' + this.selectionId + 'InExecution';
        notSelectButton(buttonId);
    }
    this.selectionId = selectionId;
    buttonId = 'btn_player' + this.selectionId + 'InExecution';
    selectButton(buttonId);
    document.getElementById('btn_result').disabled = false;
}

//「結果発表へ」をクリック
function clickResult() {
    if (selectionId == -1) {
        alert('吊るプレイヤーを選択してください');
    }
    else {
        document.getElementById('btn_result').disabled = true;
        //サーバに送信
        var messageArray = {
            type: 'system',
            state: STATE.EXECUTION,
            message: 'result',
            villageId: villageId,
            id: id,
            selectionId: selectionId
        };
        websocket.send(JSON.stringify(messageArray));
    }
}


////Result////
//「次の夜へ」をクリック
function clickNextNight() {
    document.getElementById('btn_nextNight').disabled = true;
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: STATE.RESULT,
        message: 'nextNight',
        villageId: villageId
    };
    websocket.send(JSON.stringify(messageArray));
}

//「終了」をクリック
function clickExit() {
    document.getElementById('btn_exitInResult').disabled = true;
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: STATE.RESULT,
        message: 'exit',
        villageId: villageId
    };
    websocket.send(JSON.stringify(messageArray));
}



//サーバからの通信関数
////Connection////
//データを要求
function queryData() {
    var data = window.localStorage.getItem("data");
    if (data != null) {
        var dataArray = JSON.parse(data);
        villageId = dataArray['villageId'];
        attribute = dataArray['attribute'];
        id = dataArray['id'];
        //サーバに送信
        var messageArray = {
            type: 'system',
            state: STATE.CONNECTION,
            message: 'reply',
            villageId: villageId,
            attribute: attribute,
            id: id
        };
        websocket.send(JSON.stringify(messageArray));
    }
    else {
        //サーバに送信
        var messageArray = {
            type: 'system',
            state: STATE.CONNECTION,
            message: 'none'
        };
        websocket.send(JSON.stringify(messageArray));
    }
}

//データを削除
function deleteData() {
    var data = window.localStorage.getItem("data");
    if (data != null) {
        window.localStorage.removeItem("data");
    }
}

////Top////
//トップ画面を表示
function displayTop() {
    document.getElementById('btn_lobby').disabled = false;
    document.getElementById('btn_making').disabled = false;
    displayState(STATE.TOP);
}


////Making////
//村作成画面を表示
function displayMaking() {
    selectButton('btn_spectatorYes');
    notSelectButton('btn_spectatorNo');
    document.getElementById('btn_decideInMaking').disabled = false;
    document.getElementById('btn_backInMaking').disabled = false;
    displayState(STATE.MAKING);
}

//村名重複により拒否
function rejectVillageName() {
    alert('同じ名前の村が既に存在しています\n別の名前で作り直してください')
    document.getElementById('txt_villageName').value = '';
    document.getElementById('btn_decideInMaking').disabled = false;
}


////Lobby////
//村一覧画面を表示
function displayLobby() {
    selectedVillageId = -1;
    document.getElementById('btn_updateInLobby').disabled = false;
    document.getElementById('btn_decideInLobby').disabled = true;
    document.getElementById('btn_backInLobby').disabled = false;
    displayState(STATE.LOBBY);
}

//村を追加
function addVillage(messageArray) {
    villageId = messageArray['villageId'];
    villageName = messageArray['villageName'];
    var passwordFlag = messageArray['passwordFlag'];
    var box = document.getElementById('box_selectionInLobby');
    var element = document.createElement('input');
    element.id = 'btn_village' + id;
    element.type = 'button';
    element.value = villageName;
    element.addEventListener('click', function(){clickSelectionInLobby(villageId, passwordFlag)}, false);
    box.appendChild(element);
    box.appendChild(document.createElement('br'));
    document.getElementById('btn_updateInLobby').disabled = false;
}

//村が既に削除されていることにより拒否
function deleteVillage() {
    alert('選択した村は既に廃村になっています');
    document.getElementById('btn_decideInLobby').disabled = false;
}

//パスワードが違うことにより拒否
function rejectPassword() {
    alert('パスワードが間違っています');
    document.getElementById('btn_decideInLobby').disabled = false;
}


////Participation////
//村参加画面を表示
function displayParticipation(messageArray) {
    villageId = messageArray['villageId'];
    villageName = messageArray['villageName'];
    spectatorFlag = messageArray['spectatorFlag'];
    document.getElementById('scrn_villageName').innerHTML = villageName + ' 村';
    if (spectatorFlag == true) {
        document.getElementById('btn_participationAsSpectator').disabled = false;
    }
    else {
        document.getElementById('btn_participationAsSpectator').disabled = true;
    }
    document.getElementById('btn_participationAsPlayer').disabled = false;
    document.getElementById('btn_backInParticipation').disabled = false;
    displayState(STATE.PARTICIPATION);
}

//参加者名重複により拒否
function rejectName() {
    alert('同じ名前の参加者がいます\n違う名前で参加してください');
    if (spectatorFlag == true) {
        document.getElementById('btn_participationAsSpectator').disabled = false;
    }
    else {
        document.getElementById('btn_participationAsSpectator').disabled = true;
    }
    document.getElementById('btn_participationAsPlayer').disabled = false;
}

//ゲームが既に開始していることにより拒否
function alreadyStarted() {
    alert('既にゲームが開始しています');
    displayTop();
}


////Waiting////
//初期化
function initInWaiting(messageArray) {
    playerArray = [];
    numberOfPlayer = 0;
    numberOfPositionArray = [];
    talkingTime = 0;
    document.getElementById('box_playerListInWaiting').textContent = null;
    document.getElementById('box_spectatorListInWaiting').textContent = null;

    villageId = messageArray['villageId'];
    attribute = messageArray['attribute'];
    id = messageArray['id'];
    switch (attribute) {
        case ATTRIBUTE.PLAYER:
            document.getElementById('btn_gameStart').disabled = false;
            break;
        case ATTRIBUTE.SPECTATOR:
            document.getElementById('btn_gameStart').disabled = true;
            document.getElementById('btn_decrementOfNumberOfVillager').disabled = true;
            document.getElementById('btn_incrementOfNumberOfVillager').disabled = true;
            document.getElementById('btn_decrementOfNumberOfWerewolf').disabled = true;
            document.getElementById('btn_incrementOfNumberOfWerewolf').disabled = true;
            document.getElementById('btn_decrementOfNumberOfFortuneteller').disabled = true;
            document.getElementById('btn_incrementOfNumberOfFortuneteller').disabled = true;
            document.getElementById('btn_decrementOfNumberOfThief').disabled = true;
            document.getElementById('btn_incrementOfNumberOfThief').disabled = true;
            document.getElementById('btn_decrementOfNumberOfMadman').disabled = true;
            document.getElementById('btn_incrementOfNumberOfMadman').disabled = true;
            document.getElementById('btn_decrementOfNumberOfHanging').disabled = true;
            document.getElementById('btn_incrementOfNumberOfHanging').disabled = true;
            document.getElementById('btn_decrementOfTalkingTime').disabled = true;
            document.getElementById('btn_incrementOfTalkingTime').disabled = true;
            break;
    }
    document.getElementById('btn_backInWaiting').disabled = false;
}

//待機画面を表示
function displayWaiting() {
    displayState(STATE.WAITING);
}

//参加者を追加
function addParticipant(messageArray) {
    var box;
    var element;
    var attribute = messageArray['attribute'];
    var id = messageArray['id'];
    var name = messageArray['name'];
    switch (attribute) {
        case ATTRIBUTE.PLAYER:
            var player = {id: id, name: name};
            playerArray.push(player);
            numberOfPlayer++;
            box = document.getElementById('box_playerListInWaiting');
            element = document.createElement('div');
            element.id = 'scrn_playerListInWaiting' + id;
            element.innerHTML = name;
            box.appendChild(element);
            break;
        case ATTRIBUTE.SPECTATOR:
            box = document.getElementById('box_spectatorListInWaiting');
            element = document.createElement('div');
            element.id = 'scrn_spectatorListInWaiting' + id;
            element.innerHTML = name;
            box.appendChild(element);
            break;
    }
}

//参加者を削除
function delParticipant(messageArray) {
    var box;
    var element;
    var attribute = messageArray['attribute'];
    var id = messageArray['id'];
    switch (attribute) {
        case ATTRIBUTE.PLAYER:
            var number = -1;
            for (var i = 0; i < playerArray.length; i++) {
                if (playerArray[i].id == id) {
                    number = i;
                    break;
                }
            }
            if (number != -1) {
                playerArray.splice(number, 1);
                numberOfPlayer--;
                box = document.getElementById('box_playerListInWaiting');
                element = document.getElementById('scrn_playerListInWaiting' + id);
                if (element != null) {
                    box.removeChild(element);
                }
            }
            break;
        case ATTRIBUTE.SPECTATOR:
            box = document.getElementById('box_spectatorListInWaiting');
            element = document.getElementById('scrn_spectatorListInWaiting' + id);
            if (element != null) {
                box.removeChild(element);
            }
            break;
    }
}

//役職の人数を設定
function setNumberOfPositionInWaiting(messageArray) {
    var divId;
    var buttonId;
    var position = messageArray['position'];
    var number = messageArray['number'];
    numberOfPositionArray[position] = number;
    var positionString getPositionNameInEnglish(position);
    divId = 'scrn_numberOf' + positionString + 'InWaiting';
    document.getElementById(divId).innerHTML = number + '人';
    if (attribute == ATTRIBUTE.PLAYER) {
        var sum = 0;
        for (var i = 0; i < positionArray.length; i++) {
            sum += numberOfPositionArray[positionArray[i]];
        }
        if (sum >= numberOfPlayer + 2 ) {
            document.getElementById('btn_incrementOfNumberOfVillager').disabled = true;
            document.getElementById('btn_incrementOfNumberOfWerewolf').disabled = true;
            document.getElementById('btn_incrementOfNumberOfFortuneteller').disabled = true;
            document.getElementById('btn_incrementOfNumberOfThief').disabled = true;
            document.getElementById('btn_incrementOfNumberOfMadman').disabled = true;
            document.getElementById('btn_incrementOfNumberOfHanging').disabled = true;
        }
        else {
            document.getElementById('btn_incrementOfNumberOfVillager').disabled = false;
            document.getElementById('btn_incrementOfNumberOfWerewolf').disabled = false;
            document.getElementById('btn_incrementOfNumberOfFortuneteller').disabled = false;
            if (numberOfPositionArray[POSITION.THIEF] >= 1) {
                document.getElementById('btn_incrementOfNumberOfThief').disabled = true;
            }
            else {
                document.getElementById('btn_incrementOfNumberOfThief').disabled = false;
            }
            document.getElementById('btn_incrementOfNumberOfMadman').disabled = false;
            if (numberOfPositionArray[POSITION.HANGING] >= 1) {
                document.getElementById('btn_incrementOfNumberOfHanging').disabled = true;
            }
            else {
                document.getElementById('btn_incrementOfNumberOfHanging').disabled = false;
            }
        }
        for (var i = 0; i < positionArray.length; i++) {
            positionString = getPositionNameInEnglish(positionArray[i]);
            buttonId = 'btn_decrementOfNumberOf' + positionString;
            if (numberOfPositionArray[positionArray[i]] <= 0) {
                document.getElementById(buttonId).disabled = true;
            }
            else {
                document.getElementById(buttonId).disabled = false;
            }
        }
    }
}

//話し合い時間を設定
function setTalkingTimeInWaiting(messageArray) {
    var time = messageArray['time'];
    talkingTime = time;
    document.getElementById('scrn_talkingTimeInWaiting').innerHTML = time;
    if (attribute == ATTRIBUTE.PLAYER) {
        document.getElementById('btn_incrementOfTalkingTime').disabled = false;
        if (time <= 1) {
            document.getElementById('btn_decrementOfTalkingTime').disabled = true;
        }
        else {
            document.getElementById('btn_decrementOfTalkingTime').disabled = false;
        }
    }
}

//「ゲーム開始」をクリックしたプレイヤーがいた
function setGameStart(messageArray) {
    var id = messageArray['id'];
    var elementId = 'scrn_playerListInWaiting' + id;
    selectedElement(elementId);
}


////Action////
//初期化
function initInAction(messageArray) {
    selectionId = -1;
    document.getElementById('box_selectionInAction').textContent = null;
    villageId = messageArray['villageId'];
    id = messageArray['id'];
    position = messageArray['position'];
    switch (position) {
        case POSITION.VILLAGER:
            document.getElementById('scrn_yourPosition').innerHTML = 'あなたは村人です';
            break;
        case POSITION.WEREWOLF:
            document.getElementById('scrn_yourPosition').innerHTML = 'あなたは人狼です';
            break;
        case POSITION.FORTUNETELLER:
            document.getElementById('scrn_yourPosition').innerHTML = 'あなたは占い師です\n占うプレイヤーを選んでください';
            break;
        case POSITION.THIEF:
            document.getElementById('scrn_yourPosition').innerHTML = 'あなたは怪盗です\n役職を交換するプレイヤーを選んでください';
            break;
        case POSITION.MADMAN:
            document.getElementById('scrn_yourPosition').innerHTML = 'あなたは狂人です';
            break;
        case POSITION.HANGING:
            document.getElementById('scrn_yourPosition').innerHTML = 'あなたはてるてるです';
            break;
    }
}

//行動画面を表示
function displayAction() {
    displayState(STATE.ACTION);
}

//選択するプレイヤーを追加
function setPlayerInAction(messageArray) {
    var id = messageArray['id'];
    var name = messageArray['name'];
    var box = document.getElementById('box_selectionInAction');
    var element = document.createElement('input');
    element.id = 'btn_player' + id + 'InAction';
    element.type = 'button';
    element.value = name;
    element.addEventListener('click', function(){clickSelectionInAction(id)}, false);
    box.appendChild(element);
    box.appendChild(document.createElement('br'));
}


////Notification////
//初期化
function initInNotification(messageArray) {
    buddyNameArray = [];
    selectionName = '';
    selectionPosition = -1;
    position1 = -1;
    position2 = -1;
    villageId = messageArray['villageId'];
    id = messageArray['id'];
    position = messageArray['position'];
    document.getElementById('btn_daytime').disabled = false;
}

//通知画面を表示
function displayNotification() {
    var resultString = '';
    switch (position) {
        case POSITION.VILLAGER:
            resultString += '仲間の村人と一緒に村を守りましょう';
            break;
        case POSITION.WEREWOLF:
            resultString += '仲間の人狼は\n';
            if (buddyNameArray.length >= 1) {
                for (var i = 0; i < buddyNameArray.length; i++) {
                    resultString += '・' + buddyNameArray[i] + '\n';
                }
            }
            else {
                resultString += 'いませんでした';
            }
            break;
        case POSITION.FORTUNETELLER:
            if (selectionPosition == -1) {
                var position1String = getPositionNameInJapanese(position1);
                var position2String = getPositionNameInJapanese(position2);
                resultString += '場の役職は' + position1String + 'と' + position2String + 'です';
            }
            else {
                var positionString = getPositionNameInJapanese(selectionPosition);
                resultString += selectionName + 'は' + positionString + 'です';
            }
            break;
        case POSITION.THIEF:
            if (selectionPosition == -1) {
                resultString += '誰とも交換していません';
            }
            else {
                var positionString = getPositionNameInJapanese(selectionPosition);
                resultString += selectionName + 'の' + positionString + 'と役職を交換しました';
            }
            break;
        case POSITION.MADMAN:
                resultString += '村を混乱に陥れて人狼が有利になるように行動しましょう';
            break;
        case POSITION.HANGING:
                resultString += '村人から吊られるように行動しましょう';
            break;
    }
    displayState(STATE.NOTIFICATION);
}

//結果を設定
function setResult(messageArray) {
    selectionName = messageArray['name'];
    selectionPosition = messageArray['position'];
}

//場の役職を設定
function setResultOfField(messageArray) {
    position1 = messageArray['position1'];
    position2 = messageArray['position2'];
}

//仲間を設定
function setBuddy(messageArray) {
    var name = messageArray['name'];
    buddyNameArray.push(name);
}


////Night////
//初期化
function initInNight() {
    playerArray = [];
    document.getElementById('box_playerListInNight').textContent = null;
    document.getElementById('box_resultOfFortunetellerInNight').textContent = null;
    document.getElementById('box_resultOfThiefInNight').textContent = null;
}

//夜の画面を表示
function displayNight() {
    displayState(STATE.NIGHT);
}

//プレイヤーの役職を設定
function setPositionOfPlayerInNight(messageArray) {
    var id = messageArray['id'];
    var name = messageArray['name'];
    var position = messageArray['position'];
    var player = {id: id, name: name, position: position};
    playerArray.push(player);
    var positionString = getPositionNameInJapanese(position);
    var box = document.getElementById('box_playerListInNight');
    var element = document.createElement('div');
    element.id = 'scrn_playerListInNight' + id;
    element.innerHTML = name + ': ' + positionString;
    box.appendChild(element);
}

//占い結果を設定
function setResultOfFortunetellerInNight(messageArray) {
    var id = messageArray['id'];
    var selectionId = messageArray['selectionId'];
    var fortunetellerName = getPlayer(id).name;
    var box = document.getElementById('box_resultOfFortunetellerInNight');
    var element = document.createElement('div');
    element.id = 'scrn_resultOfFortunetellerInNight' + id;
    if (selectionId == -1) {
        element.innerHTML = fortunetellerName + 'は場を占いました';
    }
    else {
        selectionName = getPlayer(selectionId).name;
        element.innerHTML = fortunetellerName + 'は' + selectionName + 'を占いました';
    }
    box.appendChild(element);
}

//交換結果を設定
function setResultOfThiefInNight(messageArray) {
    var id = messageArray['id'];
    var selectionId = messageArray['selectionId'];
    var thiefName = getPlayer(id).name;
    selectionName = getPlayer(selectionId).name;
    var box = document.getElementById('box_resultOfThiefInNight');
    var element = document.createElement('div');
    element.id = 'scrn_resultOfThiefInNight' + id;
    if (selectionId == -1) {
        element.innerHTML = thiefName + '役職を交換していません';
    }
    else {
        selectionName = getPlayer(selectionId).name;
        element.innerHTML = thiefName + 'は' + selectionName + 'と役職を交換しました';
    }
    box.appendChild(element);
}


////Daytime////
//初期化
function initInDaytime(messageArray) {
    playerArray = [];
    numberOfPositionArray = [];
    talkingTime = 3;
    resultOfFortunetellerArray = [];
    resultOfThiefArray = [];
    document.getElementById(box_playerListInDaytime).textContent = null;
    document.getElementById(box_spectatorListInDaytime).textContent = null;
    villageId = messageArray['villageId'];
    attribute = messageArray['attribute'];
    id = messageArray['id'];
    switch (attribute) {
        case ATTRIBUTE.PLAYER:
            document.getElementById('btn_talksEnd').disabled = false;
            break;
        case ATTRIBUTE.SPECTATOR:
            document.getElementById('btn_talksEnd').disabled = true;
            document.getElementById('btn_extension').style.display = 'none';
            break;
    }
}

//昼の画面を表示
function displayDaytime() {
    displayState(STATE.DAYTIME);
}

//プレイヤーを設定
function setPlayerInDaytime(messageArray) {
    var id = messageArray['id'];
    var name = messageArray['name'];
    var player = {id: id, name: name, position: -1};
    playerArray.push(player);
    var box = document.getElementById('box_playerListInDaytime');
    var element = document.createElement('div');
    element.id = 'scrn_playerListInDaytime' + id;
    element.innerHTML = name;
    box.appendChild(element);
}

//観戦者を設定
function setSpectatorInDaytime(messageArray) {
    var id = messageArray['id'];
    var name = messageArray['name'];
    var box = document.getElementById('box_spectatorListInDaytime');
    var element = document.createElement('div');
    element.id = 'scrn_spectatorListInDaytime' + id;
    element.innerHTML = name;
    box.appendChild(element);
}

//役職の人数を設定
function setNumberOfPositionInDaytime(messageArray) {
    var position = messageArray['position'];
    var number = messageArray['number'];
    numberOfPositionArray[position] = number;
    var positionString = getPositionNameInEnglish(position);
    var elementId = 'scrn_numberOf' + positionString + 'InDaytime';
    document.getElementById(elementId).innerHTML = number + '人';
}

//話し合い時間を設定
function setTalkingTimeInDaytime(messageArray) {
    var time = messageArray['time'];
    talkingTime = time;
    setInterval(updateTimer, 100);
}

//「話し合い終了」をクリックしたプレイヤーがいた
function setTalksEnd(messageArray) {
    var id = messageArray['id'];
    var elementId = 'scrn_playerListInDaytime' + id;
    selectedElement(elementId);
}

//プレイヤーの役職を設定
function setPositionOfPlayerInDaytime(messageArray) {
    var id = messageArray['id'];
    var position = messageArray['position'];
    for (var i = 0; i < playerArray.length; i++) {
        if (playerArray[i].id == id) {
            playerArray[i].position = position;
            break;
        }
    }
}

//占い結果を設定
function setResultOfFortunetellerInDaytime(messageArray) {
    var id = messageArray['id'];
    var selectionId = messageArray['selectionId'];
    var array = {id: id, selectionId: selectionId};
    resultOfFortunetellerArray.push(array);
}

//交換結果を設定
function setResultOfThiefInDaytime(messageArray) {
    var id = messageArray['id'];
    var selectionId = messageArray['selectionId'];
    var array = {id: id, selectionId: selectionId};
    resultOfThiefArray.push(array);
}