<?php
error_debug('including email.php', __file__, __line__);

function email($to, $message, $subject='Email from Your Website', $from=false) {
	global $_josh;
	error_debug('<b>email </b> sending message to <i>' . $to . '</i> with subject ' . $subject, __file__, __line__);
	$headers  = 'MIME-Version: 1.0' . NEWLINE;
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . NEWLINE;
	if ($from) {
		$headers .= 'From: ' . format_email($from) . NEWLINE;
	} else {
		if (!isset($_josh['email_default'])) error_handle('email from address missing', 'please call ' . __FUNCTION__ . ' with a from address, or specify one in the config file.', true);
		$headers .= 'From: ' . $_josh['email_default'] . NEWLINE;
	}
	$to = format_email($to);
	if (!@mail($to, $subject, $message, $headers)) error_handle('email not sent', 'sorry, an unexpected error occurred while sending your mail to ' . $to, true);
	return true;
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