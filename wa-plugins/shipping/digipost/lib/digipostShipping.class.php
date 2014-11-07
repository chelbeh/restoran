<?php

class digipostShipping extends waShipping
{

	public function allowedAddress()
	{
		$address = array(
			'country' => 'rus',
			'region'  => array(),
		);
		return array($address);
	}

	public function requestedAddressFields()
	{
		return array(
			'zip'     => array('cost' => true),
			'country' => array('hidden' => true, 'value' => 'rus'),
			'region'  => array(),
			'city'    => array(),
			'street'  => array(),
		);
	}

	public function calculate()
	{
		if (!empty($this->digipost_username) || !empty($this->digipost_api_key)) {

			$weight = $this->getTotalWeight()*1000;
			if ($weight <= 0) {$weight = $this->default_weight;}
			$zip = trim($this->getAddress('zip'));
			$from_zip = trim($this->from_zip);
			if ($this->valued) {$valued = $this->getTotalPrice();} else {$valued = 0;}

			if (!empty($zip)) {

				if (!empty($from_zip)) {

					$services = array();
					
					$url = 'http://digi-post.ru/api/calc?';
					$data = array('to'=>$this->getAddress('zip'), 'from'=>$from_zip, 'weight'=>$weight, 'value'=>$valued);
					
					$Response = $this->getFromDigiApi($url, $data, 'get');

					if (is_string($this->getSettings('allowance'))) {
						$allowance = (int)$this->getSettings('allowance');
					} else {
						$allowance = 0;
					}

					//print_r($Response);

					if (!empty($Response->parcel) && !empty($this->deliveries['parcel'])) {
						$parcel_eta_to = $Response->parcel->eta+5;
						$services['parcel'] = array(
							'name'         => $Response->parcel->type,
							'description' => $Response->info->route,
							'id'           => 'parcel',
							'est_delivery' => waDateTime::format('humandate', strtotime('+'.(int)$Response->parcel->eta.' days')).' — '.waDateTime::format('humandate', strtotime('+'.$parcel_eta_to.' days')),
							'rate'         => $Response->parcel->cost + $allowance,
							'currency'     => 'RUB',
						);
					}

					if (!empty($Response->bookpost_1class) && !empty($this->deliveries['bookpost_1class'])) {
						$bookpost_1class_eta_to = $Response->bookpost_1class->eta+3;
						$services['bookpost_1class'] = array(
							'name'         => $Response->bookpost_1class->type,
							'description' => $Response->info->route,
							'id'           => 'bookpost_1class',
							'est_delivery' => waDateTime::format('humandate', strtotime('+'.(int)$Response->bookpost_1class->eta.' days')).' — '.waDateTime::format('humandate', strtotime('+'.$bookpost_1class_eta_to.'days')),
							'rate'         => $Response->bookpost_1class->cost_nds + $allowance,
							'currency'     => 'RUB',
						);
					}

					if (!empty($Response->valued_bookpost) && !empty($this->deliveries['valued_bookpost'])) {
						$valued_bookpost_eta_to = $Response->valued_bookpost->eta+4;
						$services['valued_bookpost'] = array(
							'name'         => $Response->valued_bookpost->type,
							'description' => $Response->info->route,
							'id'           => 'valued_bookpost',
							'est_delivery' => waDateTime::format('humandate', strtotime('+'.(int)$Response->parcel->eta.' days')).' — '.waDateTime::format('humandate', strtotime('+'.$valued_bookpost_eta_to.'days')),
							'rate'         => $Response->valued_bookpost->cost_nds + $allowance,
							'currency'     => 'RUB',
						);
					}

					if (!empty($Response->bookpost) && !empty($this->deliveries['bookpost'])) {
						$bookpost_eta_to = $Response->bookpost->eta+4;
						$services['bookpost'] = array(
							'name'         => $Response->bookpost->type,
							'description' => $Response->info->route,
							'id'           => 'bookpost',
							'est_delivery' => waDateTime::format('humandate', strtotime('+'.(int)$Response->parcel->eta.' days')).' — '.waDateTime::format('humandate', strtotime('+'.$bookpost_eta_to.'days')),
							'rate'         => $Response->bookpost->cost_reg_bookpost_cost_nds + $allowance,
							'currency'     => 'RUB',
						);
					}

					if (!empty($Response->error)) {
						return $Response->message;
					}

				} else {
					$services = false;
				}

			} else {
				$services = 'Для расчета стоимости доставки укажите почтовый индекс!';
			}
		} else {
			$services = false;
		}

		return $services;
	}

	public function getPrintForms(waOrder $order = null)
	{
		$digipost_api_key = trim($this->digipost_api_key);

		if (!empty($digipost_api_key)) {

			return extension_loaded('curl') ? array(
				7 => array(
					'name'        => 'Форма №7 (ярлык)',
					'description' => 'Ярлык (наклейка) на отправление',
				),
				113 => array(
					'name'        => 'Форма №113 (наложка)',
					'description' => 'Бланк почтового перевода наложенного платежа',
				),
				107 => array(
					'name'        => 'Форма №107 (опись)',
					'description' => 'Бланк описи товаров',
				),
				116 => array(
					'name'        => 'Форма №116',
					'description' => 'Бланк сопроводительного адреса к посылке',
				),
			) : array();
		} else {
			//return array();
			return array ( 1 => array('name'=>"Почтовые формы"));
		}
	}

	public function displayPrintForm($id, waOrder $order, $params = array())
	{
		$method = 'displayPrintForm'.$id;
		if (method_exists($this, $method)) {
			return $this->$method($order, $params);
		} else {
			throw new waException('Print form not found');
		}

	}

	private function displayPrintForm1(waOrder $order, $params = array())
	{
		throw new waException('Для печати почтовых форм введите API ключ в настройках плагина.');
	}

	private function displayPrintForm113(waOrder $order, $params = array())
	{
		

		switch ($side = waRequest::get('type', ($order ? '' : 'print'), waRequest::TYPE_STRING)) {
		case 'print':
			$data = waRequest::post();
			if (is_null($data)) {
				throw new waException('ORDER data is empty! Try again.', 400);
			}
			$blankURL = $this->getFromApi($data, 'blanks');
			$orderId = waRequest::get('order_id');
			if (is_string($blankURL)) {
				$opm = new shopOrderParamsModel();
				$opm->set($orderId, array('digipost.F113' => $blankURL, 'digipost.F113.date' => time()), false);
				//print_r($opm);
				header("Location: $blankURL");
				die();
			} else {
				switch ($blankURL->error) {
				case 1:
					throw new waException('Нет такого бланка');
				case 2:
					throw new waException('Не заданы параметры запроса');
				case 3:
					throw new waException('API ключ не передан или не верен');
				case 4:
					throw new waException('Неизвестная ошибка, попробуйте еще раз');
				case 5:
					throw new waException('Лимит бланков исчерпан');
				}
			}
			break;
		default:
			$digipost_api_key = trim($this->digipost_api_key);
			$data = array('key'=>$digipost_api_key, 'get_limit'=>'1');
			$Response = $this->getFromApi($data, 'blanks_limit');
			if($Response === FALSE) {
				$limit = false;
			} else {
				$limit = json_decode($Response)->limit;
			}

			$this->view()->assign('order', $order);
			$this->view()->assign('limit', $limit);
			$this->view()->assign('settings', $this->getSettings());
			break;
		}
		return $this->view()->fetch($this->path.'/templates/form.F113.html');
	}

	private function displayPrintForm116(waOrder $order, $params = array())
	{
		

		switch ($side = waRequest::get('type', ($order ? '' : 'print'), waRequest::TYPE_STRING)) {
		case 'print':
			$data = waRequest::post();
			if (is_null($data)) {
				throw new waException('ORDER data is empty! Try again.', 400);
			}
			$blankURL = $this->getFromApi($data, 'blanks');
			$orderId = waRequest::get('order_id');
			if (is_string($blankURL)) {
				$opm = new shopOrderParamsModel();
				$opm->set($orderId, array('digipost.F116' => $blankURL, 'digipost.F116.date' => time()), false);
				//print_r($opm);
				header("Location: $blankURL");
				die();
			} else {
				switch ($blankURL->error) {
				case 1:
					throw new waException('Нет такого бланка');
				case 2:
					throw new waException('Не заданы параметры запроса');
				case 3:
					throw new waException('API ключ не передан или не верен');
				case 4:
					throw new waException('Неизвестная ошибка, попробуйте еще раз');
				case 5:
					throw new waException('Лимит бланков исчерпан');
				}
			}
			break;
		default:
			$digipost_api_key = trim($this->digipost_api_key);

			$data = array('key'=>$digipost_api_key, 'get_limit'=>'1');
			$Response = $this->getFromApi($data, 'blanks_limit');

			if($Response === FALSE) {
				$limit = false;
			} else {
				$limit = json_decode($Response)->limit;
			}

			$this->view()->assign('order', $order);
			$this->view()->assign('limit', $limit);
			$this->view()->assign('settings', $this->getSettings());
			break;
		}
		return $this->view()->fetch($this->path.'/templates/form.F116.html');
	}

	private function displayPrintForm7(waOrder $order, $params = array())
	{
		

		switch ($side = waRequest::get('type', ($order ? '' : 'print'), waRequest::TYPE_STRING)) {
		case 'print':
			$data = waRequest::post();
			if (is_null($data)) {
				throw new waException('ORDER data is empty! Try again.', 400);
			}
			$blankURL = $this->getFromApi($data, 'blanks');
			$orderId = waRequest::get('order_id');
			if (is_string($blankURL)) {
				$opm = new shopOrderParamsModel();
				$opm->set($orderId, array('digipost.7' => $blankURL, 'digipost.7.date' => time()), false);
				//print_r($opm);
				header("Location: $blankURL");
				die();
			} else {
				switch ($blankURL->error) {
				case 1:
					throw new waException('Нет такого бланка');
				case 2:
					throw new waException('Не заданы параметры запроса');
				case 3:
					throw new waException('API ключ не передан или не верен');
				case 4:
					throw new waException('Неизвестная ошибка, попробуйте еще раз');
				case 5:
					throw new waException('Лимит бланков исчерпан');
				}
			}
			break;
		default:
			$digipost_api_key = trim($this->digipost_api_key);
			$data = array('key'=>$digipost_api_key, 'get_limit'=>'1');
			$Response = $this->getFromApi($data, 'blanks_limit');
			if($Response === FALSE) {
				$limit = false;
			} else {
				$limit = json_decode($Response)->limit;
			}

			$this->view()->assign('order', $order);
			$this->view()->assign('limit', $limit);
			$this->view()->assign('settings', $this->getSettings());
			break;
		}
		return $this->view()->fetch($this->path.'/templates/form.F7.html');
	}

	private function displayPrintForm107(waOrder $order, $params = array())
	{
		

		switch ($side = waRequest::get('type', ($order ? '' : 'print'), waRequest::TYPE_STRING)) {
		case 'print':
			$data = waRequest::post();
			if (is_null($data)) {
				throw new waException('ORDER data is empty! Try again.', 400);
			}
			$blankURL = $this->getFromApi($data, 'blanks');
			$orderId = waRequest::get('order_id');
			if (is_string($blankURL)) {
				$opm = new shopOrderParamsModel();
				$opm->set($orderId, array('digipost.107' => $blankURL, 'digipost.107.date' => time()), false);
				//print_r($blankURL);
				header("Location: $blankURL");
				die();
			} else {
				switch ($blankURL->error) {
				case 1:
					throw new waException('Нет такого бланка');
				case 2:
					throw new waException('Не заданы параметры запроса');
				case 3:
					throw new waException('API ключ не передан или не верен');
				case 4:
					throw new waException('Неизвестная ошибка, попробуйте еще раз');
				case 5:
					throw new waException('Лимит бланков исчерпан');
				}
			}
			break;
		default:
			$digipost_api_key = trim($this->digipost_api_key);
			$data = array('key'=>$digipost_api_key, 'get_limit'=>'1');
			$Response = $this->getFromApi($data, 'blanks_limit');
			if($Response === FALSE) {
				$limit = false;
			} else {
				$limit = json_decode($Response)->limit;
			}

			$this->view()->assign('order', $order);
			$this->view()->assign('limit', $limit);
			$this->view()->assign('settings', $this->getSettings());
			break;
		}
		return $this->view()->fetch($this->path.'/templates/form.F107.html');
	}

	private function view()
	{
		static $view;
		if (!$view) {
			$view = wa()->getView();
		}
		return $view;
	}

	public function tracking($tracking_id = null)
	{
		$digipost_api_key = trim($this->digipost_api_key);

		if (empty($digipost_api_key)) {
			return('Пожалуйста, зарегистрируйтесь на www.digi-post.ru и введите API ключ.');
		}

		$data = array('key'=>$digipost_api_key,'method'=>'get', 'type'=>'json', 'track_id'=>$tracking_id);

		$Response = $this->getFromApi($data, 'tracking_get');
		$res = json_decode($Response);

		if($res != null) {

			$track = '<table class="light" width="100%"><tbody>';
			$track .= '<tr><th>Дата</th><th>Тип</th><th>Атрибут</th><th>Место</th></tr>';
			foreach ($res as $item) {
				$track .= '<tr>';
				$track .= '<td>'.date('d.m.Y H:i:s', strtotime($item->date)).'</td><td>'.$item->type.'</td><td>'.$item->attribute.'</td><td>'.$item->placePostCode.' '.$item->placeName.'</td>';
				$track .= '<tr>';
			}
			$track .= '</tbody></table>';
			$track .= '<p>Уведомляйте своих клиентов о движении посылки по EMAIL или SMS! <br>Настройте параметры уведомления на сайте Digi-Post.ru <a target="_blank" href="http://digi-post.ru/user/profile/trackingSettings">по этой ссылке</a>.</p>';

			if ($this->upload) {
				$order_id = waRequest::get('id', '', 'int');
				$data2 = array('key'=>$digipost_api_key, 'method'=>'post', 'type'=>'xml', 'track_id'=>$tracking_id, 'order_id'=>$order_id);

				$Response2 = $this->getFromApi($data2, 'tracking_get');
				$track .= $Response2;

			}
		} else {
			$track = $Response;
		}
		return $track;
	}



	public function allowedCurrency()
	{
		return 'RUB';
	}

	public function allowedWeightUnit()
	{
		return 'kg';
	}

	public function saveSettings($settings = array())
	{
		return parent::saveSettings($settings);
	}

	public function getFromApi($data, $type) {

		$timeout = 15;
		$str = http_build_query($data);
		
		if ($type == 'blanks') {
			$url = "http://digi-post.ru/blanks/russianpost/api";
		} elseif ($type == 'calculate') {
			$url = "http://digi-post.ru/calc/api?".$str;
		} elseif ($type == 'tracking_get') {
			$url = "http://digi-post.ru/tracking/trackit/api?".$str;
		} elseif ($type == 'blanks_limit') {
			$url = "http://digi-post.ru/blanks/russianpost/api?".$str;
		} else {
			throw new waException('Не указан тип запроса к API. Обратитесь за поддежкой в digi-post.ru');
		}

		if (extension_loaded('curl') && function_exists('curl_init')) {
			$curl_error = null;
			if (!($ch = curl_init())) {
				$curl_error = 'curl init error';
			}
			if (curl_errno($ch) != 0) {
				$curl_error = 'curl init error: '.curl_errno($ch);
			}
			if (!$curl_error) {

				@curl_setopt($ch, CURLOPT_URL, $url);
				@curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
				@curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

				if ($type == 'blanks') {
					@curl_setopt($ch, CURLOPT_POST, true);
					@curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
				}

				$res = @curl_exec($ch);
				$response = $this->getResult($res);

				if (curl_errno($ch) != 0) {
					$curl_error = 'curl error: '.curl_errno($ch);
				}
				curl_close($ch);
			} else {
				throw new waException($curl_error);
			}
		} else {
			//$response .= " PHP extension 'curl' is not loaded;";
			throw new waException("PHP extension 'curl' is not loaded");
			/*
if (!ini_get('allow_url_fopen')) {
				$hint .= " PHP ini option 'allow_url_fopen' are disabled;";
			} else {
				$old_timeout = @ini_set('default_socket_timeout', $timeout);
				$res = @file_get_contents($url.$str);
				$response = $this->getResult($res);
				@ini_set('default_socket_timeout', $old_timeout);
			}
*/
		}
		/*
if (!$response && !$hint) {
			throw new waException(sprintf('Пустой ответ от сервера. (Empty response. Hint: %s)', $hint));
		}
*/

		return $response;
	}

	public function getFromDigiApi($url, $data, $type) {

		$timeout = 5;
		if (!empty($data)) {
			$str = http_build_query($data);
		} else {
			$str = '';
		}

		$url=$url.$str;
		//print_r($url);
		if (extension_loaded('curl') && function_exists('curl_init')) {
			$curl_error = null;
			if (!($ch = curl_init())) {
				$curl_error = 'curl init error';
			}
			if (curl_errno($ch) != 0) {
				$curl_error = 'curl init error: '.curl_errno($ch);
			}
			if (!$curl_error) {

				@curl_setopt($ch, CURLOPT_URL, $url);
				@curl_setopt($ch, CURLOPT_USERPWD, trim($this->digipost_username) . ":" . trim($this->digipost_api_key));
				@curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
				@curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);


				if ($type == 'post') {
					@curl_setopt($ch, CURLOPT_POST, true);
					@curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
				}

				$res = @curl_exec($ch);
				$response = $this->getResult($res);

				//print_r($response);

				if (curl_errno($ch) != 0) {
					$curl_error = 'curl error: '.curl_errno($ch);
				}
				curl_close($ch);
			} else {
				throw new waException($curl_error);
			}
		} else {
			throw new waException("PHP extension 'curl' is not loaded");
		}
		return $response;
	}

	public function getResult($res) {
		$temp = json_decode($res);
		if (isset($temp->error))
			return $temp;

		return $temp;
	}

}
