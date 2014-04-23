<?php

class smscruSMS extends waSMSAdapter
{
	public function getControls()
	{
		return array(
			'login' => array(
				'value' => '',
				'title' => 'Логин',
				'description' => 'Введите логин в сервисе smsc.ru',
			),
			'psw' => array(
				'value' => '',
				'title' => 'Пароль',
				'description' => 'Введите пароль или MD5-хеш пароля в нижнем регистре',
			),
		);
	}

	// Указание имени отправителя временно недоступно (до обновления магазина). Используется имя отправителя по умолчанию.

	public function send($to, $text, $from = null)
	{
		$psw_smsc = $this->getOption('psw');

		$result =  $this->_read_url('http://smsc.ru/sys/send.php?login='.urlencode($this->getOption('login')).
										'&psw='.urlencode(strlen($psw_smsc) == 32 ? $psw_smsc : md5($psw_smsc)).
										'&phones='.urlencode($to).'&mes='.urlencode($text).
										'&cost=3&fmt=1&charset=utf-8');

		$this->log($to, $text, $result);

		return $result;
	}

	// Функция чтения URL. Для работы должно быть доступно:
	// curl или fsockopen (только http)

	private function _read_url($url)
	{
		$ret = "";

		if (function_exists("curl_init"))
		{
			static $c = 0; // keepalive

			if (!$c) {
				$c = curl_init();
				curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 10);
				curl_setopt($c, CURLOPT_TIMEOUT, 10);
				curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
			}

			curl_setopt($c, CURLOPT_URL, $url);

			$ret = curl_exec($c);
		}
		elseif (function_exists("fsockopen") && strncmp($url, 'http:', 5) == 0) // not https
		{
			$m = parse_url($url);

			$fp = fsockopen($m["host"], 80, $errno, $errstr, 10);

			if ($fp) {
				fwrite($fp, "GET $m[path]?$m[query] HTTP/1.1\r\nHost: smsc.ru\r\nUser-Agent: PHP\r\nConnection: Close\r\n\r\n");

				while (!feof($fp))
					$ret = fgets($fp, 1024);

				fclose($fp);
			}
		}

		return $ret;
	}
}