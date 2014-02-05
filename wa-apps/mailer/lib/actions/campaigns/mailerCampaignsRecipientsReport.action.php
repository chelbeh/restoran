<?php

/**
 * List of recipients for campaign that is sent or being sent.
 */
class mailerCampaignsRecipientsReportAction extends mailerCampaignsReportAction
{
    public function execute()
    {
        $campaign_id = waRequest::get('campaign_id', 0, 'int');
        if (!$campaign_id) {
            throw new waException('No campaign id given.', 404);
        }

        // Campaign data
        $mm = new mailerMessageModel();
        $campaign = $mm->getById($campaign_id);
        if (!$campaign) {
            throw new waException('Campaign not found.', 404);
        }

        // Access control
        if (mailerHelper::campaignAccess($campaign) < 1) {
            throw new waException('Access denied.', 403);
        }

        $campaign['opened_count_available'] = mailerCampaignsArchiveAction::isOpenedCountAvailable($campaign);
        $campaign['has_unsubscribe_link'] = mailerCampaignsArchiveAction::hasUnsubscribeLink($campaign);

        // Campaign params
        $mpm = new mailerMessageParamsModel();
        $params = $mpm->getByMessage($campaign_id);

        // Campaign recipients
        if ($campaign['status'] <= 0) {
            throw new waException('Recipients report is unavailable for drafts.');
        }

        // List of recipients
        $recipients = $this->getRecipientsSent($campaign_id);

        // Recipients stats for pie graph
        $stats = $this->getStats($campaign_id);

        $this->view->assign('recipients', $recipients);
        $this->view->assign('campaign', $campaign);
        $this->view->assign('params', $params);
        $this->view->assign('stats', $stats);
    }

    protected function getRecipientsSent($campaign_id)
    {
        $start = waRequest::request('start', 0, 'int');
        $limit = 50;
        $search = waRequest::request('search');
        $error_class = waRequest::request('error_class');
        $status = array();
        foreach(explode(',', waRequest::request('status', '')) as $s) {
            if (wa_is_int($s)) {
                $status[] = $s;
            }
        }

        $lm = new mailerMessageLogModel();

        // Hacky way to add type classification to error tab
        $error_classes = null;
        if (!$search && $start == 0 && $status && (count($status) > 1 || $status[0] < 0)) {
            $type = $status[0] > -3 ? 'bounces' : 'exceptions';
            $stata = $type == 'bounces' ? '-1,-2' : '-3,-4';

            $total_count = 0;
            $error_classes = array();
            $sql = "SELECT status, error_class, COUNT(*) AS `count` FROM mailer_message_log WHERE message_id=:mid AND status IN ({$stata}) GROUP BY status, error_class ORDER BY `count` DESC";
            foreach($lm->query($sql, array('mid' => $campaign_id, 'stata' => $stata)) as $row) {
                $row['name'] = self::getErrorClass($row['status'], $row['error_class']);
                $row['param'] = 'status='.$row['status'].'&error_class='.urlencode(ifempty($row['error_class'], 'null'));
                $total_count += $row['count'];
                $error_classes[] = $row;
            }
            foreach($error_classes as &$row) {
                list($tmp, $row['percent']) = mailerCampaignsReportAction::formatInt($row['count'] * 100 / $total_count);
            }
            unset($row, $tmp);

            array_unshift($error_classes, array(
                'status' => $stata,
                'error_class' => null,
                'count' => $total_count,
                'name' => $type == 'bounces' ? _w('All bounces') : _w('All exceptions'),
                'param' => 'status='.$stata,
                'percent' => null,
            ));
        }

        // List of recipients
        $log = array();
        $total_rows = true;
        foreach($lm->getByMessage($campaign_id, $start, $limit, $status, $search, $error_class, $total_rows) as $l) {
            $l['name'] = empty($l['name']) ? _w('<no_name>') : $l['name'];
            $l['status_text'] = '';
            switch($l['status']) {
                case -4:
                case -3:
                case -2:
                case -1:
                    $error = self::getErrorClass($l['status'], $l['error_class']);
                    $css_class = $l['status'] == -3 ? 'earlier-unsubscribed' : 'error';
                    if ($l['error']) {
                        $css_class .= ' show-full-error-text';
                    }
                    $l['status_text'] = '<span class="'.$css_class.'">'.htmlspecialchars($error).'</span>';
                    break;
                case 0:
                    $l['status_text'] = '<span class="awaits-sending">'._w('Not sent yet').'</span>';
                    break;
                case 1:
                case 2:
                    $l['status_text'] = '<span class="unknown">'._w('Unknown').'</span>';
                    break;
                case 3:
                case 4:
                    $l['status_text'] = '<span class="opened">'._w('Opened').'</span>';
                    break;
                case 5:
                    $l['status_text'] = '<span class="unsubscribed">'._w('Unsubscribed ').'</span>';
                    break;
            }
            $log[$l['id']] = $l;
        }
        unset($l);

        $parameters = array(
            'start='.($start+$limit),
        );
        if ($status) {
            $parameters[] = 'status='.implode(',', $status);
        }
        if ($search) {
            $parameters[] = 'search='.urlencode($search);
        }
        if ($error_class) {
            $parameters[] = 'error_class='.urlencode($error_class);
        }
        $parameters = implode('&', $parameters);

        $this->view->assign('start', $start);
        $this->view->assign('has_more', $total_rows > $start + $limit);
        $this->view->assign('parameters', $parameters);
        $this->view->assign('total_rows', $total_rows);
        $this->view->assign('error_classes', $error_classes);
        return $log;
    }

    public static function getErrorClass($status, $error_class)
    {
        if ($error_class) {
            return $error_class;
        }

        switch ($status) {
            case -4:
                return _w('Delivering error in past campaigns');
            case -3:
                return _w('Earlier unsubscribed');
            case -2:
                return _w('Unknown error');
            case -1:
                return _w('Bounced by sender mail server');
        }

        return '';
    }
}

