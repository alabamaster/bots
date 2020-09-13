<?php 
if (!isset($_REQUEST)) die('error #1');

// ключ доступа (https://vk.com/юрлгруппы?act=tokens)
define('GROUP_ACCES_KEY', 'qwerty');

// Callback API, Секретный ключ (https://vk.com/юрлгруппы?act=api)
define('CALLBACK_API_SECRET_KEY', 'qwerty');

// Строка, которую должен вернуть сервер (https://vk.com/юрлгруппы?act=api)
define('STR_RESPONSE', 'qwerty');

// ip сервера
define('SERVER_IP', '127.0.0.1:27015');

// показывать следующую карту. должен быть amxmodx. 1/0
define('NEXTMAP', 1);

$info_arr = array('!инфо', '!инфа', '!информация', '!сервер');
$players_arr = array('!игроки', '!игрок', '!онлайн');

// не трогать
$data = json_decode(file_get_contents('php://input'));

if ( !$data ) die('sorry');
if ( $data->secret !== CALLBACK_API_SECRET_KEY && $data->type !== 'confirmation' ) die('sorry');

// GameQ
require_once('GameQ/Autoloader.php');
$GameQ = new \GameQ\GameQ();

function vk_msg_send($uid, $msg) {
	$request_params = array(
        'message' => $msg,
        'user_id' => $uid,
        'access_token' => GROUP_ACCES_KEY,
		'read_state' => 1,
		'v' => '5.122',
		'random_id' => '0'
    );
    $get_params = http_build_query($request_params);
    
    file_get_contents('https://api.vk.com/method/messages.send?' . $get_params);
}

function serverStatus($GameQ)
{
	$GameQ->addServer([
		'type' => 'cs16',
		'host' => SERVER_IP,
	]);
	$results = $GameQ->process();
	$result = $results[SERVER_IP];
	$nextmap = '';

	if ( NEXTMAP == 1 ) {
		$nextmap .= '<br>🔪 Следующай карта: ' . $result['amx_nextmap'];
	}

	return '
		❤️ Сервер: '.$result['hostname'].'
		🔪 Текущая карта: '.$result['map'].' '.$nextmap.'
		💀 Игроки: '.$result['gq_numplayers'].'/'.$result['gq_maxplayers'].'
		🕹️ IP сервера: '.SERVER_IP;
}

function serverPlayers($GameQ)
{
	$GameQ->addServer([
		'type' => 'cs16',
		'host' => SERVER_IP,
	]);
	$results = $GameQ->process();
	$result = $results[SERVER_IP];
	$htmlMessage = '';
	$id = 0;

	if( empty($result['players']) ) return 'Сервер пустой :(';

	foreach ($result['players'] as $row) {
		$id++;
		$htmlMessage .= $id .': '. $row['gq_name'].', фраги: '.$row['gq_score'].'<br>';
	}

	return $htmlMessage;
}

switch ( $data->type ) {
	case 'confirmation':
		exit(STR_RESPONSE);
	break;
	
	case 'message_new': 
		$uid = $data->object->message->from_id;
		$msgText = $data->object->message->text;

		// инфо о сервере
		if ( in_array($msgText, $info_arr) ) {
			vk_msg_send($uid, serverStatus($GameQ));
		}

		// инфо о игроках
		if ( in_array($msgText, $players_arr) ) {
			vk_msg_send($uid, serverPlayers($GameQ));
		}
		echo 'ok';
	break;

	default:
		echo 'ok';
	break;
}
