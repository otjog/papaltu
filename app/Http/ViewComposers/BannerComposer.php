<?php
/**
 * Created by PhpStorm.
 * User: otjog
 * Date: 17.09.18
 * Time: 17:45
 */

namespace App\Http\ViewComposers;

use App\Models\Site\Banner;
use Illuminate\View\View;

class BannerComposer{

    protected $banners;

    public function __construct(Banner $banners){
        $this->banners = $banners;
    }

    public function compose(View $view){
        $view->with('banners', $this->banners->getActiveBanners());
    }

}