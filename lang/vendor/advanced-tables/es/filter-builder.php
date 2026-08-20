<?php

return [

    'form' => [

        'add_filter' => 'Agregar filtro',
        'expand_view' => 'Expandir',
        'new_filter_group' => 'Nuevo grupo',
        'or' => 'o',
        'remove_filter' => 'Remover',
        'recent' => 'Reciente',
        'relative' => 'Relativo',
        'absolute' => 'Absoluto',
        'empty' => 'Vacío',
        'no_results' => 'No se encontraron resultados',

    ],

    'filters' => [

        'indicator_name' => 'Grupo',

        'operators' => [

            'and' => 'y',
            'or' => 'o',

        ],

        'numeric' => [

            'equal_to' => [
                'indicator' => 'es igual a',
                'option' => 'igual a',
            ],

            'not_equal_to' => [
                'indicator' => 'no es igual a',
                'option' => 'no igual a',
            ],

            'greater_than' => [
                'indicator' => 'es mayor que',
                'option' => 'mayor que',
            ],

            'greater_than_or_equal_to' => [
                'indicator' => 'es mayor o igual a',
                'option' => 'mayor o igual a',
            ],

            'less_than' => [
                'indicator' => 'es menor que',
                'option' => 'menor que',
            ],

            'less_than_or_equal_to' => [
                'indicator' => 'es menor o igual a',
                'option' => 'menor o igual a',
            ],

            'between' => [
                'indicator' => 'es entre',
                'option' => 'entre',
            ],

            'not_between' => [
                'indicator' => 'no es entre',
                'option' => 'no entre',
            ],

            'positive' => [
                'indicator' => 'es positivo',
                'option' => 'es positivo',
            ],

            'negative' => [
                'indicator' => 'es negativo',
                'option' => 'es negativo',
            ],

        ],

        'text' => [

            'is' => [
                'indicator' => 'es',
                'option' => 'es',
            ],

            'is_not' => [
                'indicator' => 'no es',
                'option' => 'no es',
            ],

            'starts_with' => [
                'indicator' => 'comienza con',
                'option' => 'comienza con',
            ],

            'does_not_start_with' => [
                'indicator' => 'no comienza con',
                'option' => 'no comienza con',
            ],

            'ends_with' => [
                'indicator' => 'termina con',
                'option' => 'termina con',
            ],

            'does_not_end_with' => [
                'indicator' => 'no termina con',
                'option' => 'no termina con',
            ],

            'contains' => [
                'indicator' => 'contiene',
                'option' => 'contiene',
            ],

            'does_not_contain' => [
                'indicator' => 'no contiene',
                'option' => 'no contiene',
            ],

            'is_empty' => [
                'indicator' => 'está vacío',
                'option' => 'está vacío',
            ],

            'is_not_empty' => [
                'indicator' => 'no está vacío',
                'option' => 'no está vacío',
            ],

        ],

        'date' => [

            'yesterday' => [
                'indicator' => 'es ayer',
                'option' => 'es ayer',
            ],

            'today' => [
                'indicator' => 'es hoy',
                'option' => 'es hoy',
            ],

            'tomorrow' => [
                'indicator' => 'es mañana',
                'option' => 'es mañana',
            ],

            'in_this' => [
                'indicator' => 'es este',
                'option' => 'es este',
            ],

            'is_next' => [
                'indicator' => 'es el próximo',
                'option' => 'es el próximo',
            ],

            'is_last' => [
                'indicator' => 'es el último',
                'option' => 'es el último',
            ],

            'in_the_next' => [
                'indicator' => 'es en el próximo',
                'option' => 'es en el próximo',
            ],

            'in_the_last' => [
                'indicator' => 'es en el último',
                'option' => 'es en el último',
            ],

            'exactly' => [
                'indicator' => 'es exactamente',
                'option' => 'es exactamente',
            ],

            'before' => [
                'indicator' => 'es antes',
                'option' => 'es antes',
            ],

            'after' => [
                'indicator' => 'es despues',
                'option' => 'es despues',
            ],

            'between' => [
                'indicator' => 'es entre',
                'option' => 'es entre',
            ],

            'is_date' => [
                'indicator' => 'es',
                'option' => 'es la fecha',
            ],

            'before_date' => [
                'indicator' => 'es antes',
                'option' => 'es antes de la fecha',
            ],

            'after_date' => [
                'indicator' => 'es después',
                'option' => 'es después de la fecha',
            ],

            'between_dates' => [
                'indicator' => 'es entre',
                'option' => 'es entre las fechas',
            ],

            'is_empty' => [
                'indicator' => 'está vacío',
                'option' => 'está vacío',
            ],

            'is_not_empty' => [
                'indicator' => 'no está vacío',
                'option' => 'no está vacío',
            ],

            'unit' => [
                'week' => [
                    'indicator_singular' => 'semana',
                    'indicator' => 'semana',
                    'option' => 'semana',
                ],

                'month' => [
                    'indicator_singular' => 'mes',
                    'indicator' => 'mes',
                    'option' => 'mes',
                ],

                'quarter' => [
                    'indicator_singular' => 'cuarto',
                    'indicator' => 'cuarto',
                    'option' => 'cuarto',
                ],

                'year' => [
                    'indicator_singular' => 'año',
                    'indicator' => 'año',
                    'option' => 'año',
                ],

                'days' => [
                    'indicator_singular' => 'día',
                    'indicator' => 'días',
                    'option' => 'días',
                ],

                'weeks' => [
                    'indicator_singular' => 'semana',
                    'indicator' => 'semanas',
                    'option' => 'semanas',
                ],

                'months' => [
                    'indicator_singular' => 'mes',
                    'indicator' => 'meses',
                    'option' => 'meses',
                ],

                'quarters' => [
                    'indicator_singular' => 'cuarto',
                    'indicator' => 'cuartos',
                    'option' => 'cuartos',
                ],

                'years' => [
                    'indicator_singular' => 'año',
                    'indicator' => 'años',
                    'option' => 'años',
                ],

                'days_ago' => [
                    'indicator_singular' => 'día atrás',
                    'indicator' => 'días atrás',
                    'option' => 'días atrás',
                ],

                'days_from_now' => [
                    'indicator_singular' => 'día después de hoy',
                    'indicator' => 'días después de hoy',
                    'option' => 'días después de hoy',
                ],

                'weeks_ago' => [
                    'indicator_singular' => 'semana atrás',
                    'indicator' => 'semanas atrás',
                    'option' => 'semanas atrás',
                ],

                'weeks_from_now' => [
                    'indicator_singular' => 'semana después de hoy',
                    'indicator' => 'semanas después de hoy',
                    'option' => 'semanas después de hoy',
                ],

                'months_ago' => [
                    'indicator_singular' => 'mes atrás',
                    'indicator' => 'meses atrás',
                    'option' => 'meses atrás',
                ],

                'months_from_now' => [
                    'indicator_singular' => 'mes después de hoy',
                    'indicator' => 'meses después de hoy',
                    'option' => 'meses después de hoy',
                ],

                'quarters_ago' => [
                    'indicator_singular' => 'cuarto atrás',
                    'indicator' => 'cuartos atrás',
                    'option' => 'cuartos atrás',
                ],

                'quarters_from_now' => [
                    'indicator_singular' => 'cuarto después de hoy',
                    'indicator' => 'cuartos después de hoy',
                    'option' => 'cuartos después de hoy',
                ],

                'years_ago' => [
                    'indicator_singular' => 'año atrás',
                    'indicator' => 'años atrás',
                    'option' => 'años atrás',
                ],

                'years_from_now' => [
                    'indicator_singular' => 'año después de hoy',
                    'indicator' => 'años después de hoy',
                    'option' => 'años después de hoy',
                ],

            ],

        ],

    ],

    'parameterized' => [

        'indicator_name' => 'Grupo :number - :label',

        'operators' => [

            'and' => 'y',
            'or' => 'o',

        ],

        'numeric' => [

            'equal_to' => ':column es igual a :value',
            'not_equal_to' => ':column no es igual a :value',
            'greater_than' => ':column es mayor que :value',
            'greater_than_or_equal_to' => ':column es mayor o igual que :value',
            'less_than' => ':column es menor que :value',
            'less_than_or_equal_to' => ':column es menor o igual que :value',
            'between' => ':column está entre :value y :end',
            'not_between' => ':column no está entre :value y :end',
            'positive' => ':column es positivo',
            'negative' => ':column es negativo',

        ],

        'text' => [

            'is' => ':column es :value',
            'is_not' => ':column no es :value',
            'starts_with' => ':column empieza con :value',
            'does_not_start_with' => ':column no empieza con :value',
            'ends_with' => ':column termina con :value',
            'does_not_end_with' => ':column no termina con :value',
            'contains' => ':column contiene :value',
            'does_not_contain' => ':column no contiene :value',
            'is_empty' => ':column está vacío',
            'is_not_empty' => ':column no está vacío',

        ],

        'date' => [

            'yesterday' => ':column es ayer',
            'today' => ':column es hoy',
            'tomorrow' => ':column es mañana',
            'in_this' => ':column es este :unit',
            'is_next' => ':column es el próximo :unit',
            'is_last' => ':column es el pasado :unit',
            'in_the_next' => ':column es dentro de :value :unit',
            'in_the_last' => ':column hace menos de :value :unit',
            'exactly' => ':column hace exactamente :value :unit',
            'before' => ':column es antes de hace :value :unit',
            'after' => ':column es después de hace :value :unit',
            'between' => ':column está entre :start y :end :unit',
            'is_date' => ':column es :start',
            'before_date' => ':column es antes de :start',
            'after_date' => ':column es después de :start',
            'between_dates' => ':column está entre :start y :end',
            'is_empty' => ':column está vacío',
            'is_not_empty' => ':column no está vacío',

            'unit' => [

                'week' => ['indicator_singular' => 'semana', 'indicator' => 'semana'],
                'month' => ['indicator_singular' => 'mes', 'indicator' => 'mes'],
                'quarter' => ['indicator_singular' => 'trimestre', 'indicator' => 'trimestre'],
                'year' => ['indicator_singular' => 'año', 'indicator' => 'año'],
                'days' => ['indicator_singular' => 'día', 'indicator' => 'días'],
                'weeks' => ['indicator_singular' => 'semana', 'indicator' => 'semanas'],
                'months' => ['indicator_singular' => 'mes', 'indicator' => 'meses'],
                'quarters' => ['indicator_singular' => 'trimestre', 'indicator' => 'trimestres'],
                'years' => ['indicator_singular' => 'año', 'indicator' => 'años'],
                'days_ago' => ['indicator_singular' => 'día', 'indicator' => 'días'],
                'days_from_now' => ['indicator_singular' => 'día', 'indicator' => 'días'],
                'weeks_ago' => ['indicator_singular' => 'semana', 'indicator' => 'semanas'],
                'weeks_from_now' => ['indicator_singular' => 'semana', 'indicator' => 'semanas'],
                'months_ago' => ['indicator_singular' => 'mes', 'indicator' => 'meses'],
                'months_from_now' => ['indicator_singular' => 'mes', 'indicator' => 'meses'],
                'quarters_ago' => ['indicator_singular' => 'trimestre', 'indicator' => 'trimestres'],
                'quarters_from_now' => ['indicator_singular' => 'trimestre', 'indicator' => 'trimestres'],
                'years_ago' => ['indicator_singular' => 'año', 'indicator' => 'años'],
                'years_from_now' => ['indicator_singular' => 'año', 'indicator' => 'años'],

            ],

        ],

    ],

];
