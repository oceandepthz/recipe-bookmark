<?php

namespace App\Services\Extraction;

use fivefilters\Readability\Configuration;
use fivefilters\Readability\ParseException;
use fivefilters\Readability\Readability;
use Illuminate\Support\Facades\Log;

/**
 * 汎用フォールバック。readability.php で本文を抽出する。
 * 構造化された食材は得られないため ingredients は空。常に結果を返す（終端エクストラクタ）。
 */
class GenericExtractor implements RecipeExtractor
{
    public function extract(string $url, string $html): ?ExtractedRecipe
    {
        $result = new ExtractedRecipe();

        try {
            $readability = new Readability(new Configuration([
                'originalURL' => $url,
                'fixRelativeURLs' => true,
            ]));
            $readability->parse($html);

            $result->title = $readability->getTitle();
            $result->excerpt = $readability->getExcerpt();
            $result->imageUrl = $readability->getImage();
            $result->siteName = $readability->getSiteName();
            $result->contentHtml = $readability->getContent();
        } catch (ParseException $e) {
            // 本文抽出に失敗しても、メタ情報だけで登録できるよう空の結果を返す。
            Log::info('GenericExtractor: 本文抽出に失敗しました', ['url' => $url, 'error' => $e->getMessage()]);
        }

        return $result;
    }
}
