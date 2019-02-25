function Delivery(){

    this.getOffers = function () {

        if(offers.elements.wrapBlock !== null && offers.elements.wrapBlock !== undefined){

            /**
             * Добавляем в querystring длину, ширину, высоту посылки и кол-во.
             */
            let queryString = addParcelParameters();

            let component = offers.elements.wrapBlock.dataset.component;
            if(component !== null && component !== undefined){
                this.headers['X-Component'] = component;
            }

            for(let i=0; i < offers.elements.wrapBlock.children.length; i++){

                if(offers.elements.wrapBlock.children[i].hasAttribute('data-delivery-service-alias')){

                    let requestData = Object.assign({}, offers);

                    let dsAlias = offers.elements.wrapBlock.children[i].getAttribute('data-delivery-service-alias');

                    /**
                     * Задаем уникальное имя для нашего ajax-запроса.
                     *
                     * Это имя мы сохраняем в глобальный объект со всеми запросами,
                     * чтобы в дальнейшем мы могли управлять ими (делать abort и тп.)
                     *
                     * @type {string}
                     */
                    requestData.requestName = 'offers_' + dsAlias;
                    requestData.queryString = getFullQueryString(queryString, dsAlias);
                    requestData.reloadBlock = offers.elements.wrapBlock.children[i].getElementsByClassName('reload');

                    sendRequest(requestData);

                }

            }

        }

    };

    this.getPoints = function() {

        if(maps.elements.wrapBlock !== null && maps.elements.wrapBlock !== undefined){

            sendRequest(maps);

        }

    };

    //FUNCTIONS
    function sendRequest(requestData){

        let ajaxReq = new Ajax(requestData.method, requestData.queryString, requestData.headers, requestData.requestName);

        ajaxReq.req.onloadstart = function () {
            requestData.functions.onloadstart(requestData)
        };

        ajaxReq.req.ontimeout = function () {
            requestData.functions.ontimeout(requestData)
        };

        ajaxReq.req.onreadystatechange = function () {

            if (ajaxReq.req.readyState !== 4) return;

            /*
            * TODO
            * После третьего обновления города через форму,
            * сначала в responseText приходит пустая строка,
            * а затем нормальный ответ с данными.
            * Из-за этого не работает blur и progress.
            * С чем это связано?
            * Пока вопрос решили через проверку ответа на пустую строку.
            * */
            if(String(ajaxReq.req.responseText) === '') return;
            requestData.functions.onreadystatechange(requestData, ajaxReq);

        };

        ajaxReq.sendRequest();

    }

    function getMarkerOnMap(map, json) {

        for(let company in json.points){

            if(json.points.hasOwnProperty(company)){

                for(let terminalType in json.points[company]){

                    if(json.points[company].hasOwnProperty(terminalType)){

                        for( let terminal in json.points[company][terminalType] ){

                            if(json.points[company][terminalType].hasOwnProperty(terminal)){

                                let geoShop = json.points[company][terminalType][terminal].geoCoordinates;

                                let locationShop = {lat: +geoShop.latitude, lng: +geoShop.longitude};

                                let image = 'https://myshop.loc/storage/img/elements/delivery/' + company + '/marker-' + terminalType + '.png';

                                let marker = new google.maps.Marker({position: locationShop, map: map, icon: image});

                            }

                        }


                    }

                }

            }

        }

    }

    function addParcelParameters(){
        queryString = '';
        let glue = '&';

        queryString +=   'weight='     + offers.elements.wrapBlock.getAttribute('data-product-weight');
        queryString += glue;
        queryString +=   'height='     + offers.elements.wrapBlock.getAttribute('data-product-height');
        queryString += glue;
        queryString +=   'length='     + offers.elements.wrapBlock.getAttribute('data-product-length');
        queryString += glue;
        queryString +=   'width='      + offers.elements.wrapBlock.getAttribute('data-product-width');
        queryString += glue;
        queryString +=   'quantity='   + offers.elements.wrapBlock.getAttribute('data-product-quantity');

        return queryString;

    }

    function getFullQueryString(queryString, dsAlias){
        return queryString + '&dsalias=' + dsAlias;
    }

    offers = {
        method : 'GET',
        headers : {'X-Module' : 'delivery|offers'},
        elements : {
            wrapBlock : document.getElementById('delivery-offers'),
        },
        functions : {
            onloadstart : function (self) {
                self.elements = {
                    loadingBlock    : self.reloadBlock[0].getElementsByClassName('loading'),
                    errorBlock      : self.reloadBlock[0].getElementsByClassName('error'),
                    contentBlock    : self.reloadBlock[0].getElementsByClassName('blur'),
                };
                self.elements.loadingBlock[0].style.display = 'block';
                self.elements.contentBlock[0].style.opacity = 0.25;

            },
            ontimeout : function (self) {
                self.elements.loadingBlock[0].style.display = 'none';
            },
            onreadystatechange :function(self, ajaxReq) {

                self.reloadBlock[0].innerHTML = String(ajaxReq.req.responseText);

                self.elements.loadingBlock[0].style.display   = 'none';

                self.elements.contentBlock[0].style.opacity = 1;

            },
        },

    };

    points = {
        method : 'GET',
        headers : {'X-Module' : 'delivery|points'},
        queryString : '',
        requestName : '',
        elements    : {
            wrapBlock   : document.getElementById('map').parentElement,
        },
        functions : {
            onloadstart : function (requestData) {
                requestData.elements = {
                    loadingBlock    : requestData.elements.wrapBlock.getElementsByClassName('loading'),
                    errorBlock      : requestData.elements.wrapBlock.getElementsByClassName('error'),
                    contentBlock    : requestData.elements.wrapBlock.getElementsByClassName('blur'),
                };
                requestData.elements.loadingBlock[0].style.display = 'block';
                requestData.elements.errorBlock[0].style.display = 'none';
                requestData.elements.contentBlock[0].style.opacity = 0.75;

            },
            ontimeout : function (requestData) {

                requestData.elements.loadingBlock[0].style.display = 'none';
                requestData.elements.errorBlock[0].style.display = 'block';
            },
            onreadystatechange :function(requestData, ajaxReq) {

                let json  = JSON.parse(ajaxReq.req.responseText);

                getMarkerOnMap(points.map, json);

                requestData.elements.loadingBlock[0].style.display   = 'none';
                requestData.elements.errorBlock[0].style.display = 'none';
                requestData.elements.contentBlock[0].style.opacity = 1;


            },
        },

    };

    maps = {
        method : 'GET',
        headers : {'X-Module' : 'map|'},
        queryString : '',
        requestName : 'map',
        elements    : {
            wrapBlock : document.getElementById('map'),
        },
        functions : {
            onloadstart : function (self) {},
            ontimeout : function (self) {},
            onreadystatechange :function(self, ajaxReq) {
                if (ajaxReq.req.readyState !== 4) return;

                let json = JSON.parse(ajaxReq.req.responseText);

                points.map = initMap(json, 12);

                if(maps.elements.wrapBlock.hasAttribute('data-delivery-service-alias')){

                    let aliasesString = maps.elements.wrapBlock.getAttribute('data-delivery-service-alias');

                    let aliasesArray = aliasesString.split('|');

                    for(let i=0; i < aliasesArray.length; i++) {

                        let requestData = Object.assign({}, points);

                        requestData.requestName = 'points_' + aliasesArray[i];
                        requestData.queryString = 'dsalias=' + aliasesArray[i];

                        sendRequest(requestData);

                    }

                }

            },
        },

    };
}