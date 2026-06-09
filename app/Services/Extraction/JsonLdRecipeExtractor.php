<?php

namespace App\Services\Extraction;

use DOMDocument;
use DOMXPath;

/**
 * schema.org/Recipe の JSON-LD を持つページから構造化データを抽出する汎用エクストラクタ。
 * 多くのレシピサイト（みんなのきょうの料理・楽天レシピ等）が対象。
 */
class JsonLdRecipeExtractor implements RecipeExtractor
{
    public function extract(string $url, string $html): ?ExtractedRecipe
    {
        $recipe = $this->findRecipeNode($html);

        if ($recipe === null) {
            return null;
        }

        $ingredientsRaw = $this->stringList($recipe['recipeIngredient'] ?? []);
        $steps = $this->normalizeInstructions($recipe['recipeInstructions'] ?? []);

        return new ExtractedRecipe(
            title: $this->cleanText($recipe['name'] ?? null),
            siteName: null, // OGP 等から RecipeFetcher 側で補完する
            imageUrl: $this->firstImageUrl($recipe['image'] ?? null),
            excerpt: $this->cleanText($recipe['description'] ?? null),
            contentHtml: $this->buildContentHtml($recipe['description'] ?? null, $ingredientsRaw, $steps),
            ingredients: array_values(array_filter(array_map(
                fn (string $line): string => IngredientName::parse($line),
                $ingredientsRaw
            ))),
        );
    }

    /**
     * HTML 内のすべての ld+json から @type=Recipe のオブジェクトを探す。
     *
     * @return array<string, mixed>|null
     */
    private function findRecipeNode(string $html): ?array
    {
        if (trim($html) === '') {
            return null;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $scripts = $xpath->query("//script[@type='application/ld+json']");

        foreach ($scripts as $script) {
            $decoded = json_decode((string) $script->textContent, true);

            if (! is_array($decoded)) {
                continue;
            }

            // トップが配列／@graph／単一オブジェクトのいずれでも走査できるよう平坦化する。
            $candidates = $this->flattenCandidates($decoded);

            foreach ($candidates as $node) {
                if (is_array($node) && $this->isRecipe($node)) {
                    return $node;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<mixed>  $decoded
     * @return list<mixed>
     */
    private function flattenCandidates(array $decoded): array
    {
        if (isset($decoded['@graph']) && is_array($decoded['@graph'])) {
            return $decoded['@graph'];
        }

        // 連想配列（単一オブジェクト）か、オブジェクトの配列かを判定。
        return array_is_list($decoded) ? $decoded : [$decoded];
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function isRecipe(array $node): bool
    {
        $type = $node['@type'] ?? null;

        return $type === 'Recipe' || (is_array($type) && in_array('Recipe', $type, true));
    }

    private function firstImageUrl(mixed $image): ?string
    {
        if (is_string($image)) {
            return $image !== '' ? $image : null;
        }

        if (is_array($image)) {
            // ["url", ...] / [{"url": ...}] / {"url": ...}
            if (isset($image['url']) && is_string($image['url'])) {
                return $image['url'];
            }

            foreach ($image as $item) {
                if (is_string($item) && $item !== '') {
                    return $item;
                }
                if (is_array($item) && isset($item['url']) && is_string($item['url'])) {
                    return $item['url'];
                }
            }
        }

        return null;
    }

    /**
     * recipeInstructions の各種形（文字列 / HowToStep / HowToSection）を
     * 手順（テキスト＋任意の手順写真URL）の配列へ正規化する。
     *
     * @return list<array{text: string, image: ?string}>
     */
    private function normalizeInstructions(mixed $instructions): array
    {
        if (is_string($instructions)) {
            return array_values(array_map(
                fn (string $s): array => ['text' => trim($s), 'image' => null],
                array_filter(preg_split('/\r\n|\r|\n/', $instructions) ?: [], fn (string $s): bool => trim($s) !== '')
            ));
        }

        if (! is_array($instructions)) {
            return [];
        }

        $steps = [];

        foreach ($instructions as $item) {
            if (is_string($item)) {
                $text = $this->cleanText($item);
                if ($text !== null) {
                    $steps[] = ['text' => $text, 'image' => null];
                }

                continue;
            }

            if (! is_array($item)) {
                continue;
            }

            // HowToSection は itemListElement を展開。
            if (($item['@type'] ?? null) === 'HowToSection' && isset($item['itemListElement'])) {
                foreach ($this->normalizeInstructions($item['itemListElement']) as $sub) {
                    $steps[] = $sub;
                }

                continue;
            }

            $text = $this->cleanText($item['text'] ?? ($item['name'] ?? null));
            if ($text !== null) {
                $steps[] = ['text' => $text, 'image' => $this->firstImageUrl($item['image'] ?? null)];
            }
        }

        return $steps;
    }

    /**
     * @param  list<string>  $ingredients
     * @param  list<array{text: string, image: ?string}>  $steps
     */
    private function buildContentHtml(?string $description, array $ingredients, array $steps): ?string
    {
        $parts = [];

        $desc = $this->cleanText($description);
        if ($desc !== null) {
            $parts[] = '<p>'.e($desc).'</p>';
        }

        if ($ingredients !== []) {
            $items = implode('', array_map(fn (string $i): string => '<li>'.e($i).'</li>', $ingredients));
            $parts[] = '<h2>材料</h2><ul>'.$items.'</ul>';
        }

        if ($steps !== []) {
            $items = implode('', array_map(function (array $step): string {
                $li = e($step['text']);
                if ($step['image'] !== null) {
                    $li .= '<br><img src="'.e($step['image']).'" alt="">';
                }

                return '<li>'.$li.'</li>';
            }, $steps));
            $parts[] = '<h2>作り方</h2><ol>'.$items.'</ol>';
        }

        return $parts === [] ? null : implode("\n", $parts);
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (is_string($value)) {
            return [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($v): ?string => is_string($v) ? trim($v) : null,
            $value
        ), fn (?string $v): bool => $v !== null && $v !== ''));
    }

    private function cleanText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        // 一部サイトの手順に混ざる "**N**" 風マーカーを除去。
        $text = trim(str_replace('**', '', $value));

        return $text !== '' ? $text : null;
    }
}
