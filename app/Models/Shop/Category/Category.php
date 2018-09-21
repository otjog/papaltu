<?php

namespace App\Models\Shop\Category;

use Illuminate\Database\Eloquent\Model;

class Category extends Model{

    protected $fillable = ['active', 'name'];

    public function products(){
        return $this->hasMany('App\Models\Shop\Product\Product');
    }

    public function getAllCategories(){
        return self::select(
            'id',
            'parent_id',
            'name',
            'original_name',
            'url'
            )
            ->orderBy('name')
            ->get();
    }

    public function getActiveCategories(){
        return self::select(
            'id',
            'parent_id',
            'name',
            'original_name',
            'url'
        )
            ->where('active', 1)
            ->orderBy('sort')
            ->get();
    }

    public function getChildrenCategories($parent_id){
        return self::select(
            'id',
            'parent_id',
            'name',
            'original_name',
            'url'
        )
            ->where('parent_id', $parent_id)
            ->orderBy('sort')
            ->get();
    }

    public function getActiveChildrenCategories($parent_id){
        return self::select(
            'id',
            'parent_id',
            'name',
            'original_name',
            'url'
        )
            ->where('active', 1)
            ->where('parent_id', $parent_id)
            ->orderBy('sort')
            ->get();
    }

    public function getCategory($id){
        return self::select(
            'id',
            'parent_id',
            'name',
            'original_name',
            'url'
        )
            ->where('id', $id)
            ->orderBy('name')
            ->get();
    }

    public function getCategoryIfActive($id){
        return self::select(
            'id',
            'parent_id',
            'name',
            'original_name',
            'url'
        )
            ->where('id', $id)
            ->where('active', 1)
            ->orderBy('name')
            ->get();
    }

    public function getCategoriesTree($parent_id = 0){
        /**
         * http://forum.php.su/topic.php?forum=71&topic=4385
         */
        $allCat = [];
        $tree   = [];
        $categories = $this->getActiveCategories();

        foreach($categories as $category){
            $cur =& $allCat[$category['id']];
            $cur['id'] = $category['id'];
            $cur['parent_id'] = $category['parent_id'];
            $cur['name'] = $category['name'];

            if($category['parent_id'] == $parent_id){ /* id категории, с которой начинается дерево */
                $tree[$category['id']] =& $cur;
            }
            else{
                $allCat[$category['parent_id']]['children'][$category['id']] =& $cur;
            }
        }
        return collect($tree);
    }

}
