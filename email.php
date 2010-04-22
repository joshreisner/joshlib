<?php
error_debug('including email.php', __file__, __line__);

function email($to, $message, $subject='Email from Your Website', $from=false) {
	global $_josh;
	$to = format_email($to);
	if ($from) {
		$from = format_email($from);
	} else {
		if (!isset($_josh['email_default'])) error_handle('email from address missing', 'please call ' . __FUNCTION__ . ' with a from address, or specify one in the config file.', true);
		$from = $_josh['email_default'];
	}
	
	if (!empty($_josh['smtp']['location']) && !empty($_josh['smtp']['username']) && !empty($_josh['smtp']['password'])) {

		lib_get('smtp');
		lib_get('sasl');
	
		//use smtp server if credentials are found in config
		$smtp = new smtp_class;
		$smtp->host_name	= $_josh['smtp']['location'];
		$smtp->user			= $_josh['smtp']['username'];
		$smtp->password		= $_josh['smtp']['password'];
		
		//this has a problem with encoding
		$subject = format_accents_remove($subject);
		$message = format_accents_encode($message);
		
		if ($smtp->SendMessage(
				$from,
				array($to),
				array('From: ' . $from, 'To: ' . $to, 'Subject: ' . $subject, 'Date: ' . strftime('%a, %d %b %Y %H:%M:%S %Z'), 'MIME-Version: 1.0', 'Content-type: text/html; charset=iso-8859-1'), 
				$message
			)) {
			return true;
		} else {
			error_handle('SMTP Not Working', 'Sorry, an unexpected error of ' . $smtp->error . ' occurred while sending your mail to ' . $to, true);
			return false;
		}
	} else {
		//use regular php mechanism
		error_debug('<b>email </b> sending message to <i>' . $to . '</i> with subject ' . $subject, __file__, __line__);
		$headers  = 'MIME-Version: 1.0' . NEWLINE;
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . NEWLINE;
		$headers .= 'From: ' . format_email($from) . NEWLINE;
		if (!@mail($to, $subject, $message, $headers)) error_handle('email not sent', 'sorry, an unexpected error occurred while sending your mail to ' . $to, true);
		return true;
	}
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
	if (!$subject) $subject = 'Form Submission from ' . $_josh['request']['domain'];
	email($to, draw_page($subject, draw_array($_POST), false, true), $subject, $from);
}
?>