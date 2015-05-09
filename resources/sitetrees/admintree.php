<?php

return [
    'id'                => 'root',
    'page_type'         => 'cmsable.page',
    'url_segment'       => '/',
    'title'             => 'cmsable::sitetree.root.title',
    'menu_title'        => 'cmsable::sitetree.root.menu_title',
    'show_in_menu'      => false,
    'show_in_aside_menu'=> false,
    'show_in_search'    => false,
    'redirect_type'     => 'none',
    'redirect_target'   => 0,
    'content'           => '',
    'view_permission'   => 'cms.access',
    'edit_permission'   => 'superuser',

    'children'  => [
        [
            'id'                => 'dashboard',
            'page_type'         => 'cmsable.admin-dashboard',
            'url_segment'       => 'home',
            'icon'              => 'fa-dashboard',
            'title'             => 'cmsable::sitetree.admin-dashboard.title',
            'menu_title'        => 'cmsable::sitetree.admin-dashboard.menu_title',
            'show_in_menu'      => true,
            'show_in_aside_menu'=> false,
            'show_in_search'    => true,
            'show_when_authorized' => true,
            'redirect_type'     => 'none',
            'redirect_target'   => 0,
            'content'           => '',
            'view_permission'   => 'cms.access',
            'edit_permission'   => 'superuser',
        ],
        [
            'id'                => 'sitetree-parent',
            'page_type'         => 'cmsable.redirector',
            'url_segment'       => 'pages',
            'icon'              => 'fa-sitemap',
            'title'             => 'cmsable::sitetree.sitetree-parent.title',
            'menu_title'        => 'cmsable::sitetree.sitetree-parent.menu_title',
            'show_in_menu'      => true,
            'show_in_aside_menu'=> false,
            'show_in_search'    => true,
            'show_when_authorized' => true,
            'redirect_type'     => 'internal',
            'redirect_target'   => 'firstchild',
            'content'           => '',
            'view_permission'   => 'cms.access',
            'edit_permission'   => 'superuser',

            'children' => [
                [
                    'id'                => 'sitetree-editor',
                    'page_type'         => 'cmsable.sitetree-editor',
                    'url_segment'       => 'edit',
                    'icon'              => 'fa-sitemap',
                    'title'             => 'cmsable::sitetree.sitetree-editor.title',
                    'menu_title'        => 'cmsable::sitetree.sitetree-editor.menu_title',
                    'show_in_menu'      => true,
                    'show_in_aside_menu'=> false,
                    'show_in_search'    => true,
                    'show_when_authorized' => true,
                    'redirect_type'     => 'none',
                    'redirect_target'   => '0',
                    'content'           => '',
                    'view_permission'   => 'cms.access',
                    'edit_permission'   => 'superuser'
                ]
            ]
        ]
    ]
];