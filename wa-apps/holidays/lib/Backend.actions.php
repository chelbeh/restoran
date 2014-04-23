<?php
/**
 * Pretty much the whole backend in one controller.
 */
class holidaysBackendActions extends holidaysViewActions
{
    protected function preExecute()
    {
        $this->setLayout(new holidaysBackendLayout());
    }

    protected function defaultAction()
    {
        $this->redirect('?action=overview');
    }

    public function vacationAction()
    {
        $m = new holidaysModel();
        if ( ( $delete_id = waRequest::request('delete'))) {
            $m->deletebyId($delete_id);
            $this->redirect('?action=overview');
            exit;
        }

        $id = waRequest::request('id', 0, 'int');
        if ($id) {
            $vacation = $m->getById($id);
            if (!$vacation) {
                throw new waException('Not found', 404);
            }
        } else {
            $vacation = $m->getEmptyRow();
        }

        // !!! check access rights
        // !!! read only mode?

        $errors = array();
        if (waRequest::post()) {
            $vac = waRequest::request('vacation', array(), 'array');
            $vac = array_intersect_key($vac, $vacation) + $vacation;
            unset($vac['id']);

            if (empty($vac['contact_id']) || !wa_is_int($vac['contact_id'])) {
                $errors['contact_id'] = _ws('This field is required.');
            }

            foreach(array('start', 'end') as $f) {
                $ts = strtotime($vac[$f]);
                if ($ts) {
                    $vac[$f] = date('Y-m-d', $ts);
                } else {
                    $errors[$f] = _ws('This field is required.');
                }
            }


            if (!$errors) {
                if (strtotime($vac['start']) > strtotime($vac['end'])) {
                    list($vac['end'], $vac['start']) = array($vac['start'], $vac['end']);
                }

                if (!wa_is_int($vac['number_of_days']) || $vac['number_of_days'] <= 0) {
                    $vac['number_of_days'] = 1 + floor((strtotime($vac['end']) - strtotime($vac['start']))/24/3600);
                }

                if ($id) {
                    $m->updateById($id, $vac);
                    $vac['id'] = $id;
                } else {
                    $id = $m->insert($vac);
                }
                $this->redirect('?action=overview');
            } else {
                $vac['id'] = '';
            }

            $vacation = $vac;
        }

        if ($vacation['contact_id']) {
            $contact = new waContact($vacation['contact_id']);
        } else if (waRequest::request('contact_id')) {
            $contact = new waContact(waRequest::request('contact_id', '0', 'int'));
            $contact->getName();
        }
        if(empty($contact)) {
            $contact = wa()->getUser();
        }

        $autocomplete_url = null;
        if (wa()->appExists('contacts')) {
            $autocomplete_url = wa()->getAppUrl('contacts').'?action=autocomplete';
        } else {
            $this->view->assign('contacts', $m->getContacts());
        }

        $this->view->assign('autocomplete_url', $autocomplete_url);
        $this->view->assign('uniqid', uniqid('f'));
        $this->view->assign('vacation', $vacation);
        $this->view->assign('contact', $contact);
        $this->view->assign('errors', $errors);
    }

    public function contactAction()
    {
        $id = waRequest::request('id', 0, 'int');
        if (!$id) {
            throw new waException('Not found', 404);
        }
        $contact = new waContact($id);
        $contact->getName();

        $m = new holidaysModel();
        $vacations = $m->getByContact($id);

        $this->view->assign('vacations', $vacations);
        $this->view->assign('contact', $contact);
    }

    public function helpAction()
    {
        // Nothing to do!
    }
}

