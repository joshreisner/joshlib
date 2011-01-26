<?php
error_debug('including email.php', __file__, __line__);

function email($to, $message, $subject='Email from Your Website', $from=false) {
	//from could be array('josh@joshreisner.com'=>'Josh Reisner')

	//todo: rewrite this whole function to use swiftmailer
	global $_josh;

	//set the sender	
	if (empty($from)) $from = (!empty($_josh['email_default'])) ? $_josh['email_default'] : 'josh@joshreisner.com';
	
	//fix up the recipient list
	if (!is_array($to)) $to = array($to);
	$to = array_unique($to);
	//$tocount = count($to);
	//for ($i = 0; $i < $tocount; $i++) if (empty($to[$i])) unset($to[$i]); //make sure they're non-null
	
	$good = $bad = array();
	foreach ($to as $e) {
		if (!$good[] = format_email($e)) {
			array_pop($good);
			$bad[] = $e;
		}
	}

	if (!count($good)) return false; //quit if now is empty array

	//use swiftmailer class
	lib_get('swiftmailer');
	
	if (class_exists('Swift_MailTransport')) {	
		//establish transport
		if (empty($_josh['smtp']['location']) || empty($_josh['smtp']['username'])) {
			//have to use PHP's mail function
			$transport = Swift_MailTransport::newInstance();
		} else {
			$transport	= Swift_SmtpTransport::newInstance($_josh['smtp']['location'], 25)->setUsername($_josh['smtp']['username'])->setPassword($_josh['smtp']['password']);
		}
			
		$mailer		= Swift_Mailer::newInstance($transport);
		$message	= Swift_Message::newInstance()
			->setSubject($subject)
			->setFrom($from)
			->setTo($good)
			->setBody(strip_tags(nl2br($message)))
			->addPart($message, 'text/html')
			//->attach(Swift_Attachment::fromPath('my-document.pdf'))
		;
		if (!$count = $mailer->batchSend($message, $failures)) error_handle('email not sent', 'swiftmailer succeeded for ' . $count . ' and failed for the following addresses' . draw_array($failures), __file__, __line__);
	} else {
		//use php mail transport
		mail($to, $subject, $message, 'From:' . $from);
	}
	
	if (count($bad)) error_handle('email addresses rejected', 'the email with subject ' . $subject . ' was rejected for the following recipients ' . draw_list($bad) . ' and was successfully sent to ' . count($good) . ' recipients', __file__, __line__);
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
	global $_josh;
	if (!$to) $to = $_josh['email_default'];
	if (!$subject) $subject = 'Form Submission from ' . $_josh['request']['sanswww'];
	
	//form submit image produces these, which are confusing to users
	if (isset($_POST['x'])) unset($_POST['x']);
	if (isset($_POST['y'])) unset($_POST['y']);
	
	email($to, draw_page($subject, draw_array($_POST, true), false, true), $subject, $from);
}
?>