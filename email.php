<?php
error_debug('including email.php', __file__, __line__);

function email($to, $message, $subject='Email from Your Website', $from=false, $attachments=false) {
	//$to can be an array('person1@example.org', 'person2@example.org') or comma-separated string
	//$from can be array('josh@joshreisner.com'=>'Josh Reisner') or email address

	global $_josh;

	//set the sender	
	if (empty($from)) $from = (!empty($_josh['email_default'])) ? $_josh['email_default'] : 'josh@joshreisner.com';
	
	//check recipient list
	if (!$to = email_addresses($to)) return false; //quit if now if there are no good recipients for email
	
	//use swiftmailer class
	lib_get('swiftmailer');
	
	//establish transport
	if (empty($_josh['smtp']['location']) || empty($_josh['smtp']['username'])) {
		//have to use PHP's mail function
		$transport = Swift_MailTransport::newInstance();
	} else {
		if (empty($_josh['smtp']['socket'])) $_josh['smtp']['socket'] = 25;
		$security = ($_josh['smtp']['socket'] == 25) ? null : 'ssl';
		$transport	= Swift_SmtpTransport::newInstance($_josh['smtp']['location'], $_josh['smtp']['socket'], $security)->setUsername($_josh['smtp']['username'])->setPassword($_josh['smtp']['password']);
	}
	
	//die('CC is ' . draw_array($cc));
	
	$mailer		= Swift_Mailer::newInstance($transport);

	//define required message properties	
	$message	= Swift_Message::newInstance()
		->setSubject($subject)
		->setFrom($from)
		->setTo($to)
		->setBody(strip_tags(nl2br($message)))
		->addPart($message, 'text/html');
	
	//optional
	if ($attachments) {
		if (!is_array($attachments)) $attachments = array($attachments);
		foreach ($attachments as $a) $message->attach(Swift_Attachment::fromPath($a));
	}

	error_debug(__function__ . ' attempting to send to ' . implode(', ', $to), __file__, __line__);
	
	//debug();
	$failures = array();
	$count = $mailer->batchSend($message, $failures);
	if (!empty($failures)) error_handle('email failures', __function__ . ' succeeded for ' . $count . ' and failed for the following addresses' . draw_array($failures), __file__, __line__);
	error_debug(__function__ . ' sent ' . $count . ' messages successfully', __file__, __line__);
	
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

function email_addresses($input) {
	//return an array of properly formatted addresses from either an array or comma-separated list
	$input = (is_array($input)) ? array_unique($input) : array_separated($input);
	$good = $bad = array();
	foreach ($input as $e) {
		if (!$good[] = format_email($e)) {
			array_pop($good);
			$bad[] = $e;
		}
	}
	error_debug(__function__ . ' filtered out the following recipients ' . draw_list($bad), __file__, __line__);
	if (!count($good)) return false;
	return $good;
}

function email_array($to=false, $subject=false, $from=false, $array='post') {
	global $_josh;
	if ($array == 'post') $array = $_POST;
	if (!$to) $to = $_josh['email_default'];
	if (!$subject) $subject = 'Form Submission from ' . $_josh['request']['sanswww'];
	
	//cleanup form variables
	$confusing_vars = array('x', 'y', 'return_to', 'form_id');
	foreach ($confusing_vars as $c) if (isset($array[$c])) unset($array[$c]);
	
	email($to, draw_page($subject, draw_array($array, true), false, true), $subject, $from);
}

function email_post($to=false, $subject=false, $from=false) {
	email_array($to, $subject, $from, 'post');
}