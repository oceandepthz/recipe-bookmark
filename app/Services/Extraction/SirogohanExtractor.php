<?php

namespace App\Services\Extraction;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * 白ごはん.com（sirogohan.com）専用エクストラクタ。
 * このサイトは JSON-LD を持たないため、独自の HTML 構造から抽出する。
 *   - 材料: section.material 内の li（"名前　…　分量" 形式）
 *   - 作り方: section.howto 内の .howto-block（h3 小見出し ＋ p 段落）
 */
class SirogohanExtractor implements RecipeExtractor
{
    public function extract(string $url, string $html): ?ExtractedRecipe
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($host !== 'sirogohan.com' && ! str_ends_with($host, '.sirogohan.com')) {
            return null;
        }

        if (trim($html) === '') {
            return null;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $materialLis = $this->materialLines($xpath);
        $steps = $this->howtoBlocks($xpath);

        if ($materialLis === [] && $steps === []) {
            return null; // 想定構造が無ければ次（汎用）へ委譲
        }

        return new ExtractedRecipe(
            title: $this->text($xpath, '//h1'),
            siteName: '白ごはん.com',
            imageUrl: null, // og:image は RecipeFetcher 側で補完
            excerpt: null,  // meta description も RecipeFetcher 側で補完
            contentHtml: $this->buildContentHtml($materialLis, $steps),
            ingredients: array_values(array_filter(array_map(
                fn (string $line): string => IngredientName::parse($line),
                $materialLis
            ))),
        );
    }

    /**
     * @return list<string> 材料 li の全文（"名前　…　分量"）
     */
    private function materialLines(DOMXPath $xpath): array
    {
        $nodes = $xpath->query(
            "//section[contains(concat(' ', normalize-space(@class), ' '), ' material ')]//li"
        );

        $lines = [];
        foreach ($nodes as $li) {
            $text = $this->normalize($li->textContent);
            if ($text !== '') {
                $lines[] = $text;
            }
        }

        return $lines;
    }

    /**
     * @return list<array{heading: ?string, paragraphs: list<string>}>
     */
    private function howtoBlocks(DOMXPath $xpath): array
    {
        $blocks = $xpath->query(
            "//section[contains(concat(' ', normalize-space(@class), ' '), ' howto ')]"
            ."//div[contains(concat(' ', normalize-space(@class), ' '), ' howto-block ')]"
        );

        $result = [];
        foreach ($blocks as $block) {
            if (! $block instanceof DOMElement) {
                continue;
            }

            $heading = null;
            $headNode = $xpath->query('.//h3 | .//h4', $block)->item(0);
            if ($headNode !== null) {
                $heading = $this->normalize($headNode->textContent) ?: null;
            }

            $paragraphs = [];
            foreach ($xpath->query('.//p', $block) as $p) {
                $text = $this->normalize($p->textContent);
                if ($text !== '') {
                    $paragraphs[] = $text;
                }
            }

            if ($heading !== null || $paragraphs !== []) {
                $result[] = ['heading' => $heading, 'paragraphs' => $paragraphs];
            }
        }

        return $result;
    }

    /**
     * @param  list<string>  $materialLines
     * @param  list<array{heading: ?string, paragraphs: list<string>}>  $steps
     */
    private function buildContentHtml(array $materialLines, array $steps): ?string
    {
        $parts = [];

        if ($materialLines !== []) {
            $items = implode('', array_map(fn (string $l): string => '<li>'.e($l).'</li>', $materialLines));
            $parts[] = '<h2>材料</h2><ul>'.$items.'</ul>';
        }

        if ($steps !== []) {
            $parts[] = '<h2>作り方</h2>';
            foreach ($steps as $block) {
                if ($block['heading'] !== null) {
                    $parts[] = '<h3>'.e($block['heading']).'</h3>';
                }
                foreach ($block['paragraphs'] as $p) {
                    $parts[] = '<p>'.e($p).'</p>';
                }
            }
        }

        return $parts === [] ? null : implode("\n", $parts);
    }

    private function text(DOMXPath $xpath, string $query): ?string
    {
        $node = $xpath->query($query)->item(0);
        $value = $node !== null ? $this->normalize($node->textContent) : '';

        return $value !== '' ? $value : null;
    }

    /**
     * ASCII 空白の連続を1個へ畳んでトリム（全角スペース U+3000 は保持）。
     */
    private function normalize(string $text): string
    {
        return trim(preg_replace('/[ \t\r\n]+/', ' ', $text) ?? '');
    }
}
