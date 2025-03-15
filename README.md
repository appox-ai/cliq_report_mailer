# Cliq report mailer

This application send emails to severals users with information about checkin and checkout records at Cliq for the specific user.

Use the config.php file to set mail credentials.

~~~
<?php
$conf=[
	'mailHost' => 'url_mailHost',
	'mailUsername' => 'user@domain-example.com',
	'mailPassword' => 'mail-password',
];
global $conf;
?>
~~~

This application get users listed in users.json file and then filter all records contained in input/data.csv file matching the same user name.

users.json example
~~~
[
	{
		"name":"Jorge",
		"mail":"jorge@mail.com"
	},
	{
		"name":"Lola",
		"mail":"lola@mail.com"
	}
]
~~~

input/data.csv example
~~~
"866012951","Jorge","2025-02-26","8:32","16:2","7.5" 
"866012951","Jorge","2025-02-27","8:34","16:2","7.5" 
"860955724","Lola","2025-02-24","9:32","9:26","23.9" 
"860955724","Lola","2025-02-27","9:3","16:59","7.9" 
~~~

The input/data.csv file must be update weekle to send the right info to users before the CRON task run the main.php file.
