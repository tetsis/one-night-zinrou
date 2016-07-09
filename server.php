<?php

define('CONNECTION', 0);
define('TOP', 1);
define('MAKING', 2);
define('LOBBY', 3);
define('PARTICIPATION', 4);
define('WAITING', 5);
define('CONFIGURE', 6);
define('ACTION', 7);
define('NOTIFICATION', 8);
define('NIGHT', 9);
define('DAYTIME', 10);
define('EXECUTION', 11);
define('RESULT', 12);

define('VILLAGER', 0);
define('WEREWOLF', 1);
define('FORTUNETELLER', 2);
define('THIEF', 3);
define('MADMAN', 4);
define('HANGING', 5);
define('PEACE', 6);

define('PLAYER', 0);
define('SPECTATOR', 1);

$positionArray = array(VILLAGER, WEREWOLF, FORTUNETELLER, THIEF, MADMAN, HANGING);

require_once('villageManagement.php');
require_once('village.php');
require_once('player.php');
require_once('spectator.php');

$villageManagement = new VillageManagement();

//$host = 'localhost'; //host
$host = 'www.tetsis-net'; //host
$port = '9000'; //port
$null = NULL; //null var

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
            outputLog('RECEIVE '. $received_text);
            $messageArray = json_decode($received_text); //json decode 
            //outputMessage($messageArray, false);
            if ($messageArray !== null) {
                $type = $messageArray->type;
                if ($type == 'system') {
                    $state = $messageArray->state;
                    $message = $messageArray->message;
                    switch ($state) {
                        case TOP:
                            if ($message == 'lobby') {
                                $villageManagement->clickLobby($changedSocket);
                            }
                            else if ($message == 'making') {
                                $villageManagement->clickMaking($changedSocket);
                            }
                            break;
                        case MAKING:
                            if ($message == 'decide') {
                                $villageManagement->clickDecideInMaking($changedSocket, $messageArray);
                            }
                            if ($message == 'back') {
                                $villageManagement->clickBackInMaking($changedSocket);
                            }
                            break;
                        case LOBBY:
                            if ($message == 'update') {
                                $villageManagement->clickUpdate($changedSocket);
                            }
                            else if ($message == 'decide') {
                                $villageManagement->clickDecideInLobby($changedSocket, $messageArray);
                            }
                            else if ($message == 'back') {
                                $villageManagement->clickBackInLobby($changedSocket);
                            }
                            break;
                        case PARTICIPATION:
                            if ($message == 'participateAsPlayer') {
                                $villageId = $messageArray->villageId;
                                $village = $villageManagement->getVillage($villageId);
                                if ($village !== null) {
                                    $village->clickParticipationAsPlayer($changedSocket, $messageArray);
                                }
                            }
                            else if ($message == 'participateAsSpectator') {
                                $villageId = $messageArray->villageId;
                                $village = $villageManagement->getVillage($villageId);
                                if ($village !== null) {
                                    $village->clickParticipationAsSpectator($changedSocket, $messageArray);
                                }
                            }
                            else if ($message == 'back') {
                                $villageId = $messageArray->villageId;
                                $village = $villageManagement->getVillage($villageId);
                                if ($village !== null) {
                                    $villageManagement->clickBackInParticipation($changedSocket, $messageArray);
                                }
                            }
                            break;
                        case WAITING:
                            if ($message == 'setNumberOfPosition') {
                                $villageId = $messageArray->villageId;
                                $village = $villageManagement->getVillage($villageId);
                                if ($village !== null) {
                                    $village->clickNumberOfPosition($messageArray);
                                }
                            }
                            else if ($message == 'setTalkingTime') {
                                $villageId = $messageArray->villageId;
                                $village = $villageManagement->getVillage($villageId);
                                if ($village !== null) {
                                    $village->clickTalkingTime($messageArray);
                                }
                            }
                            else if ($message == 'gameStart') {
                                $villageId = $messageArray->villageId;
                                $village = $villageManagement->getVillage($villageId);
                                if ($village !== null) {
                                    $village->clickGameStart($messageArray);
                                }
                            }
                            else if ($message == 'back') {
                                $villageId = $messageArray->villageId;
                                $village = $villageManagement->getVillage($villageId);
                                if ($village !== null) {
                                    $villageManagement->clickBackInWaiting($changedSocket, $messageArray);
                                }
                            }
                            break;
                        case ACTION:
                            if ($message == 'notification') {
                                $villageId = $messageArray->villageId;
                                $village = $villageManagement->getVillage($villageId);
                                if ($village !== null) {
                                    $village->clickNotification($messageArray);
                                }
                            }
                            break;
                        case NOTIFICATION:
                            if ($message == 'daytime') {
                                $villageId = $messageArray->villageId;
                                $village = $villageManagement->getVillage($villageId);
                                if ($village !== null) {
                                    $village->clickDaytime($messageArray);
                                }
                            }
                            break;
                        case DAYTIME:
                            if ($message == 'extension') {
                                $villageId = $messageArray->villageId;
                                $village = $villageManagement->getVillage($villageId);
                                if ($village !== null) {
                                    $village->clickExtension($messageArray);
                                }
                            }
                            else if ($message == 'talksEnd') {
                                $villageId = $messageArray->villageId;
                                $village = $villageManagement->getVillage($villageId);
                                if ($village !== null) {
                                    $village->clickTalksEnd($messageArray);
                                }
                            }
                            break;
                        case EXECUTION:
                            if ($message == 'result') {
                                $villageId = $messageArray->villageId;
                                $village = $villageManagement->getVillage($villageId);
                                if ($village !== null) {
                                    $village->clickResult($messageArray);
                                }
                            }
                            break;
                        case RESULT:
                            if ($message == 'nextNight') {
                                $villageId = $messageArray->villageId;
                                $village = $villageManagement->getVillage($villageId);
                                if ($village !== null) {
                                    $village->clickNextNight($messageArray);
                                }
                            }
                            else if ($message == 'exit') {
                                $villageId = $messageArray->villageId;
                                $village = $villageManagement->getVillage($villageId);
                                if ($village !== null) {
                                    $village->clickExit($messageArray);
                                }
                            }
                            break;
                        case CONNECTION:
                            if ($message == 'reply') {
                                $villageManagement->replyData($changedSocket, $messageArray);
                            }
                            else if ($message == 'none') {
                                $villageManagement->noneData($changedSocket);
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
            foreach ($villageManagement->villageArray as $i) {
                $foundSocket = array_search($changedSocket, $i->participantArray);
                if ($foundSocket != false) {
                    unset($i->participantArray[$foundSocket]);
                    $i->numberOfParticipant--;
                    if ($i->numberOfParticipant == 0) {
                        echo "$villageManagement->villageArray";
                        $foundVillage = array_search($i, $villageManagement->villageArray);
                        unset($villageManagement->villageArray[$foundVillage]);
                        echo "$villageManagement->villageArray";
                    }
                }
            }
            $response = mask(json_encode(array('type'=>'system', 'message'=>$ip.' disconnected')));
            //sendMessage_all($response);
        }
    }
}
// close the listening socket
socket_close($socket);

function sendMessage($msg, $socket)
{
    outputLog('SEND '. $msg);
    $txData = mask($msg);
    @socket_write($socket, $txData, strlen($txData));
    return true;
}

function sendMessage_all($msg)
{
    global $clients;
    foreach($clients as $changedSocket)
    {
        @socket_write($changedSocket,$msg,strlen($msg));
    }
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
        "WebSocket-Location: ws://$host:$port/zinrou/server.php\r\n".
        "Sec-WebSocket-Accept:$secAccept\r\n\r\n";
    socket_write($client_conn,$upgrade,strlen($upgrade));
}

//ログを出力
function outputLog($log) {
    echo "$log\n";
}
