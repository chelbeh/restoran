<?php

/**
 * Removes contact from subscription.
 */
class mailerSubscribersDeleteController extends waJsonController
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }
        @list($contact_id, $email) = explode(',', waRequest::post('contact_id'));
        $um = new mailerSubscriberModel();
        $um->deleteByField(array(
            'contact_id' => $contact_id,
            'email' => $email,
        ));
    }
}

