<?php

namespace App\Traits;

trait DataTree
{
    public static function buildTree($data, $parent_id = 0)
    {
        $tree = [];

        foreach ($data as $item) {
            if ($item->parent_id == $parent_id) {
                // Gọi đúng tên trait bằng self::
                $children = self::buildTree($data, $item->id);
                
                if (!empty($children)) {
                    $item->childs = $children;
                }

                $tree[] = $item;
            }
        }

        return $tree;
    }
}
