<?php

$positionArray = array('VILLAGER', 'WEREWOLF', 'FORTUNETELLER', 'THIEF', 'MADMAN', 'HANGING');

require_once('villageManagement.php');
require_once('village.php');
require_once('player.php');
require_once('spectator.php');


if ($argc < 2) {
    echo("引数の指定がありません\n");
    exit(1);
}
$host = $argv[1]; //host
$port = '9000'; //port
$null = NULL; //null var

$villageManagement = new VillageManagement();

//Create TCP/IP sream socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
//reuseable port
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

//bind socket to specified host
socket_bind($socket, 0, $port);

//listen to port
socket_listen($socket);

//create & add listning socket to the list
$clients = array($socket);

//start endless loop, so that our script doesn't stop
while (true) {
    //manage multipal connections
    $changed = $clients;
    //returns the socket resources in $changed array
    socket_select($changed, $null, $null, 0, 10);

    //check for new socket
    if (in_array($socket, $changed)) {
        $socket_new = socket_accept($socket); //accpet new socket
        $clients[] = $socket_new; //add socket to client array

        $header = socket_read($socket_new, 1024); //read data sent by the socket
        perform_handshaking($header, $socket_new, $host, $port); //perform websocket handshake

        socket_getpeername($socket_new, $ip); //get ip address of connected socket

        $villageManagement->queryData($socket_new);
        //$response_text = mask(json_encode(array('type'=>'system', 'state'=>INIT, 'message'=>'query')));
        //sendMessage($response_text, $socket_new); //notify all users about new connection

        //make room for new socket
        $found_socket = array_search($socket, $changed);
        unset($changed[$found_socket]);
    }

    //loop through all connected sockets
    foreach ($changed as $changedSocket) {	

        //check for any incomming data
        while(socket_recv($changedSocket, $buf, 1024, 0) >= 1)
        {
            $received_text = unmask($buf); //unmask data
            outputLog('RECEIVE: '. $received_text);
            $messageArray = json_decode($received_text); //json decode 
            if ($messageArray !== null) {
                $type = $messageArray->type;
                if ($type == 'system') {
                    $state = $messageArray->state;
                    $message = $messageArray->message;
                    switch ($state) {
                        case 'TOP':
                            switch ($message) {
                                case 'lobby':
                                    $villageManagement->clickLobby($changedSocket);
                                    break;
                                case 'making':
                                    $villageManagement->clickMaking($changedSocket);
                                    break;
                            }
                            break;
                        case 'MAKING':
                            switch ($message) {
                                case 'decide':
                                    $villageManagement->clickDecideInMaking($changedSocket, $messageArray);
                                    break;
                                case 'back':
                                    $villageManagement->clickBackInMaking($changedSocket);
                                    break;
                            }
                            break;
                        case 'LOBBY':
                            switch ($message) {
                                case 'update':
                                    $villageManagement->clickUpdate($changedSocket);
                                    break;
                                case 'decide':
                                    $villageManagement->clickDecideInLobby($changedSocket, $messageArray);
                                    break;
                                case 'back':
                                    $villageManagement->clickBackInLobby($changedSocket);
                                    break;
                            }
                            break;
                        case 'PARTICIPATION':
                            $villageId = $messageArray->villageId;
                            $village = $villageManagement->getVillage($villageId);
                            if ($village !== null) {
                                switch ($message) {
                                    case 'participateAsPlayer':
                                        $village->clickParticipationAsPlayer($changedSocket, $messageArray);
                                        break;
                                    case 'participateAsSpectator':
                                        $village->clickParticipationAsSpectator($changedSocket, $messageArray);
                                        break;
                                    case 'back':
                                        $villageManagement->clickBackInParticipation($changedSocket, $messageArray);
                                        break;
                                }
                            }
                            break;
                        case 'WAITING':
                            $villageId = $messageArray->villageId;
                            $village = $villageManagement->getVillage($villageId);
                            if ($village !== null) {
                                switch ($message) {
                                    case 'setNumberOfPosition':
                                        $village->clickNumberOfPosition($messageArray);
                                        break;
                                    case 'setTalkingTime':
                                        $village->clickTalkingTime($messageArray);
                                        break;
                                    case 'gameStart':
                                        $village->clickGameStart($messageArray);
                                        break;
                                    case 'back':
                                        $villageManagement->clickBackInWaiting($changedSocket, $messageArray);
                                        break;
                                }
                            }
                            break;
                        case 'ACTION':
                            $villageId = $messageArray->villageId;
                            $village = $villageManagement->getVillage($villageId);
                            if ($village !== null) {
                                switch ($message) {
                                    case 'notification':
                                        $village->clickNotification($messageArray);
                                        break;
                                }
                            }
                            break;
                        case 'NOTIFICATION':
                            $villageId = $messageArray->villageId;
                            $village = $villageManagement->getVillage($villageId);
                            if ($village !== null) {
                                switch ($message) {
                                    case 'daytime':
                                        $village->clickDaytime($messageArray);
                                        break;
                                }
                            }
                            break;
                        case 'DAYTIME':
                            $villageId = $messageArray->villageId;
                            $village = $villageManagement->getVillage($villageId);
                            if ($village !== null) {
                                switch ($message) {
                                    case 'extension':
                                        $village->clickExtension($messageArray);
                                        break;
                                    case 'talksEnd':
                                        $village->clickTalksEnd($messageArray);
                                        break;
                                }
                            }
                            break;
                        case 'EXECUTION':
                            $villageId = $messageArray->villageId;
                            $village = $villageManagement->getVillage($villageId);
                            if ($village !== null) {
                                switch ($message) {
                                    case 'result':
                                        $village->clickResult($messageArray);
                                        break;
                                }
                            }
                            break;
                        case 'RESULT':
                            $villageId = $messageArray->villageId;
                            $village = $villageManagement->getVillage($villageId);
                            if ($village !== null) {
                                switch ($message) {
                                    case 'nextNight':
                                        $village->clickNextNight($messageArray);
                                        break;
                                    case 'exit':
                                        $village->clickExit($messageArray);
                                        break;
                                }
                            }
                            break;
                        case 'CONNECTION':
                            switch ($message) {
                                case 'reply':
                                    $villageManagement->replyData($changedSocket, $messageArray);
                                    break;
                                case 'none':
                                    $villageManagement->noneData($changedSocket);
                                    break;
                            }
                            break;
                    }
                }
            }
            break 2; //exist this loop
        }

        $buf = @socket_read($changedSocket, 1024, PHP_NORMAL_READ);
        if ($buf === false) { // check disconnected client
            // remove client for $clients array
            $found_socket = array_search($changedSocket, $clients);
            socket_getpeername($changedSocket, $ip);
            unset($clients[$found_socket]);

            //notify all users about disconnected connection
            foreach ($villageManagement->getVillageArray() as $i) {
                if ($i->removeParticipant($changedSocket) !== false) {
                    if ($i->getNumberOfParticipant() <= 0) {
                        $villageManagement->removeVillage($i->getId());
                        break;
                    }
                }
            }
        }
    }
}
// close the listening socket
socket_close($socket);

/*
function sendMessage($msg, $socket)
{
    outputLog('SEND '. $msg);
    $txData = mask($msg);
    @socket_write($socket, $txData, strlen($txData));
    return true;
}
*/

function sendMessage($messageArray, $socket)
{
    $message = json_encode($messageArray);
    outputLog('SEND: '. $message);
    $txData = mask($message);
    @socket_write($socket, $txData, strlen($txData));
    return true;
}

//Unmask incoming framed message
function unmask($text) {
    $length = ord($text[1]) & 127;
    if($length == 126) {
        $masks = substr($text, 4, 4);
        $data = substr($text, 8);
    }
    elseif($length == 127) {
        $masks = substr($text, 10, 4);
        $data = substr($text, 14);
    }
    else {
        $masks = substr($text, 2, 4);
        $data = substr($text, 6);
    }
    $text = "";
    for ($i = 0; $i < strlen($data); ++$i) {
        $text .= $data[$i] ^ $masks[$i%4];
    }
    return $text;
}

//Encode message for transfer to client.
function mask($text)
{
    $b1 = 0x80 | (0x1 & 0x0f);
    $length = strlen($text);

    if($length <= 125)
        $header = pack('CC', $b1, $length);
    elseif($length > 125 && $length < 65536)
        $header = pack('CCn', $b1, 126, $length);
    elseif($length >= 65536)
        $header = pack('CCNN', $b1, 127, $length);
    return $header.$text;
}

//handshake new client.
function perform_handshaking($receved_header,$client_conn, $host, $port)
{
    $headers = array();
    $lines = preg_split("/\r\n/", $receved_header);
    foreach($lines as $line)
    {
        $line = chop($line);
        if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
        {
            $headers[$matches[1]] = $matches[2];
        }
    }

    $secKey = $headers['Sec-WebSocket-Key'];
    $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
    //hand shaking header
    $upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
        "Upgrade: websocket\r\n" .
        "Connection: Upgrade\r\n" .
        "WebSocket-Origin: $host\r\n" .
        "WebSocket-Location: ws://$host:$port\r\n".
        "Sec-WebSocket-Accept:$secAccept\r\n\r\n";
    socket_write($client_conn,$upgrade,strlen($upgrade));
}

//ログを出力
function outputLog($log) {
    error_log($log. "\n", 3, '/var/log/zinrou.log');
    //echo "$log\n";
}
