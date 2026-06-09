<?php

namespace App\Services\Extraction;

interface RecipeExtractor
{
    /**
     * URL とページHTMLからレシピ情報を抽出する。
     * このエクストラクタで扱えない場合は null を返し、次のエクストラクタへ委譲する。
     * （サイト固有の定義を追加する場合は本インターフェースを実装し、extract() 内で
     *  対象ホストでなければ null を返すように実装して config('recipe.extractors') の先頭側へ登録する。）
     */
    public function extract(string $url, string $html): ?ExtractedRecipe;
}
