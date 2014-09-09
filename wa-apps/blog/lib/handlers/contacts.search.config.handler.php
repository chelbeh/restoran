<?php

class blogContactsSearchConfigHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $app_id = 'blog';
        $user = wa()->getUser();
        if (!$user->getRights($app_id, 'backend')) {
            return array();
        }
        $apps = $user->getApps();
        return array(
            'name' => $apps[$app_id]['name'],
            'items' => array(
                'posts' => array(
                    'name' => 'Писали посты',
                    'join' => array(
                        'table' => 'blog_post',
                        'on' => 'c.id = :table.contact_id'
                    ),
                    'group_by' => 1
                ),
                'comments' => array(
                    'name' => 'Добавляли комментарии',
                    'join' => array(
                        'table' => 'blog_comment',
                        'on' => 'c.id = :table.contact_id',
                    ),
                    'group_by' => 1
                ),
            )
        );
    }
}
