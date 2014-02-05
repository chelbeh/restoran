<?php

/**
 * Content block for recipients selection form.
 * Shows checklist to select subscribers.
 */
class mailerCampaignsRecipientsBlockSubscribersAction extends waViewAction
{
    public function execute()
    {
        $sm = new mailerSubscriberModel();
        $this->view->assign('contacts_count', $sm->countAll());
        $this->view->assign('all_selected_id', $this->params['all_selected_id']);
    }
}

