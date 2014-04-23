<?php

class holidaysBackendLayout extends waLayout
{
    public function execute()
    {
        $m = new holidaysModel();
        $contacts = $m->getContacts();
        $this->view->assign('contacts', $contacts);
    }
}

