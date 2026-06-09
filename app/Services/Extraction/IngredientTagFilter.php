<?php

namespace App\Services\Extraction;

/**
 * 食材名のリストを「タグにすべき利用食材」へ絞り込む。
 * config('recipe.ingredient_tag_denylist') の語に対し、完全一致または後方一致したものを除外する
 * （例: 除外語 "油" → "サラダ油"/"ごま油" を除外、"こしょう" → "黒こしょう" を除外。
 *  "塩昆布" は "昆布" 扱いで残る）。
 */
class IngredientTagFilter
{
    /**
     * @param  list<string>  $names
     * @return list<string>
     */
    public function tags(array $names): array
    {
        /** @var list<string> $deny */
        $deny = config('recipe.ingredient_tag_denylist', []);

        $tags = [];

        foreach ($names as $name) {
            $name = trim($name);

            if ($name === '' || $this->isExcluded($name, $deny)) {
                continue;
            }

            $tags[$name] = true; // 重複排除
        }

        return array_keys($tags);
    }

    /**
     * @param  list<string>  $deny
     */
    private function isExcluded(string $name, array $deny): bool
    {
        foreach ($deny as $term) {
            $term = trim($term);

            if ($term !== '' && ($name === $term || str_ends_with($name, $term))) {
                return true;
            }
        }

        return false;
    }
}
