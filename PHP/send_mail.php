<?php

/* == CONFIGURAÇÃO == */

/* SMTP
SMTP_HOST é o hostname do servidor SMTP.
SMTP_PORT é a porta usada para conexão. Atenção: Google Cloud proibe portas 587, 25 e 465, então usar outra porta como a 2525.
SMTP_USERNAME é o usuário ou e-mail usado no servidor SMTP.
SMTP_PASSWORD é a senha do servidor SMTP.
SMTP_TIMEOUT é o tempo limite para conexão do servidor SMTP (em segundos).
SMTP_DEBUG indica se deve mostrar algumas informações a mais de erro ou não.
*/
const SMTP_HOST = 'smtp.gmail.com'; //string
const SMTP_PORT = '587'; //string
const SMTP_USERNAME = 'EMAIL DO GANESH'; //string
const SMTP_PASSWORD = 'SENHA DO EMAIL DO GANESH'; //string
const SMTP_TIMEOUT = 30; //int
const SMTP_DEBUG = FALSE; // bool

/* Captcha anti-bot
Preencha com a chave secreta fornecida pela API do Google Recaptcha.
Deixe uma string vazia ("") para desativar (não recomendado).
*/
const RECAPTCHA_KEY = "INSERIR AQUI A CHAVE SECRETA DO GOOGLE RECAPTCHA"; // string

/* Mensagem
MAIL_FROM é o remetente da mensagem (o texto {FROM} será substituído pelo e-mail do usuário).
MAIL_TO é o destinatário da mensagem (o texto {FROM} será substituído pelo e-mail do usuário).
MAIL_REPLYTO é o endereço de resposta da mensagem (o texto {FROM} será substituído pelo e-mail do usuário). Se for uma string vazia (""), será o mesmo de MAIL_FROM.
MAIL_SUBJECT é o assunto da mensagem (o texto {FROM} e {SUBJECT} será substituído respectivamente pelo e-mail do usuário e assunto da mensagem).
MAIL_BODY é o corpo da mensagem (o texto {MSG}, {FROM} e {SUBJECT} será substituído respectivamente pelo conteúdo digitado pelo usuário, o e-mail do usuário e assunto da mensagem).
MAIL_HTML indica se deve ou não usar HTML na mensagem. Se HTML for desativado, tudo será enviado como texto plano, caso contrário, o corpo da mensagem será interpretado como HTML.
*/
const MAIL_FROM = 'EMAIL DO GANESH'; // string
const MAIL_TO = 'EMAIL DO GANESH'; // string
const MAIL_REPLYTO = '{FROM}'; // string
const MAIL_SUBJECT = '[Website] {SUBJECT}'; // string
const MAIL_BODY = 'Olá. O Ganesh recebeu uma nova mensagem pelo website.' . PHP_EOL . PHP_EOL . 'De: {FROM}' . PHP_EOL . 'Assunto: {SUBJECT}' . PHP_EOL . 'Mensagem:' . PHP_EOL . PHP_EOL . '{MSG}'; // string
const MAIL_HTML = FALSE; // bool

/* Comprimentos máximos
MAX_FROM é o comprimento máximo do e-mail do usuário.
MAX_SUBJECT é o comprimento máximo do assunto da mensagem do usuário.
MAX_MSG é o comprimento máximo da mensagem do usuário.
*/
const MAX_FROM = 128; // int
const MAX_SUBJECT = 128; // int
const MAX_MSG = 1024*256; // int



/* INÍCIO DO CÓDIGO */

require_once("server/PHPMailer-master/PHPMailerAutoload.php");
//$json = ($_GET["json"] == "true") ? TRUE:FALSE;

function checkRecaptcha($secretKey, $captcha) {
	if(empty($secretKey) || gettype($secretKey) !== "string" || empty($captcha) || gettype($captcha) !== "string") {
		return FALSE;
	}
	$requestData = ["secret" => $secretKey, "response" => $captcha, "remoteip" => $_SERVER["REMOTE_ADDR"]];
	$requestOptions = ["http" => ["header" => "Content-type: application/x-www-form-urlencoded\r\n", "method" => "POST", "content" => http_build_query($requestData)]];
	$requestStreamContext = stream_context_create($requestOptions);
	$requestResults = file_get_contents("https://www.google.com/recaptcha/api/siteverify", FALSE, $requestStreamContext);
	if($requestResults === FALSE || empty($requestResults = json_decode($requestResults, TRUE)) || $requestResults["success"] !== TRUE) {
		return FALSE;
	}
	return TRUE;
}

$result = ["status" => "success"];
$nonce = $_POST["nonce"];
$captcha = $_POST["captcha"];
$recaptcha = $_POST["g-recaptcha-response"];
$from = $_POST["from"];
$subject = $_POST["subject"];
$msg = $_POST["msg"];
if(RECAPTCHA_KEY !== "" && !checkRecaptcha(RECAPTCHA_KEY, $recaptcha)) {
	$result["status"] = "error";
	$result["error"] = "invalid-captcha";
	$result["error-msg"] = "Por favor, confirme que você não é um robô.";
	echo json_encode($result);
	exit(0);
} else if(empty($from) || strlen($from) > MAX_FROM || filter_var($from, FILTER_VALIDATE_EMAIL) === FALSE) {
	$result["status"] = "error";
	$result["error"] = "invalid-mail";
	$result["error-msg"] = "Digite um e-mail válido (1-" . MAX_FROM . " caracteres).";
	echo json_encode($result);
	exit(0);
} else if(empty($subject) || strlen($subject) > MAX_SUBJECT) {
	$result["status"] = "error";
	$result["error"] = "invalid-subject";
	$result["error-msg"] = "Digite um assunto válido (1-" . MAX_SUBJECT . " caracteres).";
	echo json_encode($result);
	exit(0);
} else if(empty($msg) || strlen($msg) > MAX_MSG) {
	$result["status"] = "error";
	$result["error"] = "invalid-msg";
	$result["error-msg"] = "Digite uma mensagem válida (1-" . MAX_MSG . " caracteres).";
	echo json_encode($result);
	exit(0);
} else if(preg_match("/{(FROM|SUBJECT|MSG)}/i", $from) === 1 || preg_match("/{(FROM|SUBJECT|MSG)}/i", $subject) === 1 || preg_match("/{(FROM|SUBJECT|MSG)}/i", $msg) === 1) {
	$result["status"] = "error";
	$result["error"] = "invalid-substr";
	$result["error-msg"] = "Não é permitido inserir {FROM}, {SUBJECT} ou {MSG} na sua mensagem. Remova isso e tente novamente.";
	echo json_encode($result);
	exit(0);
}
$mailFrom = MAIL_FROM;
$mailTo = MAIL_TO;
$mailReplyTo = MAIL_REPLYTO;
$mailSubject = MAIL_SUBJECT;
$mailBody = MAIL_BODY;
$mailFrom = str_replace("{FROM}", $from, $mailFrom);
$mailTo = str_replace("{FROM}", $from, $mailTo);
$mailReplyTo = str_replace("{FROM}", $from, $mailReplyTo);
$mailSubject = str_replace("{SUBJECT}", $subject, str_replace("{FROM}", $from, $mailSubject));
$mail = new PHPMailer();
$mail->IsSMTP();
$mail->SMTPAuth = true;
$mail->CharSet = "UTF-8";
// $mail->SMTPSecure = ENCRYPTION_STARTTLS;
$mail->SMTPSecure = "tls";
$mail->SMTPAutoTLS = true;
$mail->Host = SMTP_HOST;
$mail->Port = SMTP_PORT;
$mail->Username = SMTP_USERNAME;
$mail->Password = SMTP_PASSWORD;
$mail->SetFrom($mailFrom/*, $name*/);
$mail->AddAddress($mailTo/*, $name*/);
$mail->AddReplyTo($mailReplyTo/*, $name*/);
// $mail->AddAttachment($mailAttachment/*, $name*/);
$mail->Subject = $mailSubject;
$mail->IsHTML(MAIL_HTML);
$mail->Timeout = SMTP_TIMEOUT;
if(MAIL_HTML){
	$mailBody = str_replace("{MSG}", $msg, str_replace("{SUBJECT}", htmlentities($subject, ENT_QUOTES|ENT_SUBSTITUTE), str_replace("{FROM}", htmlentities($from, ENT_QUOTES|ENT_SUBSTITUTE), $mailBody)));
	$mail->Body = '<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,height=device-height,initial-scale=1,user-scalable=no"></head><body>' . $mailBody . '</body></html>';
	$mail->AltBody = strip_tags($mailBody);
} else {
	$mailBody = str_replace("{MSG}", $msg, str_replace("{SUBJECT}", $subject, str_replace("{FROM}", $from, $mailBody)));
	$mail->Body = $mailBody;
}
if($mail->Send()){
	echo json_encode($result);
}else{
	$result["status"] = "error";
	$result["error"] = "send-error";
	if(SMTP_DEBUG) {
		$result["error-msg"] = "Não foi possível enviar a mensagem. Tente novamente. Erro: " . htmlentities($mail->ErrorInfo, ENT_QUOTES|ENT_SUBSTITUTE);
	} else {
		$result["error-msg"] = "Não foi possível enviar a mensagem. Tente novamente.";
	}
	echo json_encode($result);
}

