<?php

namespace App\Services\Extraction;

/**
 * 材料の1行（"・鶏むね肉 1枚" / "しょうが(薄切り) 4枚" / "豚ロース肉（薄切り）　…　200ｇ" 等）から
 * タグ向けの食材名を取り出す共通ロジック。
 */
class IngredientName
{
    public static function parse(string $line): string
    {
        $name = trim($line);

        // 先頭の記号（・･* ★☆ ハイフン）を除去。
        $name = preg_replace('/^[\s\x{3000}・･\*\x{2605}\x{2606}\-]+/u', '', $name) ?? '';

        // 全角／半角の括弧グループ（補足説明や分量の注記）を除去。
        $name = preg_replace('/[（(][^）)]*[）)]/u', '', $name) ?? '';

        // 最初の空白（半角・全角）より前を名前とみなす（空白以降は分量や"…"区切り）。
        $parts = preg_split('/[\s\x{3000}]+/u', trim($name)) ?: [];

        return trim($parts[0] ?? '');
    }
}
