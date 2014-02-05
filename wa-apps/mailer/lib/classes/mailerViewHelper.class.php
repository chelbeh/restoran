<?php

/**
 * Available as {$wa->mailer} helper in smarty.
 */
class mailerViewHelper
{
    public function getConfigOption($opt)
    {
        return wa('mailer')->getConfig()->getOption($opt);
    }

    public function isAdmin()
    {
        return mailerHelper::isAdmin();
    }

    public function isAuthor()
    {
        return mailerHelper::isAuthor();
    }

    public function isInspector()
    {
        return mailerHelper::isInspector();
    }

    public function writable($campaign)
    {
        return mailerHelper::campaignAccess($campaign) >= 2;
    }
}

