<?php

return [
    [
        'id' => 'cmsable.page',
        'singularName' => 'Standard Seite',
        'pluralName' => 'Standard Seiten',
        'description' => 'Einfache Seite, die einen CMS Inhalt anzeigt',
        'category' => 'default',
        'targetPath' => 'pages/current'
    ],
    [
        'id' =>'cmsable.home-page',
        'singularName' => 'Homepage-Seite',
        'pluralName' => 'Homepage-Seiten',
        'description' => 'Die Startseite welche Aktionen und Aktuelles zeigen kann',
        'category' => 'default',
        'targetPath' => '/',
        'routeScope' => ''
    ],
    [
        'id' =>'cmsable.childlisting-page',
        'singularName' => 'Unterseiten-Liste',
        'pluralName' => 'Unterseiten-Listen',
        'description' => 'Eine Seite welche Unterseiten auflistet',
        'category' => 'default',
        'targetPath' => 'list-childs'
    ],
    [
        'id' =>'cmsable.redirector',
        'controller' => '\Cmsable\Controller\RedirectorController',
        'singularName' => 'Weiterleitungs-Seite',
        'pluralName' => 'Weiterleitungs-Seiten',
        'description' => 'Diese Seite leitet auf andere Seiten weiter',
        'category' => 'default',
        'formPluginClass' => 'Cmsable\Controller\SiteTree\Plugin\RedirectorPlugin',
        'targetPath' => 'cms-redirect'
    ],
];