<?php

namespace App\Services\Extraction;

/**
 * 1ページから抽出したレシピ情報。
 */
final class ExtractedRecipe
{
    /**
     * @param  list<string>  $ingredients  食材名のリスト（分量を除いた名前。タグ化はこの後で除外処理する）
     */
    public function __construct(
        public ?string $title = null,
        public ?string $siteName = null,
        public ?string $imageUrl = null,
        public ?string $excerpt = null,
        public ?string $contentHtml = null,
        public array $ingredients = [],
    ) {}
}
