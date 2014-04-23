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
		$digipost_api_key = trim($this->digipost_api_key);

		if (!empty($digipost_api_key)) {

			$weight = $this->getTotalWeight();
			if ($weight <= 0) {$weight = $this->default_weight;}
			$zip = trim($this->getAddress('zip'));
			$from_zip = trim($this->from_zip);

			if (!empty($zip)) {
				if (!empty($from_zip)) {
					$services = array();
					$data = array('key'=>$digipost_api_key, 'to'=>$this->getAddress('zip'), 'from'=>$from_zip, 'weight'=>$weight, 'value'=> $this->getTotalPrice(), 'method'=>'json');

					$Response = $this->getFromApi($data, 'calculate');
					$res = json_decode($Response);

					if($res != null) {

						if (isset($res->delivery_time)) {
						$std = $res->delivery_time->standart +6;
						$first_class = $res->delivery_time->first_class +6;
						$avia = $res->delivery_time->avia +6;
						$ems = $res->delivery_time->first_class +2;

						$delivery_date = waDateTime::format('humandate', strtotime('+'.(int)$res->delivery_time->standart.' days')).' — '.waDateTime::format('humandate', strtotime('+'.(int)$std.' days'));
						$delivery_date_first_class = waDateTime::format('humandate', strtotime('+'.(int)$res->delivery_time->first_class.' days')).' — '.waDateTime::format('humandate', strtotime('+'.(int)$first_class.' day'));
						$delivery_date_avia = waDateTime::format('humandate', strtotime('+'.(int)$res->delivery_time->avia.' days')).' — '.waDateTime::format('humandate', strtotime('+'.(int)$avia.' day'));
						$delivery_date_ems = waDateTime::format('humandate', strtotime('+'.(int)$res->delivery_time->first_class.' days')).' — '.waDateTime::format('humandate', strtotime('+'.(int)$ems.' day'));
						
						} else {
							$delivery_date = false;
							$delivery_date_first_class = false;
							$delivery_date_avia = false;
							$delivery_date_ems = false;
							
						}
						
						if (is_string($this->getSettings('allowance'))) {
							$allowance = (int)$this->getSettings('allowance');
						} else {
							$allowance = 0;
						}

						if (isset($res->bookpost) && $weight < $res->bookpost->max_weight && !empty($this->deliveries['bookpost'])) {
							$services['bookpost'] = array(
								'name'         => $res->bookpost->name,
								'description' => $res->to->address,
								'est_delivery' => $delivery_date,
								'id'           => 'bookpost',
								'rate'         => $res->bookpost->rate + $allowance,
								'currency'     => 'RUB',
							);
						}
						if (isset($res->valuable_bookpost) && $weight < $res->valuable_bookpost->max_weight && !empty($this->deliveries['valuable_bookpost'])) {
							$services['valuable_bookpost'] = array(
								'name'         => $res->valuable_bookpost->name,
								'description' => $res->to->address,
								'id'           => 'valuable_bookpost',
								'est_delivery' => $delivery_date,
								'rate'         => $res->valuable_bookpost->delivery + $allowance,
								'currency'     => 'RUB',
							);
						}
						if (isset($res->first_class_valuable_bookpost) && $weight < $res->first_class_valuable_bookpost->max_weight && !empty($this->deliveries['first_class_valuable_bookpost'])) {
							$services['first_class_valuable_bookpost'] = array(
								'name'         => $res->first_class_valuable_bookpost->name,
								'description' => $res->to->address,
								'id'           => 'first_class_valuable_bookpost',
								'est_delivery' => $delivery_date_first_class,
								'rate'         => $res->first_class_valuable_bookpost->delivery + $allowance,
								'currency'     => 'RUB',
							);
						}
						if (isset($res->valuable_bookpost_avia) && $weight < $res->valuable_bookpost_avia->max_weight && !empty($this->deliveries['valuable_bookpost_avia'])) {
							$services['valuable_bookpost_avia'] = array(
								'name'         => $res->valuable_bookpost_avia->name,
								'description' => $res->to->address,
								'id'           => 'valuable_bookpost_avia',
								'est_delivery' => $delivery_date_avia,
								'rate'         => $res->valuable_bookpost_avia->delivery + $allowance,
								'currency'     => 'RUB',
							);
						}
						if (isset($res->registered_bookpost) && $weight < $res->registered_bookpost->max_weight && !empty($this->deliveries['registered_bookpost'])) {
							$services['registered_bookpost'] = array(
								'name'         => $res->registered_bookpost->name,
								'description' => $res->to->address,
								'id'           => 'registered_bookpost',
								'est_delivery' => $delivery_date,
								'rate'         => $res->registered_bookpost->rate + $allowance,
								'currency'     => 'RUB',
							);
						}

						if (isset($res->first_class_registered_bookpost) && $weight < $res->first_class_registered_bookpost->max_weight && !empty($this->deliveries['first_class_registered_bookpost'])) {
							$services['first_class_registered_bookpost'] = array(
								'name'         => $res->first_class_registered_bookpost->name,
								'description' => $res->to->address,
								'id'           => 'first_class_registered_bookpost',
								'est_delivery' => $delivery_date_first_class,
								'rate'         => $res->first_class_registered_bookpost->rate + $allowance,
								'currency'     => 'RUB',
							);
						}

						if (isset($res->valuable_parcel) && $weight < $res->valuable_parcel->max_weight && !empty($this->deliveries['valuable_parcel'])) {
							$services['valuable_parcel'] = array(
								'name'         => $res->valuable_parcel->name,
								'description' => $res->to->address,
								'id'           => 'valuable_parcel',
								'est_delivery' => $delivery_date,
								'rate'         => $res->valuable_parcel->delivery + $allowance,
								'currency'     => 'RUB',
							);
						}

						if (isset($res->avia_valuable_parcel) && $weight < $res->avia_valuable_parcel->max_weight && !empty($this->deliveries['avia_valuable_parcel'])) {
							$services['avia_valuable_parcel'] = array(
								'name'         => $res->avia_valuable_parcel->name,
								'description' => $res->to->address,
								'id'           => 'avia_valuable_parcel',
								'est_delivery' => $delivery_date_avia,
								'rate'         => $res->avia_valuable_parcel->delivery + $allowance,
								'currency'     => 'RUB',
							);
						}

						if (isset($res->ems) && $weight < $res->ems->max_weight && !empty($this->deliveries['ems'])) {
							$services['ems'] = array(
								'name'         => $res->ems->name,
								'description' => $res->to->address,
								'id'           => 'ems',
								'est_delivery' => $delivery_date_ems,
								'rate'         => $res->ems->delivery + $allowance,
								'currency'     => 'RUB',
							);
						}

					} else { $services = false; }

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

	public function getResult($res) {
		$temp = json_decode($res);
		if (isset($temp->error))
			return $temp;

		return $res;
	}

}
