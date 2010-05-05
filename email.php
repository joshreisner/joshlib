<?php
error_debug('including email.php', __file__, __line__);

function email($to, $message, $subject='Email from Your Website', $from=false) {
	//todo: rewrite this whole function to use swiftmailer
	global $_josh;

	//set the sender	
	if (!$from) $from = (isset($_josh['email_default'])) ? $from : array('joshlib@joshreisner.com'=>'Joshlib');
	
	//fix up the recipient list
	if (!is_array($to)) $to = array($to);
	$to = array_unique($to);
	$tocount = count($to);
	for ($i = 0; $i < $tocount; $i++) if (empty($to[$i])) unset($to[$i]); //make sure they're non-null
	if (!count($to)) return false; //quit if now is empty array

	//use swiftmailer class
	lib_get('swiftmailer');

	//establish transport
	if (empty($_josh['smtp']['location']) || empty($_josh['smtp']['username'])) {
		//have to use PHP's mail function, bad!!
		$transport = Swift_MailTransport::newInstance();
	} else {
		$transport	= Swift_SmtpTransport::newInstance($_josh['smtp']['location'], 25)->setUsername($_josh['smtp']['username'])->setPassword($_josh['smtp']['password']);
	}
	$mailer		= Swift_Mailer::newInstance($transport);
	$message	= Swift_Message::newInstance()
		->setSubject($subject)
		->setFrom($from)
		->setTo($to)
		->setBody(strip_tags(nl2br($message)))
		->addPart($message, 'text/html')
		//->attach(Swift_Attachment::fromPath('my-document.pdf'))
	;
	if (!$count = $mailer->batchSend($message, $failures)) error_handle('email not sent', 'swiftmailer succeeded for ' . $count . ' and failed for the following addresses' . draw_array($failures));
	return $count;
}

function email_address_parse($address) {
	//eg josh@Joshreisner.com or Joshua Reisner <josh@joshreisner.com> or Reisner, Joshua <josh@joshreisner.com>
	$address = str_replace('"', '', strtolower($address));
	
	//address has name or it doesn't
	if (stristr($address, '<')) {
		list ($from, $email) = explode('<', str_replace('>', '', $address));
	
		//name is possibly reversed
		if (stristr($from, ',')) $from = implode(' ', array_reverse(array_map('trim', explode(',', $from))));
	} else {
		$email = $address;
		$from = substr($email, 0, strpos($email, '@'));
	}
	
	return array(trim($email), format_title(trim($from)));
}

function email_post($to=false, $subject=false, $from=false) {
	global $_josh, $_POST;
	if (!$to) $to = $_josh['email_default'];
	if (!$subject) $subject = 'Form Submission from ' . $_josh['request']['sanswww'];
	
	//form submit image produces these, which are confusing to users
	if (isset($_POST['x'])) unset($_POST['x']);
	if (isset($_POST['y'])) unset($_POST['y']);
	
	email($to, draw_page($subject, draw_array($_POST, true), false, true), $subject, $from);
}
?>