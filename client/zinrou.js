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
var numberOfLeft;
var talkingTime;
var remaingTime;
var selectionId;
var selectionName;
var selectionPosition;
var buddyNameArray;
var positionArray;
var timer;

playerArray = [];
resultOfFortunetellerArray = [];
resultOfThiefArray = [];
numberOfPositionArray = [];
buddyNameArray = [];
positionArray = [
    'VILLAGER',
    'WEREWOLF',
    'FORTUNETELLER',
    'THIEF',
    'MADMAN',
    'HANGING'
];

//汎用関数
//stateの画面を表示
function displayState(state) {
    console.log('ENTER: displayState, state: ' + state);
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
        case 'TOP':
            document.getElementById('top').style.display = 'block';
            break;
        case 'MAKING':
            document.getElementById('making').style.display = 'block';
            break;
        case 'LOBBY':
            document.getElementById('lobby').style.display = 'block';
            break;
        case 'PARTICIPATION':
            document.getElementById('participation').style.display = 'block';
            break;
        case 'WAITING':
            document.getElementById('waiting').style.display = 'block';
            break;
        case 'ACTION':
            document.getElementById('action').style.display = 'block';
            break;
        case 'NOTIFICATION':
            document.getElementById('notification').style.display = 'block';
            break;
        case 'NIGHT':
            document.getElementById('night').style.display = 'block';
            break;
        case 'DAYTIME':
            document.getElementById('daytime').style.display = 'block';
            break;
        case 'EXECUTION':
            document.getElementById('execution').style.display = 'block';
            break;
        case 'RESULT':
            document.getElementById('result').style.display = 'block';
            break;
    }
    //最上部へスクロール
    window.scrollTo(0, 0);
}

//buttonIdのボタンを選択されたという表示に設定
function selectButton(buttonId) {
    document.getElementById(buttonId).style.background = 'blue';
    document.getElementById(buttonId).style.color = 'white';
}

//buttonIdのボタンを選択されていないという表示に設定
function notSelectButton(buttonId) {
    document.getElementById(buttonId).style.background = '';
    document.getElementById(buttonId).style.color = '';
}

//elementIdの要素を選択されたという表示に設定
function selectedElement(elementId) {
    document.getElementById(elementId).style.background = 'blue';
    document.getElementById(elementId).style.color = 'white';
}

//elementIdの要素を選択されていないという表示に設定
function notSelectedElement(elementId) {
    document.getElementById(elementId).style.background = '';
    document.getElementById(elementId).style.color = '';
}

//タイマーを更新
function updateTimer() {
    console.log('ENTER: updateTimer');
    var screen = document.getElementById('scrn_remainingTime');
    if (remaingTime > 0) {
        remaingTime--;
        var minute = parseInt(remaingTime / 60);
        var second = remaingTime % 60;
        document.getElementById('box_extension').style.display = 'none';
        screen.innerHTML = minute + ' : ' + second;
        screen.style.display = 'block';
    }
    else {
        screen.style.display = 'none';
        document.getElementById('box_extension').style.display = 'block';
        clearInterval(timer);
    }
}

//役職IDから役職名（日本語）を取得
function getPositionNameInJapanese(position) {
    var positionString = '';
    switch (position) {
        case 'VILLAGER':
            positionString = '村人';
            break;
        case 'WEREWOLF':
            positionString = '人狼';
            break;
        case 'FORTUNETELLER':
            positionString = '占い師';
            break;
        case 'THIEF':
            positionString = '怪盗';
            break;
        case 'MADMAN':
            positionString = '狂人';
            break;
        case 'HANGING':
            positionString = 'てるてる';
            break;
    }

    return positionString;
}

//役職IDから役職名（英語）を取得
function getPositionNameInEnglish(position) {
    var positionString = '';
    switch (position) {
        case 'VILLAGER':
            positionString = 'Villager';
            break;
        case 'WEREWOLF':
            positionString = 'Werewolf';
            break;
        case 'FORTUNETELLER':
            positionString = 'Fortuneteller';
            break;
        case 'THIEF':
            positionString = 'Thief';
            break;
        case 'MADMAN':
            positionString = 'Madman';
            break;
        case 'HANGING':
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

//ローカルストレージに保存
function setStorageData(villageId, attribute, id) {
    console.log('ENTER: setStorageData');
    var dataArray = {
        villageId: villageId,
        attribute: attribute,
        id: id
    };
    var data = JSON.stringify(dataArray);
    window.localStorage.setItem("data", data);
}

//サーバにメッセージを送信
function sendMessage(messageArray) {
    var message = JSON.stringify(messageArray);
    websocket.send(message);
    console.log('SEND: ' + message);
}

//ロード時の処理
window.addEventListener('load',
    function (event) {
        console.log('ENTER: addEventListener');
        displayState(-1);
        var hostname = window.location.hostname;
        var wsUri = 'ws://' + hostname + ':9000';
        websocket = new WebSocket(wsUri);

        websocket.onopen = function(ev) {};

        //#### Message received from server?
        websocket.onmessage = function(ev) {
            var messageArray = JSON.parse(ev.data); //PHP sends Json data
            console.log('RECEIVE: ' + JSON.stringify(messageArray));
            var type = messageArray['type'];
            if (type == 'system') {
                state = messageArray['state'];
                var message = messageArray['message'];
                switch(state) {
                    case 'CONNECTION':
                        if (message == 'query') {
                            queryData();
                        }
                        else if (message == 'delete') {
                            deleteData();
                        }
                        break;
                    case 'TOP':
                        if (message == 'display') {
                            displayTop();
                        }
                        break;
                    case 'MAKING':
                        if (message == 'display') {
                            displayMaking();
                        }
                        else if (message == 'reject') {
                            rejectVillageName();
                        }
                        break;
                    case 'LOBBY':
                        if (message == 'display') {
                            displayLobby();
                        }
                        else if (message == 'add') {
                            addVillage(messageArray);
                        }
                        else if (message == 'notExit') {
                            notExitVillage();
                        }
                        else if (message == 'delete') {
                            deleteVillage();
                        }
                        else if (message == 'reject') {
                            rejectPassword();
                        }
                        break;
                    case 'PARTICIPATION':
                        if (message == 'display') {
                            displayParticipation(messageArray);
                        }
                        else if (message == 'reject') {
                            rejectName();
                        }
                        else if (message == 'exceedNumberOfPlayer') {
                            exceedNumberOfPlayer();
                        }
                        else if (message == 'alreadyStarted') {
                            alreadyStarted();
                        }
                        break;
                    case 'WAITING':
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
                        else if (message == 'setGameStart') {
                            setGameStart(messageArray);
                        }
                        break;
                    case 'ACTION':
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
                    case 'NOTIFICATION':
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
                    case 'NIGHT':
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
                    case 'DAYTIME':
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
                        else if (message == 'setRemainingTime') {
                            setRemainingTimeInDaytime(messageArray);
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
                    case 'EXECUTION':
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
                    case 'RESULT':
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
        document.getElementById('btn_decrementOfNumberOfVillager').addEventListener('click', function(){clickNumberOfPosition(false, 'VILLAGER')}, false);
        document.getElementById('btn_incrementOfNumberOfVillager').addEventListener('click', function(){clickNumberOfPosition(true, 'VILLAGER')}, false);
        document.getElementById('btn_decrementOfNumberOfWerewolf').addEventListener('click', function(){clickNumberOfPosition(false, 'WEREWOLF')}, false);
        document.getElementById('btn_incrementOfNumberOfWerewolf').addEventListener('click', function(){clickNumberOfPosition(true, 'WEREWOLF')}, false);
        document.getElementById('btn_decrementOfNumberOfFortuneteller').addEventListener('click', function(){clickNumberOfPosition(false, 'FORTUNETELLER')}, false);
        document.getElementById('btn_incrementOfNumberOfFortuneteller').addEventListener('click', function(){clickNumberOfPosition(true, 'FORTUNETELLER')}, false);
        document.getElementById('btn_decrementOfNumberOfThief').addEventListener('click', function(){clickNumberOfPosition(false, 'THIEF')}, false);
        document.getElementById('btn_incrementOfNumberOfThief').addEventListener('click', function(){clickNumberOfPosition(true, 'THIEF')}, false);
        document.getElementById('btn_decrementOfNumberOfMadman').addEventListener('click', function(){clickNumberOfPosition(false, 'MADMAN')}, false);
        document.getElementById('btn_incrementOfNumberOfMadman').addEventListener('click', function(){clickNumberOfPosition(true, 'MADMAN')}, false);
        document.getElementById('btn_decrementOfNumberOfHanging').addEventListener('click', function(){clickNumberOfPosition(false, 'HANGING')}, false);
        document.getElementById('btn_incrementOfNumberOfHanging').addEventListener('click', function(){clickNumberOfPosition(true, 'HANGING')}, false);
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
    console.log('ENTER: clickLobby');
    document.getElementById('btn_lobby').disabled = true;
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: 'TOP',
        message: 'lobby'
    };
    sendMessage(messageArray);
}

//「村を作成」をクリック
function clickMaking() {
    console.log('ENTER: clickMaking');
    document.getElementById('btn_making').disabled = true;
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: 'TOP',
        message: 'making'
    };
    sendMessage(messageArray);
}


////Making////
//観戦者ありをクリック
function clickSpectatorYes() {
    console.log('ENTER: clickSpectatorYes');
    selectButton('btn_spectatorYes');
    notSelectButton('btn_spectatorNo');
    spectatorFlag = true;
}

//観戦者なしをクリック
function clickSpectatorNo() {
    console.log('ENTER: clickSpectatorNo');
    selectButton('btn_spectatorNo');
    notSelectButton('btn_spectatorYes');
    spectatorFlag = false;
}

//「決定」をクリック
function clickDecideInMaking() {
    console.log('ENTER: clickDecideInLobby');
    var name = document.getElementById('txt_villageName').value;
    var password = document.getElementById('txt_villagePassword').value;
    if (name == "") {
        alert('名前を入力してください');
    }
    else {
        document.getElementById('btn_decideInMaking').disabled = true;
        if (password !== "") {
            password = MD5_hash(password);
        }
        //サーバに送信
        var messageArray = {
            type: 'system',
            state: 'MAKING',
            message: 'decide',
            name: name,
            password: password,
            spectatorFlag: spectatorFlag
        };
        sendMessage(messageArray);
    }
}

//「戻る」をクリック
function clickBackInMaking() {
    console.log('ENTER: clickBackInMaking');
    document.getElementById('btn_backInMaking').disabled = true;
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: 'MAKING',
        message: 'back'
    };
    sendMessage(messageArray);
}


////Lobby////
//村をクリック
function clickSelectionInLobby(id, flag) {
    console.log('ENTER: clickSelectionInLobby, id: ' + id + ', flag: ' + flag);
    var buttonId;
    if (selectedVillageId != -1) {
        buttonId = 'btn_village' + selectedVillageId;
        notSelectButton(buttonId);
    }
    selectedVillageId = id;
    selectedVillagePasswordFlag = flag;

    buttonId = 'btn_village' + selectedVillageId;
    selectButton(buttonId);
    document.getElementById('btn_decideInLobby').disabled = false;
}

//「更新」をクリック
function clickUpdateInLobby() {
    console.log('ENTER: clickUpdateInLobby');
    document.getElementById('btn_updateInLobby').disabled = true;
    document.getElementById('btn_decideInLobby').disabled = true;
    document.getElementById('box_villageList').textContent = null;
    selectedVillageId = -1;
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: 'LOBBY',
        message: 'update'
    };
    sendMessage(messageArray);
}

//「決定」をクリック
function clickDecideInLobby() {
    console.log('ENTER: clickDecideInLobby');
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
            if (password !== "") {
                password = MD5_hash(password);
            }
            //サーバに送信
            var messageArray = {
                type: 'system',
                state: 'LOBBY',
                message: 'decide',
                villageId: selectedVillageId,
                password: password
            };
            sendMessage(messageArray);
        }
    }
}

//「戻る」をクリック
function clickBackInLobby() {
    console.log('ENTER: clickBackInLobby');
    document.getElementById('btn_backInLobby').disabled = true;
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: 'LOBBY',
        message: 'back'
    };
    sendMessage(messageArray);
}


////Participation////
//「プレイヤー参加」をクリック
function clickParticipationAsPlayer() {
    console.log('ENTER: clickParticipationAsPlayer');
    var name = document.getElementById('txt_participantName').value;
    if (name == "") {
        alert('名前を入力してください');
    }
    else {
        document.getElementById('btn_participationAsPlayer').disabled = true;
        //サーバに送信
        var messageArray = {
            type: 'system',
            state: 'PARTICIPATION',
            message: 'participateAsPlayer',
            villageId: villageId,
            name: name
        };
        sendMessage(messageArray);
    }
}

//「観戦者参加」をクリック
function clickParticipationAsSpectator() {
    console.log('ENTER: clickParticipationAsSpectator');
    var name = document.getElementById('txt_participantName').value;
    if (name == "") {
        alert('名前を入力してください');
    }
    else {
        document.getElementById('btn_participationAsSpectator').disabled = true;
        //サーバに送信
        var messageArray = {
            type: 'system',
            state: 'PARTICIPATION',
            message: 'participateAsSpectator',
            villageId: villageId,
            name: name
        };
        sendMessage(messageArray);
    }
}

//「戻る」をクリック
function clickBackInParticipation() {
    console.log('ENTER: clickBackInParticipation');
    document.getElementById('btn_backInParticipation').disabled = true;
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: 'PARTICIPATION',
        message: 'back',
        villageId: villageId
    };
    sendMessage(messageArray);
}


////Waiting////
//役職の人数をクリック
function clickNumberOfPosition(incrementOrDecrement, position) {
    console.log('ENTER: clickNumberOfPosition, position: ' + position + ', incrementOrDecrement: ' + incrementOrDecrement);
    var buttonId;
    var positionString = getPositionNameInEnglish(position);
    if (incrementOrDecrement == true) {
        buttonId = 'btn_incrementOfNumberOf' + positionString;
        document.getElementById(buttonId).disabled = true;
        numberOfPositionArray[position]++;
    }
    else {
        buttonId = 'btn_decrementOfNumberOf' + positionString;
        document.getElementById(buttonId).disabled = true;
        numberOfPositionArray[position]--;
    }
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: 'WAITING',
        message: 'setNumberOfPosition',
        villageId: villageId,
        position: position,
        number: numberOfPositionArray[position]
    };
    sendMessage(messageArray);
}

//話し合い時間をクリック
function clickTalkingTime(incrementOrDecrement) {
    console.log('ENTER: clickTalkingTime, incrementOrDecrement: ' + incrementOrDecrement);
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
        state: 'WAITING',
        message: 'setTalkingTime',
        villageId: villageId,
        time: talkingTime
    };
    sendMessage(messageArray);
}

//「ゲーム開始」をクリック
function clickGameStart() {
    console.log('ENTER: clickGameStart');
    if (numberOfPlayer <= 2) {
        alert('ゲームを始めるには3人以上必要です');
    }
    else if (numberOfLeft >= 1) {
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
            state: 'WAITING',
            message: 'gameStart',
            villageId: villageId,
            id: id
        };
        sendMessage(messageArray);
    }
}

//「戻る」をクリック
function clickBackInWaiting() {
    console.log('ENTER: clickBackInWaiting');
    document.getElementById('btn_backInWaiting').disabled = true;
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: 'WAITING',
        message: 'back',
        villageId: villageId,
        attribute: attribute,
        id: id
    };
    sendMessage(messageArray);
}


////Action////
//「OK」をクリック
function clickOK() {
    console.log('ENTER: clickOK');
    document.getElementById('btn_notification').disabled = false;
}

//選択プレイヤーをクリック
function clickSelectionInAction(selectionId) {
    console.log('ENTER: clickSelectionInAction, selectionId: ' + selectionId);
    var buttonId;
    if (this.selectionId != -2) {
        buttonId = 'btn_selectionInAction' + this.selectionId;
        notSelectButton(buttonId);
    }
    this.selectionId = selectionId;
    buttonId = 'btn_selectionInAction' + this.selectionId;
    selectButton(buttonId);
    document.getElementById('btn_notification').disabled = false;
}

//「次へ」をクリック
function clickNotification() {
    console.log('ENTER: clickNotification');
    if (((position == 'FORTUNETELLER') || (position == 'THIEF')) && selectionId == -2) {
        alert('プレイヤーを選択してください');
    }
    else {
        document.getElementById('btn_notification').disabled = true;
        //サーバに送信
        var messageArray = {
            type: 'system',
            state: 'ACTION',
            message: 'notification',
            villageId: villageId,
            id: id,
            selectionId: selectionId
        };
        sendMessage(messageArray);
    }
}


////Notification////
//「昼のフェーズへ」をクリック
function clickDaytime() {
    console.log('ENTER: clickDaytime');
    document.getElementById('btn_daytime').disabled = true;
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: 'NOTIFICATION',
        message: 'daytime',
        villageId: villageId,
        id: id
    };
    sendMessage(messageArray);
}


////Daytime////
//「延長」をクリック
function clickExtension() {
    console.log('ENTER: clickExtension');
    document.getElementById('btn_extension').disabled = true;
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: 'DAYTIME',
        message: 'extension',
        villageId: villageId,
        id: id
    };
    sendMessage(messageArray);
}

//「話し合い終了」をクリック
function clickTalksEnd() {
    console.log('ENTER: clickTalksEnd');
    document.getElementById('btn_talksEnd').disabled = true;
    clearInterval(timer);
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: 'DAYTIME',
        message: 'talksEnd',
        villageId: villageId,
        id: id
    };
    sendMessage(messageArray);
}

//「役職確認」をクリック
function clickConfirmation() {
    console.log('ENTER: clickConfirmation');
    var popupString = '役職確認\n';
    for (var i = 0; i < playerArray.length; i++) {
        var positionString = getPositionNameInJapanese(playerArray[i].position);
        popupString += playerArray[i].name + 'の役職は' + positionString + 'です\n';
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
            popupString += thiefName + 'は役職を交換しませんでした\n';
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
    console.log('ENTER: clickSelectionInExecution, selectionId: ' + selectionId);
    var buttonId;
    if (this.selectionId != -2) {
        buttonId = 'btn_selectionInExecution' + this.selectionId;
        notSelectButton(buttonId);
    }
    this.selectionId = selectionId;
    buttonId = 'btn_selectionInExecution' + this.selectionId;
    selectButton(buttonId);
    document.getElementById('btn_result').disabled = false;
}

//「結果発表へ」をクリック
function clickResult() {
    console.log('ENTER: clickResult');
    if (selectionId == -2) {
        alert('吊るプレイヤーを選択してください');
    }
    else {
        document.getElementById('btn_result').disabled = true;
        var buttons = document.getElementById('box_selectionInExecution').childNodes;
        for (var i = 0; i < buttons.length; i+=2) {
            buttons[i].disabled = true;
        }
        //サーバに送信
        var messageArray = {
            type: 'system',
            state: 'EXECUTION',
            message: 'result',
            villageId: villageId,
            id: id,
            hangingId: selectionId
        };
        sendMessage(messageArray);
    }
}


////Result////
//「次の夜へ」をクリック
function clickNextNight() {
    console.log('ENTER: clickNextNight');
    document.getElementById('btn_nextNight').disabled = true;
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: 'RESULT',
        message: 'nextNight',
        villageId: villageId
    };
    sendMessage(messageArray);
}

//「終了」をクリック
function clickExitInResult() {
    console.log('ENTER: clickExitInResult');
    document.getElementById('btn_exitInResult').disabled = true;
    //サーバに送信
    var messageArray = {
        type: 'system',
        state: 'RESULT',
        message: 'exit',
        villageId: villageId
    };
    sendMessage(messageArray);
}



//サーバからの通信関数
////Connection////
//データを要求
function queryData() {
    console.log('ENTER: queryData');
    //ローカルストレージから取得
    var data = window.localStorage.getItem("data");
    if (data != null) {
        var dataArray = JSON.parse(data);
        villageId = dataArray['villageId'];
        attribute = dataArray['attribute'];
        id = dataArray['id'];
        //サーバに送信
        var messageArray = {
            type: 'system',
            state: 'CONNECTION',
            message: 'reply',
            villageId: villageId,
            attribute: attribute,
            id: id
        };
        sendMessage(messageArray);
    }
    else {
        //サーバに送信
        var messageArray = {
            type: 'system',
            state: 'CONNECTION',
            message: 'none'
        };
        sendMessage(messageArray);
    }
}

//データを削除
function deleteData() {
    console.log('ENTER: deleteData');
    //ローカルストレージから削除
    var data = window.localStorage.getItem("data");
    if (data != null) {
        window.localStorage.removeItem("data");
    }
}

////Top////
//トップ画面を表示
function displayTop() {
    console.log('ENTER: displayTop');
    document.getElementById('btn_lobby').disabled = false;
    document.getElementById('btn_making').disabled = false;
    displayState('TOP');
}


////Making////
//村作成画面を表示
function displayMaking() {
    console.log('ENTER: displayMaking');
    spectatorFlag = true;
    selectButton('btn_spectatorYes');
    notSelectButton('btn_spectatorNo');
    document.getElementById('btn_decideInMaking').disabled = false;
    document.getElementById('btn_backInMaking').disabled = false;
    displayState('MAKING');
}

//村名重複により拒否
function rejectVillageName() {
    console.log('ENTER: rejectVillageName');
    alert('同じ名前の村が既に存在しています\n別の名前で作り直してください');
    document.getElementById('txt_villageName').value = '';
    document.getElementById('btn_decideInMaking').disabled = false;
}


////Lobby////
//村一覧画面を表示
function displayLobby() {
    console.log('ENTER: displayLobby');
    selectedVillageId = -1;
    document.getElementById('box_villageList').textContent = null;
    document.getElementById('btn_updateInLobby').disabled = false;
    document.getElementById('btn_decideInLobby').disabled = true;
    document.getElementById('btn_backInLobby').disabled = false;
    displayState('LOBBY');
}

//村を追加
function addVillage(messageArray) {
    console.log('ENTER: addVillage, messageArray: ' + JSON.stringify(messageArray));
    villageId = messageArray['villageId'];
    villageName = messageArray['villageName'];
    var passwordFlag = messageArray['passwordFlag'];
    var box = document.getElementById('box_villageList');
    var element = document.createElement('input');
    element.id = 'btn_village' + villageId;
    element.type = 'button';
    element.value = villageName;
    var id = villageId;
    element.addEventListener('click', function(){clickSelectionInLobby(id, passwordFlag)}, false);
    box.appendChild(element);
    box.appendChild(document.createElement('br'));
    document.getElementById('btn_updateInLobby').disabled = false;
}

//参加できる村がない
function notExitVillage() {
    console.log('ENTER: notExitVillage');
    document.getElementById('btn_updateInLobby').disabled = false;
}

//村が既に削除されていることにより拒否
function deleteVillage() {
    console.log('ENTER: deleteVillage');
    alert('選択した村は既に廃村になっています');
    document.getElementById('btn_decideInLobby').disabled = false;
}

//パスワードが違うことにより拒否
function rejectPassword() {
    console.log('ENTER: rejectPassword');
    alert('パスワードが間違っています');
    document.getElementById('btn_decideInLobby').disabled = false;
}


////Participation////
//村参加画面を表示
function displayParticipation(messageArray) {
    console.log('ENTER: displayParticipation, messageArray: ' + JSON.stringify(messageArray));
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
    displayState('PARTICIPATION');
}

//参加者名重複により拒否
function rejectName() {
    console.log('ENTER: rejectName');
    alert('同じ名前の参加者がいます\n違う名前で参加してください');
    if (spectatorFlag == true) {
        document.getElementById('btn_participationAsSpectator').disabled = false;
    }
    else {
        document.getElementById('btn_participationAsSpectator').disabled = true;
    }
    document.getElementById('btn_participationAsPlayer').disabled = false;
}

//プレイヤー人数超過により拒否
function exceedNumberOfPlayer() {
    console.log('ENTER: exceedNumberOfPlayer');
    alert('この村のプレイヤーが制限人数の7人に達しています\n村をもう1つ作って遊ぶことをおすすめします');
    displayTop();
}

//ゲームが既に開始していることにより拒否
function alreadyStarted() {
    console.log('ENTER: alreadyStarted');
    alert('既にゲームが開始しています');
    displayTop();
}


////Waiting////
//初期化
function initInWaiting(messageArray) {
    console.log('ENTER: initInWaiting, messageArray: ' + JSON.stringify(messageArray));
    playerArray = [];
    numberOfPlayer = 0;
    numberOfPositionArray = [];
    numberOfLeft = 0;
    talkingTime = 0;
    document.getElementById('box_playerListInWaiting').textContent = null;
    document.getElementById('box_spectatorListInWaiting').textContent = null;
    villageId = messageArray['villageId'];
    attribute = messageArray['attribute'];
    id = messageArray['id'];
    switch (attribute) {
        case 'PLAYER':
            document.getElementById('btn_gameStart').disabled = false;
            break;
        case 'SPECTATOR':
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
    //ローカルストレージに保存
    setStorageData(villageId, attribute, id);
}

//待機画面を表示
function displayWaiting() {
    console.log('ENTER: displayWaiting');
    displayState('WAITING');
}

//参加者を追加
function addParticipant(messageArray) {
    console.log('ENTER: addParticipant, messageArray: ' + JSON.stringify(messageArray));
    var box;
    var element;
    var attribute = messageArray['attribute'];
    var id = messageArray['id'];
    var name = messageArray['name'];
    switch (attribute) {
        case 'PLAYER':
            var player = {id: id, name: name};
            playerArray.push(player);
            numberOfPlayer++;
            box = document.getElementById('box_playerListInWaiting');
            element = document.createElement('div');
            element.id = 'scrn_playerListInWaiting' + id;
            element.innerHTML = name;
            box.appendChild(element);
            numberOfLeft++;
            document.getElementById('scrn_left').innerHTML = numberOfLeft + '人';
            break;
        case 'SPECTATOR':
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
    console.log('ENTER: delParticipant, messageArray: ' + JSON.stringify(messageArray));
    var box;
    var element;
    var attribute = messageArray['attribute'];
    var id = messageArray['id'];
    switch (attribute) {
        case 'PLAYER':
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
                numberOfLeft--;
                document.getElementById('scrn_left').innerHTML = numberOfLeft + '人';
            }
            break;
        case 'SPECTATOR':
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
    console.log('ENTER: setNumberOfPositionInWaiting, messageArray: ' + JSON.stringify(messageArray));
    var divId;
    var buttonId;
    var position = messageArray['position'];
    var number = messageArray['number'];
    numberOfPositionArray[position] = number;
    var positionString = getPositionNameInEnglish(position);
    divId = 'scrn_numberOf' + positionString + 'InWaiting';
    document.getElementById(divId).innerHTML = number + '人';
    var sum = 0;
    for (var i = 0; i < positionArray.length; i++) {
        sum += numberOfPositionArray[positionArray[i]];
    }
    numberOfLeft = numberOfPlayer + 2 - sum;
    document.getElementById('scrn_left').innerHTML = numberOfLeft + '人';
    if (attribute == 'PLAYER') {
        if (numberOfLeft <= 0) {
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
            if (numberOfPositionArray['THIEF'] >= 1) {
                document.getElementById('btn_incrementOfNumberOfThief').disabled = true;
            }
            else {
                document.getElementById('btn_incrementOfNumberOfThief').disabled = false;
            }
            document.getElementById('btn_incrementOfNumberOfMadman').disabled = false;
            if (numberOfPositionArray['HANGING'] >= 1) {
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
    console.log('ENTER: setTalkingTimeInWaiting, messageArray: ' + JSON.stringify(messageArray));
    var time = messageArray['time'];
    talkingTime = time;
    document.getElementById('scrn_talkingTimeInWaiting').innerHTML = time + ' 分';
    if (attribute == 'PLAYER') {
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
    console.log('ENTER: setGameStart, messageArray: ' + JSON.stringify(messageArray));
    var id = messageArray['id'];
    var elementId = 'scrn_playerListInWaiting' + id;
    selectedElement(elementId);
}


////Action////
//初期化
function initInAction(messageArray) {
    console.log('ENTER: initInAction, messageArray: ' + JSON.stringify(messageArray));
    selectionId = -2;
    document.getElementById('btn_OK').disabled = false;
    document.getElementById('box_selectionInAction').textContent = null;
    villageId = messageArray['villageId'];
    id = messageArray['id'];
    position = messageArray['position'];
    switch (position) {
        case 'VILLAGER':
            document.getElementById('scrn_yourPosition').innerHTML = 'あなたは村人です';
            document.getElementById('box_OK').style.display = 'block';
            document.getElementById('box_selectionInAction').style.display = 'none';
            break;
        case 'WEREWOLF':
            document.getElementById('scrn_yourPosition').innerHTML = 'あなたは人狼です';
            document.getElementById('box_OK').style.display = 'block';
            document.getElementById('box_selectionInAction').style.display = 'none';
            break;
        case 'FORTUNETELLER':
            document.getElementById('scrn_yourPosition').innerHTML = 'あなたは占い師です<br/>占うプレイヤーを選んでください';
            document.getElementById('box_OK').style.display = 'none';
            document.getElementById('box_selectionInAction').style.display = 'block';
            break;
        case 'THIEF':
            document.getElementById('scrn_yourPosition').innerHTML = 'あなたは怪盗です<br/>役職を交換するプレイヤーを選んでください';
            document.getElementById('box_OK').style.display = 'none';
            document.getElementById('box_selectionInAction').style.display = 'block';
            break;
        case 'MADMAN':
            document.getElementById('scrn_yourPosition').innerHTML = 'あなたは狂人です';
            document.getElementById('box_OK').style.display = 'block';
            document.getElementById('box_selectionInAction').style.display = 'none';
            break;
        case 'HANGING':
            document.getElementById('scrn_yourPosition').innerHTML = 'あなたはてるてるです';
            document.getElementById('box_OK').style.display = 'block';
            document.getElementById('box_selectionInAction').style.display = 'none';
            break;
    }
    document.getElementById('btn_notification').disabled = true
    //ローカルストレージに保存
    setStorageData(villageId, attribute, id);
}

//行動画面を表示
function displayAction() {
    console.log('ENTER: displayAction');
    //占い師と怪盗は選択ボタンの最後にボタンを追加
    if (((position == 'FORTUNETELLER') || (position == 'THIEF'))) {
        var box = document.getElementById('box_selectionInAction');
        var element = document.createElement('input');
        var id = -1
        element.id = 'btn_selectionInAction' + id;
        element.type = 'button';
        switch (position) {
            case 'FORTUNETELLER':
                element.value = '場を占う';
                break;
            case 'THIEF':
                element.value = '交換しない';
                break;
        }
        element.addEventListener('click', function(){clickSelectionInAction(id)}, false);
        box.appendChild(element);
        box.appendChild(document.createElement('br'));
    }
    displayState('ACTION');
}

//選択するプレイヤーを追加
function setPlayerInAction(messageArray) {
    console.log('ENTER: setPlayerInAction, messageArray: ' + JSON.stringify(messageArray));
    var id = messageArray['id'];
    var name = messageArray['name'];
    var box = document.getElementById('box_selectionInAction');
    var element = document.createElement('input');
    element.id = 'btn_selectionInAction' + id;
    element.type = 'button';
    element.value = name;
    element.addEventListener('click', function(){clickSelectionInAction(id)}, false);
    box.appendChild(element);
    box.appendChild(document.createElement('br'));
}


////Notification////
//初期化
function initInNotification(messageArray) {
    console.log('ENTER: initInNotification, messageArray: ' + JSON.stringify(messageArray));
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
    console.log('ENTER: displayNotification');
    var resultString = '';
    switch (position) {
        case 'VILLAGER':
            resultString += '仲間の村人と一緒に村を守りましょう';
            break;
        case 'WEREWOLF':
            resultString += '仲間の人狼は<br/>';
            if (buddyNameArray.length >= 1) {
                for (var i = 0; i < buddyNameArray.length; i++) {
                    resultString += '・' + buddyNameArray[i] + '<br/>';
                }
            }
            else {
                resultString += 'いませんでした';
            }
            break;
        case 'FORTUNETELLER':
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
        case 'THIEF':
            if (selectionPosition == -1) {
                resultString += '誰とも役職を交換しませんでした';
            }
            else {
                var positionString = getPositionNameInJapanese(selectionPosition);
                resultString += selectionName + 'の' + positionString + 'と役職を交換しました';
            }
            break;
        case 'MADMAN':
                resultString += '村を混乱に陥れて人狼が有利になるように行動しましょう';
            break;
        case 'HANGING':
                resultString += '村人から吊られるように行動しましょう';
            break;
    }
    document.getElementById('scrn_notification').innerHTML = resultString;
    displayState('NOTIFICATION');
}

//結果を設定
function setResult(messageArray) {
    console.log('ENTER: setResult, messageArray: ' + JSON.stringify(messageArray));
    selectionName = messageArray['name'];
    selectionPosition = messageArray['position'];
}

//場の役職を設定
function setResultOfField(messageArray) {
    console.log('ENTER: setResultOfField, messageArray: ' + JSON.stringify(messageArray));
    position1 = messageArray['position1'];
    position2 = messageArray['position2'];
}

//仲間を設定
function setBuddy(messageArray) {
    console.log('ENTER: setBuddy, messageArray: ' + JSON.stringify(messageArray));
    var name = messageArray['name'];
    buddyNameArray.push(name);
}


////Night////
//初期化
function initInNight() {
    console.log('ENTER: initInNight');
    playerArray = [];
    document.getElementById('box_playerListInNight').textContent = null;
    document.getElementById('box_resultOfFortunetellerInNight').textContent = null;
    document.getElementById('box_resultOfThiefInNight').textContent = null;
    //ローカルストレージに保存
    setStorageData(villageId, attribute, id);
}

//夜の画面を表示
function displayNight() {
    console.log('ENTER: displayNight');
    displayState('NIGHT');
}

//プレイヤーの役職を設定
function setPositionOfPlayerInNight(messageArray) {
    console.log('ENTER: setPositionOfPlayerInNight, messageArray: ' + JSON.stringify(messageArray));
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
    console.log('ENTER: setResultOfFortunetellerInNight, messageArray: ' + JSON.stringify(messageArray));
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
    console.log('ENTER: setResultOfThiefInNight, messageArray: ' + JSON.stringify(messageArray));
    var id = messageArray['id'];
    var selectionId = messageArray['selectionId'];
    var thiefName = getPlayer(id).name;
    var box = document.getElementById('box_resultOfThiefInNight');
    var element = document.createElement('div');
    element.id = 'scrn_resultOfThiefInNight' + id;
    if (selectionId == -1) {
        element.innerHTML = thiefName + 'は役職を交換しませんでした';
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
    console.log('ENTER: initInDaytime, messageArray: ' + JSON.stringify(messageArray));
    playerArray = [];
    numberOfPositionArray = [];
    talkingTime = 3;
    resultOfFortunetellerArray = [];
    resultOfThiefArray = [];
    document.getElementById('scrn_remainingTime').style.display = 'none';
    document.getElementById('box_extension').style.display = 'none';
    document.getElementById('btn_extension').disabled = false;
    document.getElementById('box_playerListInDaytime').textContent = null;
    document.getElementById('box_spectatorListInDaytime').textContent = null;
    villageId = messageArray['villageId'];
    attribute = messageArray['attribute'];
    id = messageArray['id'];
    switch (attribute) {
        case 'PLAYER':
            document.getElementById('btn_talksEnd').disabled = false;
            document.getElementById('btn_confirmation').disabled = true;
            break;
        case 'SPECTATOR':
            document.getElementById('btn_talksEnd').disabled = true;
            document.getElementById('btn_confirmation').disabled = false;
            break;
    }
}

//昼の画面を表示
function displayDaytime() {
    console.log('ENTER: displayDaytime');
    displayState('DAYTIME');
}

//プレイヤーを設定
function setPlayerInDaytime(messageArray) {
    console.log('ENTER: setPlayerInDaytime, messageArray: ' + JSON.stringify(messageArray));
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
    console.log('ENTER: setSpectatorInDaytime, messageArray: ' + JSON.stringify(messageArray));
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
    console.log('ENTER: setNumberOfPositionInDaytime, messageArray: ' + JSON.stringify(messageArray));
    var position = messageArray['position'];
    var number = messageArray['number'];
    numberOfPositionArray[position] = number;
    var positionString = getPositionNameInEnglish(position);
    var elementId = 'scrn_numberOf' + positionString + 'InDaytime';
    document.getElementById(elementId).innerHTML = number + '人';
}

//話し合い時間を設定
function setRemainingTimeInDaytime(messageArray) {
    console.log('ENTER: setRemainingTimeInDaytime, messageArray: ' + JSON.stringify(messageArray));
    var time = messageArray['time'];
    remaingTime = time;
    timer = setInterval(updateTimer, 1000);
}

//「話し合い終了」をクリックしたプレイヤーがいた
function setTalksEnd(messageArray) {
    console.log('ENTER: setTalksEnd, messageArray: ' + JSON.stringify(messageArray));
    var id = messageArray['id'];
    var elementId = 'scrn_playerListInDaytime' + id;
    selectedElement(elementId);
}

//プレイヤーの役職を設定
function setPositionOfPlayerInDaytime(messageArray) {
    console.log('ENTER: setPositionOfPlayerInDaytime, messageArray: ' + JSON.stringify(messageArray));
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
    console.log('ENTER: setResultOfFortunetellerInDaytime, messageArray: ' + JSON.stringify(messageArray));
    var id = messageArray['id'];
    var selectionId = messageArray['selectionId'];
    var array = {id: id, selectionId: selectionId};
    resultOfFortunetellerArray.push(array);
}

//交換結果を設定
function setResultOfThiefInDaytime(messageArray) {
    console.log('ENTER: setResultOfThiefInDaytime, messageArray: ' + JSON.stringify(messageArray));
    var id = messageArray['id'];
    var selectionId = messageArray['selectionId'];
    var array = {id: id, selectionId: selectionId};
    resultOfThiefArray.push(array);
}


////Execution////
//初期化
function initInExecution(messageArray) {
    console.log('ENTER: initInExecution, messageArray: ' + JSON.stringify(messageArray));
    selectionId = -2;
    document.getElementById('box_selectionInExecution').textContent = null;
    villageId = messageArray['villageId'];
    attribute = messageArray['attribute'];
    id = messageArray['id'];
    switch (attribute) {
        case 'PLAYER':
            document.getElementById('scrn_execution').innerHTML = '吊る人を選択してください';
            document.getElementById('btn_result').disabled = false;
            break;
        case 'SPECTATOR':
            document.getElementById('scrn_execution').innerHTML = 'プレイヤーが吊る人を選択しています';
            document.getElementById('btn_result').disabled = true;
            break;
    }

}

//吊る人選択画面を表示
function displayExecution() {
    console.log('ENTER: displayExecution');
    displayState('EXECUTION');
}

//選択するプレイヤーを設定
function setPlayerInExecution(messageArray) {
    console.log('ENTER: setPlayerInExecution, messageArray: ' + JSON.stringify(messageArray));
    var id = messageArray['id'];
    var name = messageArray['name'];
    var box = document.getElementById('box_selectionInExecution');
    var element = document.createElement('input');
    element.id = 'btn_selectionInExecution' + id;
    element.type = 'button';
    element.value = name;
    element.addEventListener('click', function(){clickSelectionInExecution(id)}, false);
    box.appendChild(element);
    box.appendChild(document.createElement('br'));
}


////Result////
//初期化
function initInResult(messageArray) {
    console.log('ENTER: initInResult, messageArray: ' + JSON.stringify(messageArray));
    playerArray = [];
    resultOfFortunetellerArray = [];
    resultOfThiefArray = [];
    document.getElementById('box_playerListInResult').textContent = null;
    document.getElementById('box_resultOfFortunetellerInResult').textContent = null;
    document.getElementById('box_resultOfThiefInResult').textContent = null;
    villageId = messageArray['villageId'];
    attribute = messageArray['attribute'];
    id = messageArray['id'];
    side = messageArray['side'];
    var sideString = '';
    switch (side) {
        case 'VILLAGER':
            sideString = '勝者は村人サイドです';
            break;
        case 'WEREWOLF':
            sideString = '勝者は人狼サイドです';
            break;
        case 'HANGING':
            sideString = '勝者はてるてるサイドです';
            break;
        case 'PEACE':
            sideString = '平和村です';
            break;
    }
    document.getElementById('scrn_winnerSide').innerHTML = sideString;
    switch (attribute) {
        case 'PLAYER':
            document.getElementById('btn_nextNight').disabled = false;
            document.getElementById('scrn_winnerOrLoser').style.display = 'block';
            break;
        case 'SPECTATOR':
            document.getElementById('btn_nextNight').disabled = true;
            document.getElementById('scrn_winnerOrLoser').style.display = 'none';
            break;
    }
    document.getElementById('btn_exitInResult').disabled = false;
}

//結果発表画面を表示
function displayResult() {
    console.log('ENTER: displayResult');
    for (var i = 0; i < playerArray.length; i++) {
        var positionString = getPositionNameInJapanese(playerArray[i].position);
        var hangingName = getPlayer(playerArray[i].hangingId).name;
        var box = document.getElementById('box_playerListInResult');
        var element = document.createElement('div');
        element.id = 'scrn_playerListInResult' + playerArray[i].id;
        element.innerHTML = '名前: '+ playerArray[i].name + '<br/>役職: ' + positionString + ', 吊った相手: ' + hangingName + ', ポイント: ' + playerArray[i].point;
        if (playerArray[i].id == this.id) {
            element.style.background = 'skyblue';
        }
        box.appendChild(element);
    }
    displayState('RESULT');
}

//勝ち負けを設定
function setWinnerOrLoser(messageArray) {
    console.log('ENTER: setWinnerOrLoser, messageArray: ' + JSON.stringify(messageArray));
    var winnerOrLoser = messageArray['winnerOrLoser'];
    var winnerOrLoserString = '';
    if (winnerOrLoser == true) {
        winnerOrLoserString = 'あなたの勝ちです';
    }
    else {
        winnerOrLoserString = 'あなたの負けです';
    }
    document.getElementById('scrn_winnerOrLoser').innerHTML = winnerOrLoserString;
}

//プレイヤーの結果を設定
function setResultOfPlayerInResult(messageArray) {
    console.log('ENTER: setResultOfPlayerInResult, messageArray: ' + JSON.stringify(messageArray));
    var id = messageArray['id'];
    var name = messageArray['name'];
    var position = messageArray['position'];
    var hangingId = messageArray['hangingId'];
    var point = messageArray['point'];
    var player = {id: id, name: name, position: position, hangingId: hangingId, point: point};
    playerArray.push(player);
}

//占い結果を表示
function setResultOfFortunetellerInResult(messageArray) {
    console.log('ENTER: setResultOfFortunetellerInResult, messageArray: ' + JSON.stringify(messageArray));
    var id = messageArray['id'];
    var selectionId = messageArray['selectionId'];
    var fortunetellerName = getPlayer(id).name;
    var box = document.getElementById('box_resultOfFortunetellerInResult');
    var element = document.createElement('div');
    element.id = 'scrn_resultOfFortunetellerInResult' + id;
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
function setResultOfThiefInResult(messageArray) {
    console.log('ENTER: setResultOfThiefInResult, messageArray: ' + JSON.stringify(messageArray));
    var id = messageArray['id'];
    var selectionId = messageArray['selectionId'];
    var thiefName = getPlayer(id).name;
    var box = document.getElementById('box_resultOfThiefInResult');
    var element = document.createElement('div');
    element.id = 'scrn_resultOfThiefInResult' + id;
    if (selectionId == -1) {
        element.innerHTML = thiefName + 'は役職を交換しませんでした';
    }
    else {
        selectionName = getPlayer(selectionId).name;
        element.innerHTML = thiefName + 'は' + selectionName + 'と役職を交換しました';
    }
    box.appendChild(element);
}
