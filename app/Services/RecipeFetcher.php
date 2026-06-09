<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use fivefilters\Readability\Configuration;
use fivefilters\Readability\ParseException;
use fivefilters\Readability\Readability;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecipeFetcher
{
    /**
     * URL からページを取得し、メタ情報と本文を抽出して返す。
     * 取得・抽出に失敗してもできる範囲の情報を返す（フォールバック）。
     *
     * @return array{title: ?string, site_name: ?string, image_url: ?string, excerpt: ?string, content_html: ?string}
     */
    public function fetch(string $url): array
    {
        $data = [
            'title' => null,
            'site_name' => null,
            'image_url' => null,
            'excerpt' => null,
            'content_html' => null,
        ];

        try {
            $response = Http::timeout((int) config('recipe.fetch_timeout', 10))
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; RecipeBookmark/1.0; +https://localhost)',
                    'Accept' => 'text/html,application/xhtml+xml',
                ])
                ->get($url);
        } catch (\Throwable $e) {
            Log::warning('RecipeFetcher: ページ取得に失敗しました', ['url' => $url, 'error' => $e->getMessage()]);

            return $data;
        }

        if (! $response->successful()) {
            return $data;
        }

        $html = $response->body();

        $meta = $this->extractMeta($html);
        $data['title'] = $meta['title'];
        $data['site_name'] = $meta['site_name'];
        $data['image_url'] = $meta['image'];
        $data['excerpt'] = $meta['description'];

        try {
            $readability = new Readability(new Configuration([
                'originalURL' => $url,
                'fixRelativeURLs' => true,
            ]));
            $readability->parse($html);

            $data['content_html'] = $readability->getContent();
            $data['title'] = $data['title'] ?: $readability->getTitle();
            $data['excerpt'] = $data['excerpt'] ?: $readability->getExcerpt();
            $data['image_url'] = $data['image_url'] ?: $readability->getImage();
            $data['site_name'] = $data['site_name'] ?: $readability->getSiteName();
        } catch (ParseException $e) {
            // 本文抽出に失敗しても、メタ情報だけで登録できるようにする。
            Log::info('RecipeFetcher: 本文抽出に失敗しました', ['url' => $url, 'error' => $e->getMessage()]);
        }

        return $data;
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
        // 文字コードを UTF-8 として解釈させる。
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
