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

					$url = 'https://api.digi-post.ru/calc?';
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
				112 => array(
					'name'        => 'Форма №112 (наложка)',
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
			return array ( 666 => array('name'=>"Почтовые формы"));
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

	private function displayPrintForm666(waOrder $order, $params = array())
	{
		throw new waException('Для печати почтовых форм введите API ключ в настройках плагина.');
	}

	private function displayPrintForm112(waOrder $order, $params = array())
	{

		switch ($side = waRequest::get('type', ($order ? '' : 'print'), waRequest::TYPE_STRING)) {
		case 'print':
			$data = waRequest::post();
			if (is_null($data)) {
				throw new waException('ORDER data is empty! Перезагрузите страницу, и попробуйте еще раз.', 400);
			}
			$blankURL = $this->getFromDigiApi("https://api.digi-post.ru/blanks?", $data, 'post');

			$orderId = waRequest::get('order_id');
			if ($blankURL->error == 0 && is_string($blankURL->message)) {
				$opm = new shopOrderParamsModel();
				$opm->set($orderId, array('digipost.F112' => $blankURL->message, 'digipost.F112.date' => time()), false);
				header("Location: $blankURL->message");
				die();
			} else {
				switch ($blankURL->error) {
				case 10:
					throw new waException('Неверно указано имя пользователя в настройках плагина');
				case 11:
					throw new waException('Неверно указан API ключ или неверно указан пользователь в настройках плагина');
				case 12:
					throw new waException('Количество запросов к API превысило возможности бесплатного пользования');
				case 300:
					throw new waException('Исчерпан дневной лимит печатаемых бланков. Чтобы снять лимит, приобретите лицензию.');
				case 301:
					throw new waException('Не установлен параметр "order_price". Обратитесь на d.post@dtgp.ru');
				case 303:
					throw new waException('Установите параметр "blank_type". Обратитесь на d.post@dtgp.ru ');
				case 304:
					throw new waException('Установите параметр "blank_type_id". Обратитесь на d.post@dtgp.ru ');
				case 305:
					throw new waException('Не передан массив "products". Обратитесь на d.post@dtgp.ru ');
				case 306:
					throw new waException('Неверный параметр "blank_type". Обратитесь на d.post@dtgp.ru ');
				case 307:
					throw new waException('Параметр "order_price" должен быть больше нуля. Обратитесь на d.post@dtgp.ru ');

				}
			}
			break;
		default:

			$data = array();
			$plan = $this->getFromDigiApi("https://api.digi-post.ru/tracking/limits", $data, '');

			$limits = $plan->message;

			$this->view()->assign('order', $order);
			$this->view()->assign('limits', $limits);
			$this->view()->assign('settings', $this->getSettings());
			break;
		}
		return $this->view()->fetch($this->path.'/templates/form.F112.html');
	}



	private function displayPrintForm7(waOrder $order, $params = array())
	{
		switch ($side = waRequest::get('type', ($order ? '' : 'print'), waRequest::TYPE_STRING)) {
		case 'print':
			$data = waRequest::post();
			if (is_null($data)) {
				throw new waException('ORDER data is empty! Try again.', 400);
			}

			$blankURL = $this->getFromDigiApi("https://api.digi-post.ru/blanks?", $data, 'post');

			$orderId = waRequest::get('order_id');
			if (is_string($blankURL->message)) {
				$opm = new shopOrderParamsModel();
				$opm->set($orderId, array('digipost.F7' => $blankURL->message, 'digipost.F7.date' => time()), false);
				//print_r($opm);
				header("Location: $blankURL->message");
				die();
			} else {
				switch ($blankURL->error) {
				case 10:
					throw new waException('Неверно указано имя пользователя в настройках плагина');
				case 11:
					throw new waException('Неверно указан API ключ или неверно указан пользователь в настройках плагина');
				case 12:
					throw new waException('Количество запросов к API превысило возможности бесплатного пользования');
				case 300:
					throw new waException('Исчерпан дневной лимит печатаемых бланков. Чтобы снять лимит, приобретите лицензию.');
				case 301:
					throw new waException('Не установлен параметр "order_price". Обратитесь на d.post@dtgp.ru');
				case 303:
					throw new waException('Установите параметр "blank_type". Обратитесь на d.post@dtgp.ru ');
				case 304:
					throw new waException('Установите параметр "blank_type_id". Обратитесь на d.post@dtgp.ru ');
				case 305:
					throw new waException('Не передан массив "products". Обратитесь на d.post@dtgp.ru ');
				case 306:
					throw new waException('Неверный параметр "blank_type". Обратитесь на d.post@dtgp.ru ');
				case 307:
					throw new waException('Параметр "order_price" должен быть больше нуля. Обратитесь на d.post@dtgp.ru ');
				}
			}
			break;
		default:
			$data = array();
			$plan = $this->getFromDigiApi("https://api.digi-post.ru/tracking/limits", $data, '');

			$limits = $plan->message;

			$this->view()->assign('order', $order);
			$this->view()->assign('limits', $limits);
			$this->view()->assign('settings', $this->getSettings());
			break;
		}
		return $this->view()->fetch($this->path.'/templates/form.F7.html');
	}



	private function displayPrintForm116(waOrder $order, $params = array())
	{

		switch ($side = waRequest::get('type', ($order ? '' : 'print'), waRequest::TYPE_STRING)) {
		case 'print':
			$data = waRequest::post();
			if (is_null($data)) {
				throw new waException('ORDER data is empty! Try again.', 400);
			}

			$blankURL = $this->getFromDigiApi("https://api.digi-post.ru/blanks?", $data, 'post');

			$orderId = waRequest::get('order_id');
			if (is_string($blankURL->message)) {
				$opm = new shopOrderParamsModel();
				$opm->set($orderId, array('digipost.F116' => $blankURL, 'digipost.F116.date' => time()), false);
				//print_r($opm);
				header("Location: $blankURL->message");
				die();
			} else {
				switch ($blankURL->error) {
				case 10:
					throw new waException('Неверно указано имя пользователя в настройках плагина');
				case 11:
					throw new waException('Неверно указан API ключ или неверно указан пользователь в настройках плагина');
				case 12:
					throw new waException('Количество запросов к API превысило возможности бесплатного пользования');
				case 300:
					throw new waException('Исчерпан дневной лимит печатаемых бланков. Чтобы снять лимит, приобретите лицензию.');
				case 301:
					throw new waException('Не установлен параметр "order_price". Обратитесь на d.post@dtgp.ru');
				case 303:
					throw new waException('Установите параметр "blank_type". Обратитесь на d.post@dtgp.ru ');
				case 304:
					throw new waException('Установите параметр "blank_type_id". Обратитесь на d.post@dtgp.ru ');
				case 305:
					throw new waException('Не передан массив "products". Обратитесь на d.post@dtgp.ru ');
				case 306:
					throw new waException('Неверный параметр "blank_type". Обратитесь на d.post@dtgp.ru ');
				case 307:
					throw new waException('Параметр "order_price" должен быть больше нуля. Обратитесь на d.post@dtgp.ru ');
				}
			}
			break;
		default:
			$data = array();
			$plan = $this->getFromDigiApi("https://api.digi-post.ru/tracking/limits", $data, '');

			$limits = $plan->message;

			$this->view()->assign('order', $order);
			$this->view()->assign('limits', $limits);
			$this->view()->assign('settings', $this->getSettings());
			break;
		}
		return $this->view()->fetch($this->path.'/templates/form.F116.html');
	}



	private function displayPrintForm107(waOrder $order, $params = array())
	{
		switch ($side = waRequest::get('type', ($order ? '' : 'print'), waRequest::TYPE_STRING)) {
		case 'print':
			$data = waRequest::post();
			if (is_null($data)) {
				throw new waException('ORDER data is empty! Try again.', 400);
			}

			$blankURL = $this->getFromDigiApi("https://api.digi-post.ru/blanks?", $data, 'post');

			$orderId = waRequest::get('order_id');
			if (is_string($blankURL->message)) {
				$opm = new shopOrderParamsModel();
				$opm->set($orderId, array('digipost.F107' => $blankURL, 'digipost.F107.date' => time()), false);
				//print_r($blankURL);
				header("Location: $blankURL->message");
				die();
			} else {
				switch ($blankURL->error) {
				case 10:
					throw new waException('Неверно указано имя пользователя в настройках плагина');
				case 11:
					throw new waException('Неверно указан API ключ или неверно указан пользователь в настройках плагина');
				case 12:
					throw new waException('Количество запросов к API превысило возможности бесплатного пользования');
				case 300:
					throw new waException('Исчерпан дневной лимит печатаемых бланков. Чтобы снять лимит, приобретите лицензию.');
				case 301:
					throw new waException('Не установлен параметр "order_price". Обратитесь на d.post@dtgp.ru');
				case 303:
					throw new waException('Установите параметр "blank_type". Обратитесь на d.post@dtgp.ru ');
				case 304:
					throw new waException('Установите параметр "blank_type_id". Обратитесь на d.post@dtgp.ru ');
				case 305:
					throw new waException('Не передан массив "products". Обратитесь на d.post@dtgp.ru ');
				case 306:
					throw new waException('Неверный параметр "blank_type". Обратитесь на d.post@dtgp.ru ');
				case 307:
					throw new waException('Параметр "order_price" должен быть больше нуля. Обратитесь на d.post@dtgp.ru ');
				}
			}
			break;
		default:
			$data = array();
			$plan = $this->getFromDigiApi("https://api.digi-post.ru/tracking/limits", $data, '');

			$limits = $plan->message;

			$this->view()->assign('order', $order);
			$this->view()->assign('limits', $limits);
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
			return('Пожалуйста, зарегистрируйтесь на api.digi-post.ru и введите API ключ.');
		}

		
		$data = array('postcode'=>$tracking_id);

		if ($tracking_id != null) {
			$Response = $this->getFromDigiApi("https://api.digi-post.ru/tracking?mode=track&", $data, '');
		}

		if($Response->error == 0) {
			$track = '<table class="light" width="100%"><tbody>';
			$track .= '<tr><th>Дата</th><th>Тип</th><th>Атрибут</th><th>Место</th></tr>';
			foreach ($Response->message as $item) {
				$track .= '<tr>';
				$track .= '<td>'.$item->operationDate.'</td><td>'.$item->operationTypeName.'</td><td>'.$item->operationAttrName.'</td><td><a href="http://digi-post.ru/address/postcode/'.$item->operationIndex.'" target="_blank">'.$item->operationIndex.'</a>, '.$item->operationDescription.'</td>';
				$track .= '<tr>';
			}
			$track .= '</tbody></table>';
			
			$track .= '<br><a target="_blank" href="https://api.digi-post.ru/user/tracks/view/id/'.$tracking_id.'">Посмотрите</a> полную информацию движения трека, а так же уведомления, отправленные клиенту сервисом api.digi-post.ru<br><br>';
			
			$track .= '<p>Модуль отслеживания умеет <strong>автоматически</strong> уведомлять ваших клиентов через SMS и Email о статусе движения посылки. Таким образом повышается лояльность клиентов, увеличивается конверсия, снижается процент возвратов. Ведь мы будем по вашему заданию отправлять уведомления клиенту каждые 5 дней, пока он не заберет посылку. <br><br>Настройте параметры уведомления на сайте api.Digi-Post.ru <a target="_blank" href="https://api.digi-post.ru/user/settings">в разделе "Настройки"</a>.</p>';
		} else {
			$track = $Response->message;
		}
		
		if($Response->error == 105) {
			$som = new shopOrderModel();
			$order_id = waRequest::get('id', '', 'int');
			$order = $som->getOrder($order_id, true, true);
			
			$contact = new waContact($order['contact']['id']);
			
			$order_data = array('postcode'=>$tracking_id, 'tracking_name'=>'Заказ #'.$order['id'], 'order_id'=>$order['id'], 'client_name'=>$contact->get('name'),'client_phone'=>$contact->get('phone'),'client_email'=>$contact->get('email'));
			$upload_track = $this->getFromDigiApi("https://api.digi-post.ru/tracking?mode=addtrack&", $order_data, 'post');
			
			if ($upload_track->error == '13') {
				$track .= '<br><br><storng>Упппс!</strong> Мы попытались добавить идентификатор '.$tracking_id.' в систему api.digi-post.ru автоматически, но сделать это не получилось, так как <strong>ваша лицензия неактивна</strong>. <a target="_blank" href="https://api.digi-post.ru/invoice">Оплатите</a> использование сервиса api.digi-post.ru, чтобы добавлять неограниченное количество идентификаторов.';
			}
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


	public function getFromDigiApi($url, $data, $type) {

		$timeout = 7;
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
				@curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);


				if ($type == 'post') {
					@curl_setopt($ch, CURLOPT_POST, true);
					@curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
				}

				$res = @curl_exec($ch);
				$response = $this->getResult($res);

				//print_r($response);
				//var_dump($res);

				if (curl_errno($ch) != 0) {
					$curl_error = 'Curl error: '.curl_errno($ch);
					throw new waException($curl_error);
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