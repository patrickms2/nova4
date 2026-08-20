<?php

return [

    'forms' => [

        'user' => 'Utilisateur',
        'resource' => 'Ressource',
        'note' => 'Note',

        'status' => [

            'label' => 'Statut',

        ],

        'name' => [

            'label' => 'Nom',
            'helper_text' => 'Choisissez un nom court mais facilement identifiable pour votre vue',

        ],

        'panels' => [

            'label' => 'Panneaux',

        ],

        'filters' => [

            'label' => 'Résumé de la vue',
            'helper_text' => 'Ces configurations seront enregistrées avec cette vue',

        ],

        'preset_view' => [

            'label' => 'Vue prédéfinie',
            'query_label' => 'Requête de vue prédéfinie',
            'helper_text_start' => 'Vous utilisez la vue prédéfinie ',
            'helper_text_end' => ' comme base pour cette vue. Les vues prédéfinies peuvent avoir leur propre configuration indépendante en plus des configurations que vous avez sélectionnées.',

        ],

        'icon' => [

            'label' => 'Icône',
            'placeholder' => 'Sélectionner une icône',
        ],

        'color' => [

            'label' => 'Couleur',

        ],

        'public' => [

            'label' => 'Rendre publique',
            'toggle_label' => 'Est publique',
            'helper_text' => 'Rendre cette vue disponible à tous les utilisateurs',

        ],

        'favorite' => [

            'label' => 'Ajouter aux favoris',
            'toggle_label' => 'Est favori',
            'helper_text' => 'Ajoutez cette vue à vos favoris',

        ],

        'global_favorite' => [

            'label' => 'Définir comme favori global',
            'toggle_label' => 'Est favori global',
            'helper_text' => 'Ajouter cette vue aux favoris de tous les utilisateurs',

        ],

    ],

    'advanced_search' => [

        'constraints' => [

            'contains' => 'Contient',
            'does_not_contain' => 'Ne contient pas',
            'equals' => 'Est égal à',
            'does_not_equal' => 'N\'est pas égal à',
            'starts_with' => 'Commence par',
            'does_not_start_with' => 'Ne commence pas par',
            'ends_with' => 'Se termine par',
            'does_not_end_with' => 'Ne se termine pas par',

        ],

        'constraints_singular' => [

            'contains' => 'contient',
            'does_not_contain' => 'ne contient pas',
            'equals' => 'est égal à',
            'does_not_equal' => 'n\'est pas égal à',
            'starts_with' => 'commence par',
            'does_not_start_with' => 'ne commence pas par',
            'ends_with' => 'se termine par',
            'does_not_end_with' => 'ne se termine pas par',

        ],

        'constraints_plural' => [

            'contains' => 'contiennent',
            'does_not_contain' => 'ne contiennent pas',
            'equals' => 'sont égaux à',
            'does_not_equal' => 'ne sont pas égaux à',
            'starts_with' => 'commencent par',
            'does_not_start_with' => 'ne commencent pas par',
            'ends_with' => 'se terminent par',
            'does_not_end_with' => 'ne se terminent pas par',

        ],

        'indicator_more' => '+ :count de plus',

        'boolean' => [
            'and' => 'et',
            'or' => 'ou',
        ],

        'dropdown' => [

            'no_results' => 'Aucune option correspondante',
            'constraints_header' => 'Contraintes',
            'columns_header' => 'Colonnes',
            'database_header' => 'Base de données',
            'boolean_header' => 'Booléen',

        ],

        'search_reference' => [

            'tooltip' => 'Référence de recherche',
            'heading' => 'Référence de recherche',
            'navigation' => 'Navigation',
            'focus_search' => 'Cibler la recherche',
            'open_dropdown' => 'Ouvrir la liste',
            'navigate_dropdown' => 'Naviguer dans la liste',
            'select_tag' => 'Sélectionner la balise',
            'remove_tag' => 'Retirer la balise',
            'constraints' => 'Contraintes',
            'search' => 'recherche',
            'exact_phrase' => 'Expression exacte',
            'search_words' => 'mots de recherche',
            'columns' => 'Colonnes',
            'single' => 'Simple',
            'multiple' => 'Multiple',
            'combined' => 'Combiné',
            'column_example_column' => 'Nom',
            'column_example_columns' => 'Nom,E-mail',
            'boolean' => 'Booléen',
            'and_operator' => 'ET',
            'or_operator' => 'OU',

        ],

    ],

    'quick_filters' => [

        'more_indicator_labels' => 'et :count de plus',

    ],

    'multi_sort' => [

        'label' => 'Tri multiple par',
        'add_column_label' => 'Ajouter une colonne',
        'reset_label' => 'Réinitialiser',

    ],

    'notifications' => [

        'preset_views' => [

            'title' => 'Impossible de créer une vue',
            'body' => "De nouvelles vues ne peuvent pas être créées à partir d'une vue prédéfinie. Veuillez créer votre vue en utilisant la vue par défaut ou toute vue créée par l'utilisateur.",

        ],

        'save_view' => [

            'saved' => [

                'title' => 'Enregistré',

            ],

        ],

        'edit_view' => [

            'saved' => [

                'title' => 'Enregistré',

            ],

        ],

        'replace_view' => [

            'replaced' => [

                'title' => 'Remplacé',

            ],

        ],

    ],

    'quick_save' => [

        'save' => [

            'modal_heading' => 'Enregistrer la vue',
            'submit_label' => 'Enregistrer la vue',

        ],

    ],

    'select' => [

        'label' => 'Vues',
        'placeholder' => 'Sélectionner une vue',

    ],

    'status' => [

        'approved' => 'approuvé',
        'pending' => 'en attente',
        'rejected' => 'rejeté',

    ],

    'tables' => [

        'favorites' => [

            'default' => 'Défaut',

        ],

        'columns' => [

            'user' => 'Utilisateur',
            'icon' => 'Icône',
            'color' => 'Couleur',
            'name' => 'Nom de la vue',
            'panel' => 'Panneau',
            'resource' => 'Ressource',
            'status' => 'Statut',
            'filters' => 'Filtres',
            'is_public' => 'Publique',
            'is_user_favorite' => 'Mon favori',
            'is_global_favorite' => 'Global',
            'sort_order' => 'Ordre de tri',
            'users_favorite_sort_order' => 'Ordre de tri des favoris',

        ],

        'tooltips' => [

            'is_user_favorite' => [

                'unfavorite' => 'Retirer des favoris',
                'favorite' => 'Ajouter aux favoris',

            ],

            'is_public' => [

                'make_private' => 'Rendre privé',
                'make_public' => 'Rendre publique',

            ],

            'is_global_favorite' => [

                'make_personal' => 'Rendre personnel',
                'make_global' => 'Rendre global',

            ],

        ],

        'actions' => [

            'buttons' => [

                'open' => 'Ouvrir',
                'approve' => 'Approuver',

            ],

        ],

    ],

    'toggled_columns' => [

        'visible' => 'Visible',
        'hidden' => 'Cachée',
        'enable_all' => 'Activer tout',

    ],

    'user_view_resource' => [

        'model_label' => 'Vue',
        'plural_model_label' => 'Vues',
        'navigation_label' => 'Vues',

    ],

    'view_manager' => [

        'actions' => [

            'add_view_to_favorites' => 'Ajouter aux favoris',
            'set_as_managed_default_view' => 'Définir comme par défaut',
            'remove_as_managed_default_view' => 'Supprimer comme par défaut',
            'apply_view' => 'Appliquer la vue',
            'save' => 'Enregistrer',
            'save_view' => 'Enregistrer la vue',
            'delete_view' => 'Supprimer la vue',
            'delete_view_modal_submit_label' => 'Supprimer',
            'delete_view_description' => 'Cette vue est une vue :type. Les autres utilisateurs perdront l\'accès à votre vue. Êtes-vous sûr de vouloir continuer ?',
            'remove_view_from_favorites' => 'Retirer des favoris',
            'edit_view' => 'Modifier la vue',
            'replace_view' => 'Remplacer la vue',
            'replace_view_modal_description' => 'Vous êtes sur le point de remplacer cette vue enregistrée par la configuration actuelle de la table. Êtes-vous sûr de vouloir faire cela?',
            'replace_view_modal_submit_label' => 'Remplacer',
            'show_view_manager' => 'Afficher le gestionnaire de vues',

        ],

        'badges' => [

            'active' => 'active',
            'preset' => 'prédéfinie',
            'user' => 'utilisateur',
            'global' => 'mondiale',
            'public' => 'publique',
            'default' => 'défaut',

        ],

        'heading' => 'Gestionnaire de vues',

        'table_heading' => 'Vues',

        'no_views' => 'Aucune vue',

        'subheadings' => [

            'user_favorites' => 'Vues favorites',
            'user_views' => 'Vues des utilisateurs',
            'preset_views' => 'Vues prédéfinies',
            'global_views' => 'Vues mondiales',
            'public_views' => 'Vues publiques',

        ],

    ],
];
