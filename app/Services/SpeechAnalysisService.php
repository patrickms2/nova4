<?php

namespace App\Services;

use Illuminate\Support\Str;

class SpeechAnalysisService
{
    public function analyze(string $text): array
    {
        $normalized = $this->normalizeServiceText($text);
        $lower = Str::lower($normalized);
        $plainLower = Str::lower(Str::ascii($normalized));

        $type = 'service';
        if (Str::contains($lower, ['comer', 'restaurante', 'comida', 'plato', 'cena', 'almuerzo'])) {
            $type = 'restaurant';
        } elseif (Str::contains($lower, ['experiencia', 'tour', 'visita', 'guia', 'guía', 'excursion', 'excursión', 'excursiones', 'clase', 'clases', 'curso', 'cata'])) {
            $type = 'experience';
        } elseif (Str::contains($lower, ['taller', 'artesania', 'artesanía', 'hecho a mano', 'barro', 'tejido'])) {
            $type = 'artisan';
        } elseif (Str::contains($lower, ['producto', 'gofio', 'tomate', 'tomates', 'bolsa', 'botella', 'tarro', 'venta', 'kilo', 'unidad'])) {
            $type = 'product';
        }

        // Si el texto parece una experiencia pero contiene palabras clave de producto, reconsiderar
        if ($type === 'experience' && Str::contains($lower, ['tomate', 'tomates', 'gofio', 'bolsa', 'kilo'])) {
            $type = 'product';
        }

        $price = null;
        if (preg_match('/(\d+(?:[\.,]\d+)?)\s*(?:euros?|€)/u', $lower, $m)) {
            $price = (float) str_replace(',', '.', $m[1]);
        } elseif (preg_match('/(?:vale|cuesta|por|precio)\s*(\d+(?:[\.,]\d+)?)/u', $lower, $m)) {
            $price = (float) str_replace(',', '.', $m[1]);
        } elseif (preg_match('/(\d+(?:[\.,]\d+)?)\s*(?:por\s*persona|pax|la\s*bolsa|la\s*unidad|el\s*kilo|un\s*kilo)/u', $lower, $m)) {
            $price = (float) str_replace(',', '.', $m[1]);
        }

        $duration = null;
        if (Str::contains($lower, ['día', 'jornada', 'completo'])) {
            $duration = 480;
        } elseif (preg_match('/(\d+)\s*(min|minutos)/', $lower, $m)) {
            $duration = (int) $m[1];
        } elseif (preg_match('/(\d+)\s*(h|hr|hora|horas)/', $lower, $m)) {
            $duration = (int) $m[1] * 60;
        } elseif (Str::contains($lower, ['una hora', 'cada hora', 'y hora'])) {
            $duration = 60;
        }
        if ($duration !== null) {
            $duration = max(15, (int) (round($duration / 15) * 15));
        }

        $perPerson = Str::contains($lower, ['persona', 'personas', 'pax', 'por cabeza']);
        $priceUnit = null;
        if (Str::contains($lower, ['bolsa'])) {
            $priceUnit = 'bolsa';
        } elseif (Str::contains($lower, ['kilo', 'kg'])) {
            $priceUnit = 'kg';
        } elseif ($perPerson) {
            $priceUnit = 'pax';
        }

        // Puntos de interés (POIs) conocidos con coordenadas e imagen libre
        $pois = [
            'bodega la geria' => [
                'Bodega La Geria, LZ-30, Yaiza',
                28.9713208,
                -13.7102029,
                // Imagen representativa libre (Wikimedia Commons)
                'https://upload.wikimedia.org/wikipedia/commons/3/3c/Yaiza_-_LZ-30_-_Bodega_La_Geria_01_ies.jpg',
            ],
            'bodegas la geria' => [
                'Bodegas La Geria, LZ-30, Yaiza',
                28.9713208,
                -13.7102029,
                'https://upload.wikimedia.org/wikipedia/commons/3/3c/Yaiza_-_LZ-30_-_Bodega_La_Geria_01_ies.jpg',
            ],
            'la geria' => [
                'La Geria, Yaiza',
                28.963283,
                -13.706028,
                'https://upload.wikimedia.org/wikipedia/commons/5/52/La_Geria_-_vineyard_region_of_Lanzarote.jpg',
            ],
        ];

        $towns = [
            'arrecife' => ['Arrecife, Lanzarote', 28.963021, -13.547693],
            'puerto del carmen' => ['Puerto del Carmen, Tías', 28.924355, -13.661337],
            'playa blanca' => ['Playa Blanca, Yaiza', 28.867163, -13.828278],
            'costa teguise' => ['Costa Teguise, Teguise', 28.998567, -13.487019],
            'teguise' => ['Teguise, Lanzarote', 29.060236, -13.560047],
            'haria' => ['Haría, Lanzarote', 29.145244, -13.495236],
            'haría' => ['Haría, Lanzarote', 29.145244, -13.495236],
            'la santa' => ['La Santa, Tinajo', 29.108, -13.666, 'https://upload.wikimedia.org/wikipedia/commons/e/e0/La_Santa_-_Tinajo_-_Lanzarote.jpg'],
            'tinajo' => ['Tinajo, Lanzarote', 29.060552, -13.672301],
            'san bartolome' => ['San Bartolomé, Lanzarote', 28.995360, -13.613520],
            'san bartolomé' => ['San Bartolomé, Lanzarote', 28.995360, -13.613520],
            'yaiza' => ['Yaiza, Lanzarote', 28.956271, -13.765835],
            'orzola' => ['Órzola, Lanzarote', 29.214592, -13.454385],
            'órzola' => ['Órzola, Lanzarote', 29.214592, -13.454385],
            'famara' => ['Caleta de Famara, Lanzarote', 29.120411, -13.557946],
            'timanfaya' => ['Parque Nacional del Timanfaya, Lanzarote', 29.004456, -13.753363],
            'mirador del rio' => ['Mirador del Río, Lanzarote', 29.214479, -13.481121],
            'jameos del agua' => ['Jameos del Agua, Lanzarote', 29.157321, -13.430045],
            'cueva de los verdes' => ['Cueva de los Verdes, Lanzarote', 29.160356, -13.438512],
            'fundacion cesar manrique' => ['Fundación César Manrique, Tahíche', 29.002811, -13.547141],
            'jardin de cactus' => ['Jardín de Cactus, Guatiza', 29.052636, -13.475965],
            'monumento al campesino' => ['Monumento al Campesino, San Bartolomé', 29.005111, -13.611412],
            // Papagayo (Yaiza) y variantes
            'papagayo' => ['Playa de Papagayo, Yaiza', 28.8442, -13.7850],
            'playa de papagayo' => ['Playa de Papagayo, Yaiza', 28.8442, -13.7850],
            // Restaurantes/POIs específicos
            'cangrejo rojo' => ['Restaurante El Cangrejo Rojo, Puerto del Carmen', 28.920807, -13.668579, 'https://images.unsplash.com/photo-1552332386-f8dd00dc2f85?auto=format&fit=crop&w=800&q=80'],
            'el cangrejo rojo' => ['Restaurante El Cangrejo Rojo, Puerto del Carmen', 28.920807, -13.668579, 'https://images.unsplash.com/photo-1552332386-f8dd00dc2f85?auto=format&fit=crop&w=800&q=80'],
            'molino de gofio' => ['Molino de Gofio, San Bartolomé', 28.995360, -13.613520, 'https://upload.wikimedia.org/wikipedia/commons/e/e0/Gofio_Canario.jpg'],
            'gofio' => ['Molino de Gofio, San Bartolomé', 28.995360, -13.613520, 'https://upload.wikimedia.org/wikipedia/commons/e/e0/Gofio_Canario.jpg'],
        ];

        $poiImages = [
            'surf' => 'https://images.unsplash.com/photo-1502680390469-be75c86b636f?auto=format&fit=crop&w=800&q=80',
            'vino' => 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?auto=format&fit=crop&w=800&q=80',
            'cata' => 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?auto=format&fit=crop&w=800&q=80',
            'comida' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=800&q=80',
            'cena' => 'https://images.unsplash.com/photo-1559339352-11d035aa65de?auto=format&fit=crop&w=800&q=80',
            'restaurante' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=800&q=80',
            'pescado' => 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?auto=format&fit=crop&w=800&q=80',
            'marisco' => 'https://images.unsplash.com/photo-1533134242443-d4fd215305ad?auto=format&fit=crop&w=800&q=80',
            'artesania' => 'https://images.unsplash.com/photo-1459411552884-841db9b3cc2a?auto=format&fit=crop&w=800&q=80',
            'taller' => 'https://images.unsplash.com/photo-1459411552884-841db9b3cc2a?auto=format&fit=crop&w=800&q=80',
            'excursion' => 'https://images.unsplash.com/photo-1501555088652-021faa106b9b?auto=format&fit=crop&w=800&q=80',
            'senderismo' => 'https://images.unsplash.com/photo-1501555088652-021faa106b9b?auto=format&fit=crop&w=800&q=80',
            'playa' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
            'volcan' => 'https://images.unsplash.com/photo-1444065381814-865dc9da92c0?auto=format&fit=crop&w=800&q=80',
            'guitarra' => 'https://images.unsplash.com/photo-1510915361894-db8b60106cb1?auto=format&fit=crop&w=800&q=80',
            'musica' => 'https://images.unsplash.com/photo-1511379938547-c1f69419868d?auto=format&fit=crop&w=800&q=80',
            'gofio' => 'https://upload.wikimedia.org/wikipedia/commons/e/e0/Gofio_Canario.jpg',
            'bolsa' => 'https://upload.wikimedia.org/wikipedia/commons/e/e0/Gofio_Canario.jpg',
            'tomate' => 'https://images.unsplash.com/photo-1592841200221-a6898f307baa?auto=format&fit=crop&w=800&q=80',
            'tomates' => 'https://images.unsplash.com/photo-1592841200221-a6898f307baa?auto=format&fit=crop&w=800&q=80',
            'producto' => 'https://images.unsplash.com/photo-1558640473-27485d85efc6?auto=format&fit=crop&w=800&q=80',
            'kilo' => 'https://upload.wikimedia.org/wikipedia/commons/e/e0/Gofio_Canario.jpg',
        ];

        $detectedAddress = null;
        $detectedLat = null;
        $detectedLng = null;
        $detectedImage = null;

        // 1) Buscar un POI conocido primero
        foreach ($pois as $needle => $meta) {
            $needlePlain = Str::lower(Str::ascii($needle));
            if (Str::contains($plainLower, $needlePlain)) {
                [$detectedAddress, $detectedLat, $detectedLng, $detectedImage] = $meta;
                break;
            }
        }

        // 2) Si no hubo POI, intentar con poblaciones
        if ($detectedAddress === null) {
            foreach ($towns as $needle => $meta) {
                $needlePlain = Str::lower(Str::ascii($needle));
                if (Str::contains($plainLower, $needlePlain)) {
                    [$detectedAddress, $detectedLat, $detectedLng] = $meta;
                    break;
                }
            }
        }

        // 3) Si no hubo imagen de POI, buscar por palabras clave genéricas
        if ($detectedImage === null) {
            foreach ($poiImages as $keyword => $imageUrl) {
                if (Str::contains($lower, $keyword)) {
                    $detectedImage = $imageUrl;
                    break;
                }
            }
        }

        $suggestedTitle = null;
        if (Str::length($normalized) > 0) {
            $words = preg_split('/\s+/', trim($normalized));
            $titleWords = array_slice($words, 0, 5);
            $suggestedTitle = $this->titleCasePreservingPrepositions(implode(' ', $titleWords));
            if (Str::length($suggestedTitle) < 5 && isset($words[5])) {
                $suggestedTitle .= ' '.Str::title($words[5]);
            }
        }

        $excerpt = $normalized;
        if ($perPerson) {
            $excerpt = preg_replace('/euros?\s*(?:por\s*)?personas?/iu', 'euros por persona', $excerpt);
        }
        // Normalizar unidad "bolsa"
        $excerpt = preg_replace('/\b(la\s*)?bolsa\b/iu', 'por bolsa', $excerpt);
        $excerpt = trim(preg_replace('/\s+/', ' ', $excerpt));
        if ($duration !== null) {
            if (! preg_match('/\b(min|minutos|hora|horas|h|hr)\b/', Str::lower($excerpt))) {
                $excerpt .= '. '.$duration.' min';
            }
        }

        return [
            'suggested_type' => $type,
            'suggested_price' => $price,
            'suggested_duration' => $duration,
            'suggested_title' => $suggestedTitle,
            'is_per_person' => $perPerson,
            'price_unit' => $priceUnit,
            'is_relevant' => Str::length($normalized) > 10,
            'suggested_address' => $detectedAddress,
            'suggested_latitude' => $detectedLat,
            'suggested_longitude' => $detectedLng,
            'suggested_image_url' => $detectedImage,
            'normalized_text' => $normalized,
            'suggested_excerpt' => $excerpt,
        ];
    }

    private function normalizeServiceText(string $text): string
    {
        $t = ' '.trim($text).' ';
        $t = preg_replace('/\s+coma\s+/iu', ', ', $t);
        $t = preg_replace('/\s*([,.;:])\s*/u', '$1 ', $t);
        $t = preg_replace('/\.{2,}/', '.', $t);
        $t = preg_replace('/euros?\s*personas?/iu', 'euros por persona', $t);
        $t = preg_replace('/euros?\s*persona/iu', 'euros por persona', $t);
        $t = preg_replace('/\b(\d+)\s*h(r)?\b/iu', '$1h', $t);
        $t = preg_replace('/\s{2,}/', ' ', $t);
        $t = trim($t);
        $t = ucfirst($t);
        if (! preg_match('/[\.!?]$/', $t)) {
            $t .= '.';
        }

        return $t;
    }

    private function titleCasePreservingPrepositions(string $phrase): string
    {
        $small = ['de', 'la', 'las', 'los', 'del', 'al', 'por', 'para', 'en', 'y', 'o', 'a'];
        $words = preg_split('/\s+/', trim($phrase));
        $out = [];
        foreach ($words as $i => $w) {
            $lw = Str::lower($w);
            if ($i > 0 && in_array($lw, $small, true)) {
                $out[] = $lw;
            } else {
                $out[] = Str::title($lw);
            }
        }

        return implode(' ', $out);
    }
}
