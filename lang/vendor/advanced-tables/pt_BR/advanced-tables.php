<?php

return [

    'forms' => [

        'user' => 'Usuário',
        'resource' => 'Recurso',
        'note' => 'Nota',

        'status' => [

            'label' => 'Status',

        ],

        'name' => [

            'label' => 'Nome',
            'helper_text' => 'Escolha um nome curto, porém fácil de identificar para sua visão',

        ],

        'filters' => [

            'label' => 'View summary',
            'helper_text' => 'Essas configurações serão salvas com esta visão',

        ],

        'panels' => [

            'label' => 'Panéis',

        ],

        'preset_view' => [

            'label' => 'visão predefinida',
            'query_label' => 'Consulta de vista predefinida',
            'helper_text_start' => 'Você está usando a visão predefinida',
            'helper_text_end' => ' com base nessa visão. As visões predefinidas têm suas próprias configurações independentes, além das configurações selecionadas.',

        ],

        'icon' => [

            'label' => 'Ícone',
            'placeholder' => 'Selecione um ícone',

        ],

        'color' => [

            'label' => 'Cor',

        ],

        'public' => [

            'label' => 'Tornar público',
            'toggle_label' => 'É público',
            'helper_text' => 'Tornar essa visão disponível para todos os usuários.',

        ],

        'favorite' => [

            'label' => 'Adicionar aos favoritos',
            'toggle_label' => 'É meu favorito',
            'helper_text' => 'Adicionar essa visão aos meus favoritos',

        ],

        'global_favorite' => [

            'label' => 'Tornar favorito global',
            'toggle_label' => 'É favorito global',
            'helper_text' => 'Adicionar essa visão a lista de favoritos de todos os usuários',

        ],

    ],

    'advanced_search' => [

        'constraints' => [

            'contains' => 'Contém',
            'does_not_contain' => 'Não contém',
            'equals' => 'É igual a',
            'does_not_equal' => 'Não é igual a',
            'starts_with' => 'Começa com',
            'does_not_start_with' => 'Não começa com',
            'ends_with' => 'Termina com',
            'does_not_end_with' => 'Não termina com',

        ],

        'constraints_singular' => [

            'contains' => 'contém',
            'does_not_contain' => 'não contém',
            'equals' => 'é igual a',
            'does_not_equal' => 'não é igual a',
            'starts_with' => 'começa com',
            'does_not_start_with' => 'não começa com',
            'ends_with' => 'termina com',
            'does_not_end_with' => 'não termina com',

        ],

        'constraints_plural' => [

            'contains' => 'contêm',
            'does_not_contain' => 'não contêm',
            'equals' => 'são iguais a',
            'does_not_equal' => 'não são iguais a',
            'starts_with' => 'começam com',
            'does_not_start_with' => 'não começam com',
            'ends_with' => 'terminam com',
            'does_not_end_with' => 'não terminam com',

        ],

        'indicator_more' => '+ mais :count',

        'boolean' => [
            'and' => 'e',
            'or' => 'ou',
        ],

        'dropdown' => [

            'no_results' => 'Nenhuma opção correspondente',
            'constraints_header' => 'Restrições',
            'columns_header' => 'Colunas',
            'database_header' => 'Banco de dados',
            'boolean_header' => 'Booleano',

        ],

        'search_reference' => [

            'tooltip' => 'Referência de busca',
            'heading' => 'Referência de busca',
            'navigation' => 'Navegação',
            'focus_search' => 'Focar na busca',
            'open_dropdown' => 'Abrir menu suspenso',
            'navigate_dropdown' => 'Navegar no menu suspenso',
            'select_tag' => 'Selecionar tag',
            'remove_tag' => 'Remover tag',
            'constraints' => 'Restrições',
            'search' => 'busca',
            'exact_phrase' => 'Frase exata',
            'search_words' => 'palavras de busca',
            'columns' => 'Colunas',
            'single' => 'Simples',
            'multiple' => 'Múltiplo',
            'combined' => 'Combinado',
            'column_example_column' => 'Nome',
            'column_example_columns' => 'Nome,E-mail',
            'boolean' => 'Booleano',
            'and_operator' => 'E',
            'or_operator' => 'OU',

        ],

    ],

    'quick_filters' => [

        'more_indicator_labels' => 'e mais :count',

    ],

    'multi_sort' => [

        'label' => 'Ordenar por',
        'add_column_label' => 'Adicionar coluna',
        'reset_label' => 'Redefinir',

    ],

    'notifications' => [

        'preset_views' => [

            'title' => 'Não foi possível criar a visão',
            'body' => 'Novas visões não podem ser criadas a partir de uma visão predefinida. Crie sua visão usando a visão padrão ou qualquer visão criada pelo usuário.',

        ],

        'save_view' => [

            'saved' => [

                'title' => 'Salvo',

            ],

        ],

        'edit_view' => [

            'saved' => [

                'title' => 'Salvo',

            ],

        ],

        'replace_view' => [

            'replaced' => [

                'title' => 'Substituído',

            ],

        ],

    ],

    'quick_save' => [

        'save' => [

            'modal_heading' => 'Salvar visão',
            'submit_label' => 'Salvar visão',

        ],

    ],

    'select' => [

        'label' => 'Visões',
        'placeholder' => 'Selecionar visão',

    ],

    'status' => [

        'approved' => 'aprovada',
        'pending' => 'pendente',
        'rejected' => 'rejeitada',

    ],

    'tables' => [

        'favorites' => [

            'default' => 'Padrão',

        ],

        'columns' => [

            'user' => 'Usuário',
            'icon' => 'Ícone',
            'color' => 'Cor',
            'name' => 'Nome da visão',
            'panel' => 'Painel',
            'resource' => 'Recurso',
            'status' => 'Status',
            'filters' => 'Filtros',
            'is_public' => 'Público',
            'is_user_favorite' => 'Meu favorito',
            'is_global_favorite' => 'Global',
            'sort_order' => 'Ordem',
            'users_favorite_sort_order' => 'Ordem dos favoritos',

        ],

        'tooltips' => [

            'is_user_favorite' => [

                'unfavorite' => 'Remover favorito',
                'favorite' => 'Tornar favorito',

            ],

            'is_public' => [

                'make_private' => 'Tornar privado',
                'make_public' => 'Tornar público',

            ],

            'is_global_favorite' => [

                'make_personal' => 'Tornar pessoal',
                'make_global' => 'Tornar global',

            ],

        ],

        'actions' => [

            'buttons' => [

                'open' => 'Abrir',
                'approve' => 'Aprovar',

            ],

        ],

    ],

    'toggled_columns' => [

        'visible' => 'Visível',
        'hidden' => 'Oculto',
        'enable_all' => 'Habilitar tudo',

    ],

    'user_view_resource' => [

        'model_label' => 'Visão',
        'plural_model_label' => 'Visões',
        'navigation_label' => 'Visões',

    ],

    'view_manager' => [

        'actions' => [

            'add_view_to_favorites' => 'Adicionar aos favoritos',
            'set_as_managed_default_view' => 'Definir como padrão',
            'remove_as_managed_default_view' => 'Remover como padrão',
            'apply_view' => 'Aplicar visão',
            'save' => 'Salvar',
            'save_view' => 'Salvar visão',
            'delete_view' => 'Apagar visão',
            'delete_view_description' => 'Esta visão é uma :type. Outros usuários perderão o acesso à sua visão. Tem certeza de que deseja prosseguir?',
            'delete_view_modal_submit_label' => 'Apagar',
            'remove_view_from_favorites' => 'Remover dos favoritos',
            'edit_view' => 'Editar visão',
            'replace_view' => 'Substituir visão',
            'replace_view_modal_description' => 'Você está prestes a substituir esta visão salva pela configuração atual da tabela. Tem certeza de que gostaria de fazer isso?',
            'replace_view_modal_submit_label' => 'Substituir',
            'show_view_manager' => 'Mostrar gerenciador de visões',

        ],

        'badges' => [

            'active' => 'ativo',
            'preset' => 'predefinida',
            'user' => 'usuário',
            'global' => 'global',
            'public' => 'público',
            'default' => 'padrão',

        ],

        'heading' => 'Gerenciador de visões',

        'table_heading' => 'Visões',

        'no_views' => 'Nenhuma visão',

        'subheadings' => [

            'user_favorites' => 'Visões favoritas',
            'user_views' => 'Visões do usuário',
            'preset_views' => 'Visões predefinidas',
            'global_views' => 'Visões Globais',
            'public_views' => 'Visões Públicas',

        ],

    ],
];
