<?php

namespace App\Http\Controllers\Info;

use App\Models\Site\Page;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Settings;
class PageController extends Controller{

    protected $pages;

    protected $data;

    /**
     * Создание нового экземпляра контроллера.
     *
     * @param  Page $pages
     * @return void
     */
    public function __construct(Page $pages){

        $settings = Settings::getInstance();

        $this->data = $settings->getParameters();

        $this->pages = $pages;

        $this->data['template'] = [
            'component' => 'info',
            'resource'  => 'page'
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(){

        $this->data['data']['pages']  = $this->pages->getAllPages();

        return view('components.info.page.index', $this->data);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id){

        $this->data['template']['view'] = 'show';
        $this->data['data']['page']  = $this->pages->getPageIfActive($id);

        return view( 'templates.default', $this->data);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id){

        $this->data['data']['page']  = $this->pages->getPage($id);

        return view($this->data['template_name'] . 'components.info.page.edit', $this->data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id){

        $this->pages->updatePage($id, $request->all());

        return redirect()->route('pages.index');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
