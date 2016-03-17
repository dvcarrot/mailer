# mailer

smtp mail class

## example
    use dvcarrot\mailer\SmtpMail;
    $mailer = new SmtpMail(array(
		'username' => 'username',
		'password' => 'password',
	));
	echo $mailer->send('to1@example.ru, to2@example.ru,', 'subject', 'message', '');
