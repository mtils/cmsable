<?php

return array(

    'page' => array(
        'name' => 'Seite|Seiten',
        'fields' => array(
            'menu_title'            => 'Navigationsname',
            'url_segment'           => 'URL-Segment',
            'title'                 => 'Titel',
            'content'               => 'Inhalt',
            'visibility'            => 'Sichtbarkeit',
            'show_in_menu'          => 'In Hauptmenü anzeigen?',
            'show_in_aside_menu'    => 'In Fußmenü anzeigen?',
            'show_in_search'        => 'In Suche anzeigen?',
            'view_permission'       => 'Berechtigung um Seite aufrufen zu können:',
            'edit_permission'       => 'Berechtigung um Seite bearbeiten zu können:',
            'delete_permission'     => 'Berechtigung um Seite löschen zu können:',
            'add_child_permission'  => 'Berechtigung Unterseiten zu dieser Seite anlegen zu können:',
            'redirect_type'         => 'Art der Weiterleitung',
            'redirect_target'       => 'Ziel der Weiterleitung'
        ),
        'enums' => array(
            'redirect_type' => array(
                'internal' => 'Auf interne Seite weiterleiten',
                'external' => 'Auf externe Seite weiterleiten',
                'none'     => 'Keine Weiterleitung'
            ),
            'visibility' => array(
                'show_in_menu'          => 'In Hauptmenü anzeigen?',
                'show_in_aside_menu'    => 'In Fußmenü anzeigen?',
                'show_in_search'        => 'In Suche anzeigen?',
                'show_when_authorized'  => 'Auch anzeigen wenn Benutzer angemeldet ist?',
            )
        )
    )

); 
