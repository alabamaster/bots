<?php 
if ( $_SERVER['REQUEST_METHOD'] != 'POST' ) die('Bad method');

// ключ доступа (https://vk.com/юрлгруппы?act=tokens)
define('GROUP_ACCES_KEY', 'qwerty');

// Callback API, Секретный ключ (https://vk.com/юрлгруппы?act=api)
define('CALLBACK_API_SECRET_KEY', 'qwerty');

// версия vk api
define('API_V', '5.124');

// Строка, которую должен вернуть сервер (https://vk.com/юрлгруппы?act=api)
define('STR_RESPONSE', 'qwerty');

// ip/домен сервера / 82.202.173.35:27015
define('SERVER_IP', '127.0.0.1:27015');

// 1 - use curl, 0 - file_get_contents
define('USE_CURL', 1);

// показывать следующую карту. должен быть amxmodx. 1/0
define('NEXTMAP', 1);

$info_arr = array('!инфо', '!инфа', '!информация', '!сервер');
$players_arr = array('!игроки', '!игрок', '!онлайн');

$data = json_decode(file_get_contents('php://input'));

if ( !$data || !isset($data->type) ) die('Error #1 - ' . json_last_error());
if ( $data->secret !== CALLBACK_API_SECRET_KEY && $data->type !== 'confirmation' ) die('Error #2');

// GameQ
require_once('GameQ/Autoloader.php');
$GameQ = new \GameQ\GameQ();

function vk_msg_send($peer_id, $msg) {
	$request_params = array(
		'message' => $msg,
		$peer_id['type'] => $peer_id['value'],
		'access_token' => GROUP_ACCES_KEY,
		'read_state' => 1,
		'random_id' => rand(1337, 999999999999),
		'v' => API_V
	);
	
	$get_params = http_build_query($request_params);
	
	if ( USE_CURL == 1 ) {
		$ch = curl_init('https://api.vk.com/method/messages.send?' . $get_params);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		$response = curl_exec( $ch );
		return curl_close( $ch );
	} else {
		return file_get_contents('https://api.vk.com/method/messages.send?' . $get_params);
	}
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
		$msgText = $data->object->message->text;

		if ( $data->object->message->id == 0 ) {
			$peer_id['type'] = 'peer_id';
			$peer_id['value'] = $data->object->message->peer_id;
		} else {
			$peer_id['type'] = 'user_id';
			$peer_id['value'] = $data->object->message->from_id;
		}

		if ( in_array($msgText, $info_arr) ) 
		{
			vk_msg_send($peer_id, serverStatus($GameQ));
		} 
		else if ( in_array($msgText, $players_arr) ) 
		{
			vk_msg_send($peer_id, serverPlayers($GameQ));
		}
		echo 'ok';
	break;

	default:
		echo 'ok';
	break;
}
