<?php

/**
 * Implements core recipient selection criteria.
 * See recipients.form event description for details.
 */
class mailerMailerRecipientsFormHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $campaign = $params['campaign'];
        $recipients = $params['recipients'];
        $recipients_groups = &$params['recipients_groups'];

        $recipients_groups['languages'] = array(
            'name' => _w('Languages'),
            'content' => '',
            'opened' => false,
            'included_in_all_contacts' => true,
            'comment' => _w('Your contacts may be speaking different languages. You can limit the recipient list by selecting only the desired languages.'),

            // not part of event interface, but used internally here
            'selected' => array(),
        );
        $recipients_groups['categories'] = array(
            'name' => _w('Categories'),
            'content' => '',
            'opened' => false,
            'included_in_all_contacts' => true,
            'comment' => _w('Categories are groups of contacts which you can freely manage in the Contacts application. In addition to manually created categories, there are also system categories created by other Webasyst applications; e.g., Shop-Script or Blog. Those categories contain contacts added by the corresponding applications: customers of the online store or authors of comments posted in the blog.'),

            // not part of event interface, but used internally here
            'selected' => array(),
            'all_selected_id' => false,
        );
        $recipients_groups['subscribers'] = array(
            'name' => _w('Subscribers'),
            'content' => '',
            'opened' => false,
            'included_in_all_contacts' => true,
            'comment' => _w('This option allows selecting contacts who have opted for receiving your email newsletters using a subscription form (see Subscribers section).'),

            // not part of event interface, but used internally here
            'all_selected_id' => false,
        );
        $recipients_groups['flat_list'] = array(
            'name' => _w('Additional emails'),
            'content' => null,
            'opened' => false,
            'comment' => _w('Use this field  to manually enter any additional email addresses. If such addresses are not yet contained in the Contacts application, they will be added there as new contacts once the sending of this message is completed.'),

            // not part of event interface, but used internally here
            'count' => 0,
            'ids' => array(),
            'all_emails' => '',
        );

        // Loop through all message_resipients and gather data avout what is selected
        foreach($recipients as $r_id => $value) {

            // Being paranoid...
            if (!strlen($value)) {
                continue;
            }

            // Skip list types supported by plugins
            if ($value{0} == '@') {
                continue;
            }

            // Is it subscribers list id?
            if (wa_is_int($value)) {
                // Currently, only "All subscribers" option is supported,
                // so we don't even check for actual list id.
                $recipients_groups['subscribers']['all_selected_id'] = $r_id;
                $recipients_groups['subscribers']['opened'] = true;
                continue;
            }

            // Is it a list of emails?
            if ($value{0} != '/') {
                // Parse and count emails in this list
                // to count total number of emails
                $flat_list = array();
                $parser = new mailerMailAddressParser($value);
                foreach($parser->parse() as $a) {
                    $flat_list[mb_strtolower($a['email'])] = true;
                }

                $recipients_groups['flat_list']['ids'][] = $r_id;
                $recipients_groups['flat_list']['count'] += count($flat_list);
                $recipients_groups['flat_list']['all_emails'] .= "\n".trim($value);
                $recipients_groups['flat_list']['opened'] = true;
                unset($flat_list);
                continue;
            }

            //
            // Otherwise, it is a ContactsCollection hash.
            //

            // See if the hash is of one of supported types
            if (FALSE !== strpos($value, '/category/')) {
                $category_id = explode('/', $value);
                $category_id = end($category_id);
                if ($category_id && wa_is_int($category_id)) {
                    $recipients_groups['categories']['selected'][$r_id] = $category_id;
                    $recipients_groups['categories']['opened'] = true;
                } else {
                    $recipients_groups['categories']['all_selected_id'] = $r_id;
                }
            } else if (FALSE !== strpos($value, '/locale=')) {
                $locale = explode('=', $value);
                $locale = end($locale);
                $recipients_groups['languages']['selected'][$r_id] = $locale;
                $recipients_groups['languages']['opened'] = true;
            } else if ($value == '/') {
                $recipients_groups['categories']['all_selected_id'] = $r_id;
            } else {
                // Not one of supported hash types. Ignore it.
                continue;
            }
        }

        //
        // Now, as we collected data about which categoies, locales, etc. are selected,
        // use it to prepare HTML parts for the form.
        //

        try {
            $recipients_groups['languages']['content'] = trim(wao(new mailerCampaignsRecipientsBlockLanguagesAction($recipients_groups['languages']))->display());
        } catch (Exception $e) {
            // hide languages group when nothing is selected and there's only one language
            unset($recipients_groups['languages']);
        }

        try {
            $recipients_groups['categories']['content'] = trim(wao(new mailerCampaignsRecipientsBlockCategoriesAction($recipients_groups['categories']))->display());
        } catch (Exception $e) {
            // hide categories block when nothing is selected and there are no available categories
            unset($recipients_groups['categories']);
        }

        $recipients_groups['subscribers']['content'] = trim(wao(new mailerCampaignsRecipientsBlockSubscribersAction($recipients_groups['subscribers']))->display());

        $recipients_groups['flat_list']['content'] = trim(wao(new mailerCampaignsRecipientsBlockFlatListAction($recipients_groups['flat_list']))->display());
        if ($recipients_groups['flat_list']['count']) {
            $recipients_groups['flat_list']['name'] .= '<span class="hide-when-modified"> ('.$recipients_groups['flat_list']['count'].')</span>';
        }
    }
}

