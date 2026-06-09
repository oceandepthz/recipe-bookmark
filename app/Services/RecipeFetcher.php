<?php

namespace App\Services;

use App\Services\Extraction\ExtractedRecipe;
use App\Services\Extraction\RecipeExtractor;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecipeFetcher
{
    /**
     * URL からページを取得し、登録済みのエクストラクタで本文・食材を抽出する。
     * 取得・抽出に失敗してもできる範囲の情報を返す（フォールバック）。
     */
    public function fetch(string $url): ExtractedRecipe
    {
        $html = $this->download($url);

        if ($html === null) {
            return new ExtractedRecipe();
        }

        // OGP/title を base メタとして先に取得し、抽出結果の欠損を後で補完する。
        $meta = $this->extractMeta($html);

        foreach ($this->extractors() as $extractor) {
            $extracted = $extractor->extract($url, $html);

            if ($extracted !== null) {
                return $this->mergeMeta($extracted, $meta);
            }
        }

        // 通常は終端の GenericExtractor が必ず結果を返すためここには来ない。
        return $this->mergeMeta(new ExtractedRecipe(), $meta);
    }

    private function download(string $url): ?string
    {
        try {
            $response = Http::timeout((int) config('recipe.fetch_timeout', 10))
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; RecipeBookmark/1.0; +https://localhost)',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->get($url);
        } catch (\Throwable $e) {
            Log::warning('RecipeFetcher: ページ取得に失敗しました', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }

        return $response->successful() ? $response->body() : null;
    }

    /**
     * @return iterable<RecipeExtractor>
     */
    private function extractors(): iterable
    {
        /** @var list<class-string<RecipeExtractor>> $classes */
        $classes = config('recipe.extractors', []);

        foreach ($classes as $class) {
            yield app($class);
        }
    }

    /**
     * 抽出結果の null フィールドを base メタ（OGP/title）で補完する。
     *
     * @param  array{title: ?string, site_name: ?string, image: ?string, description: ?string}  $meta
     */
    private function mergeMeta(ExtractedRecipe $extracted, array $meta): ExtractedRecipe
    {
        return new ExtractedRecipe(
            title: $extracted->title ?: $meta['title'],
            siteName: $extracted->siteName ?: $meta['site_name'],
            imageUrl: $extracted->imageUrl ?: $meta['image'],
            excerpt: $extracted->excerpt ?: $meta['description'],
            contentHtml: $extracted->contentHtml,
            ingredients: $extracted->ingredients,
        );
    }

    /**
     * OGP / 標準メタタグからタイトル・サイト名・画像・説明を抽出する。
     *
     * @return array{title: ?string, site_name: ?string, image: ?string, description: ?string}
     */
    private function extractMeta(string $html): array
    {
        $meta = ['title' => null, 'site_name' => null, 'image' => null, 'description' => null];

        if (trim($html) === '') {
            return $meta;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $ogContent = static function (string $property) use ($xpath): ?string {
            $node = $xpath->query("//meta[@property='og:{$property}']/@content")->item(0)
                ?? $xpath->query("//meta[@name='og:{$property}']/@content")->item(0);

            $value = $node?->nodeValue;

            return ($value !== null && trim($value) !== '') ? trim($value) : null;
        };

        $meta['title'] = $ogContent('title');
        $meta['site_name'] = $ogContent('site_name');
        $meta['image'] = $ogContent('image');
        $meta['description'] = $ogContent('description');

        if ($meta['description'] === null) {
            $node = $xpath->query("//meta[@name='description']/@content")->item(0);
            $value = $node?->nodeValue;
            $meta['description'] = ($value !== null && trim($value) !== '') ? trim($value) : null;
        }

        if ($meta['title'] === null) {
            $node = $xpath->query('//title')->item(0);
            $value = $node?->textContent;
            $meta['title'] = ($value !== null && trim($value) !== '') ? trim($value) : null;
        }

        return $meta;
    }
}
