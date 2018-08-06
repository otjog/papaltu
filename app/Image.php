<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Image extends Model{

    public function getAllImages(){
        return self::select(
            'images.id',
            'images.name',
            'images.src'
        )
            ->get();
    }

}
