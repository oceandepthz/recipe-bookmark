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
        $steps = $this->howtoBlocks($xpath, $url);

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
     * 作り方ブロックを、文章と手順写真を文書順に並べて取り出す。
     *
     * @return list<array{heading: ?string, items: list<array{type: string, value: string, alt: string}>}>
     */
    private function howtoBlocks(DOMXPath $xpath, string $url): array
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

            // 段落（p）と手順写真（img）を文書順に収集する。
            $items = [];
            foreach ($xpath->query('.//p | .//img', $block) as $node) {
                if ($node->nodeName === 'img' && $node instanceof DOMElement) {
                    $src = $this->resolveUrl($url, $node->getAttribute('src'));
                    if ($src !== null) {
                        $items[] = ['type' => 'image', 'value' => $src, 'alt' => $this->normalize($node->getAttribute('alt'))];
                    }

                    continue;
                }

                $text = $this->normalize($node->textContent);
                if ($text !== '') {
                    $items[] = ['type' => 'text', 'value' => $text, 'alt' => ''];
                }
            }

            if ($heading !== null || $items !== []) {
                $result[] = ['heading' => $heading, 'items' => $items];
            }
        }

        return $result;
    }

    /**
     * 相対URL（例 /_files/...）をページのスキーム・ホストで絶対URLへ補正する。
     */
    private function resolveUrl(string $base, string $src): ?string
    {
        $src = trim($src);
        if ($src === '') {
            return null;
        }

        if (str_starts_with($src, 'http://') || str_starts_with($src, 'https://')) {
            return $src;
        }

        $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';
        $host = parse_url($base, PHP_URL_HOST);
        if ($host === null) {
            return null;
        }

        if (str_starts_with($src, '//')) {
            return $scheme.':'.$src;
        }

        return $scheme.'://'.$host.'/'.ltrim($src, '/');
    }

    /**
     * @param  list<string>  $materialLines
     * @param  list<array{heading: ?string, items: list<array{type: string, value: string, alt: string}>}>  $steps
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
                foreach ($block['items'] as $item) {
                    $parts[] = $item['type'] === 'image'
                        ? '<p><img src="'.e($item['value']).'" alt="'.e($item['alt']).'"></p>'
                        : '<p>'.e($item['value']).'</p>';
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
