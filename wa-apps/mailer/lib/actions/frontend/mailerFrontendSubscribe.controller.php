<?php

class mailerFrontendSubscribeController extends waViewController
{
    public function execute()
    {
        $name = waRequest::post('name');
        $email = waRequest::post('email');
        $locale = waRequest::post('locale');
        $charset = waRequest::post('charset');

        if (!$locale || !waLocale::getInfo($locale)) {
            $locale = wa()->getLocale();
        }

        // Convert name and email to UTF-8
        if ($charset && $charset != 'utf8') {
            if ( ( $t = @iconv($charset, 'utf8//IGNORE', $name))) {
                $name = $t;
            }
            if ( ( $t = @iconv($charset, 'utf8//IGNORE', $email))) {
                $email = $t;
            }
        }

        // Validate email
        $email = trim($email);
        if (!$email) {
            throw new waException('No email to subscribe.', 404);
        }
        $ev = new waEmailValidator();
        if (!$ev->isValid($email)) {
            throw new waException('Email is invalid.', 404);
        }

        // Get contact_id by email
        $cem = new waContactEmailsModel();
        $contact_id = $cem->getContactIdByNameEmail($name, $email);
        if (!$contact_id) {
            $contact_id = $cem->getContactIdByEmail($email);
        }

        // Create new contact if no id found
        if (!$contact_id) {
            $contact = new waContact();
            $contact['locale'] = $locale;
            $contact['email'] = $email;
            if ($name) {
                $contact['name'] = $name;
            }
            $contact['create_method'] = 'subscriber';
            if ($contact->save()) {
                throw new waException('Unable to create contact.', 500);
            }
            $contact_id = $contact->getId();
        }

        // Remove contact from unsubscribers
        $um = new mailerUnsubscriberModel();
        $um->deleteByField(array(
            'email' => $email,
            'list_id' => array(0, 1),
        ));

        // Subscribe contact to default list (id=1)
        $sm = new mailerSubscriberModel();
        $sm->add($contact_id, 1, $email);
        echo $contact_id;
        exit;
    }

    //
    // Since we have no form or list management in current version,
    // some functionality is disabled.
    // !!! Old code below this line may be of some use in future.
    //

    public function old__execute()
    {
        if (waRequest::get('confirm')) {
            $this->confirm();
        } else {
            $this->subscribe();
        }
    }

    protected function subscribe()
    {
        $id = waRequest::get('form');
        $form_model = new mailerFormModel();
        $form = $form_model->getById($id);

        $name = waRequest::post('name');
        $email = waRequest::post('email');

        $charset = waRequest::post('charset');
        if ($charset && $charset != 'utf8') {
            $name = iconv($charset, 'utf8', $name);
        }

        if (!$form || !$email) {
            throw new waException("Page not found", 404);
        }

        $contact_emails_model = new waContactEmailsModel();

        $email_field = waContactFields::get('email');
        if ($email_field->isUnique()) {
            $contact_id = $contact_emails_model->getContactIdByEmail($email);
        } else {
            $contact_id = $contact_emails_model->getContactIdByNameEmail($name, $email);
        }
        if (!$contact_id) {
            $contact_id = $this->addContact($name, $email, $form);
        }
        if ($contact_id) {
            if ($form['list_id']) {
                if ($form['confirmation']) {
                    $this->sendConfirmation($contact_id, $email, $name, $form);
                } else {
                    $this->addSubscriber($contact_id, $form);
                }
            }
        }
    }

    protected function confirm()
    {
        $hash = waRequest::get('confirm');
        $temp = explode('-', substr($hash, 16, -16));
        if (count($temp) == 2) {
            if ($hash === $this->getHash($temp[0], $temp[1])) {
                $this->addSubscriber($temp[0], $temp[1]);
                echo "Теперь вы подписаны на рассылку!";
                return true;
            }
        }
        throw new waException("Page not found", 404);
    }

    protected function addContact($name, $email, $form)
    {
        $contact = new waContact();
        if ($name) {
            $contact['name'] = $name;
        }
        if ($form['locale']) {
            $contact['locale'] = $form['locale'];
        }
        $contact['email'] = $email;
        $contact['create_method'] = 'subscriber';
        if (!$contact->save()) {
            return $contact->getId();
        }
        return false;
    }

    protected function addSubscriber($contact_id, $list_id)
    {
        $subscriber_model = new mailerSubscriberModel();
        $subscriber_model->add($contact_id, $list_id);
    }

    protected function sendConfirmation($contact_id, $email, $name, $form)
    {
        $hash = $this->getHash($contact_id, $form['list_id']);
        $confirm_url = wa()->getRouteUrl('mailer/frontend/subscribe', true).'?confirm='.$hash;

        if ($form['confirmation_sender_id']) {
            $sender_model = new mailerSenderModel();
            $sender = $sender_model->getById($form['confirmation_sender_id']);
            $from_name = $sender['name'];
            $from_email = $sender['email'];
        } else {
            $app_settings_model = new waAppSettingsModel();
            $from_name = $app_settings_model->get('webasyst', 'name');
            $from_email = $app_settings_model->get('webasyst', 'email');
        }

        $message = new mailerSimpleMessage(array(
            'subject' => $form['confirmation_subject'],
            'body' => $form['confirmation_body'],
            'from_name' => $from_name,
            'from_email' => $from_email,
            'sender_id' => $form['confirmation_sender_id']
        ));
        $message->send($email, $name, array(
            '{$name}' => $name,
            '{$confirmation_link}' => $confirm_url
        ));
    }

    protected function getHash($contact_id, $list_id)
    {
        $app_settings_model = new waAppSettingsModel();
        $salt = $app_settings_model->get('mailer', 'subscribe_salt');
        if (!$salt) {
            $salt = uniqid(time());
            $app_settings_model->set('mailer', 'subscribe_salt', $salt);
        }
        $hash = md5($contact_id.$salt.$list_id);
        $hash = substr($hash, 0, 16).$contact_id.'-'.$list_id.substr($hash, -16);
        return $hash;
    }
}

