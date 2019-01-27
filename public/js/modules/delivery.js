function Delivery(){

    this.calculate = function () {

        let listOfferWrap = document.getElementById('delivery-offers');

        if(listOfferWrap !== null && listOfferWrap !== undefined){

            let headers = {
                'X-Module'      : 'delivery|offers',
            };

            let queryString = getQueryStringWithParcelData(listOfferWrap);

            let component = listOfferWrap.dataset.component;

            if(component !== null && component !== undefined){
                headers['X-Component'] = component;
            }

            for(let i=0; i < listOfferWrap.children.length; i++){

                if(listOfferWrap.children[i].hasAttribute('data-delivery-service-alias')){

                    let deliveryServiceAlias = listOfferWrap.children[i].getAttribute('data-delivery-service-alias');

                    queryString += '&dsalias=' + deliveryServiceAlias;

                    /**
                     * Задаем уникальное имя для нашего ajax-запроса.
                     *
                     * Это имя мы сохраняем в глобальный объект со всеми запросами,
                     * чтобы в дальнейщем мы могли управлять ими (abort и тп.)
                     *
                     * @type {string}
                     */
                    let requestName = 'delivery_' + deliveryServiceAlias;

                    sendRequestReturnView(listOfferWrap.children[i], queryString, headers, requestName);

                }

            }

        }


        let listOfferAndPointsWrap = document.getElementById( 'delivery-offers-points' );

        if (listOfferAndPointsWrap !== null && listOfferAndPointsWrap !== undefined){

            let headers = {
                'X-Module'      : 'delivery|offers-points'
            };

            /**
             * Задаем уникальное имя для нашего ajax-запроса.
             *
             * Это имя мы сохраняем в глобальный объект со всеми запросами,
             * чтобы в дальнейшем мы могли управлять ими (abort и тп.)
             *
             * @type {string}
             */
            let requestName = 'delivery_points';

            let component = listOfferAndPointsWrap.dataset.component;

            if(component !== null && component !== undefined){
                headers['X-Component'] = component;
            }

            sendRequestReturnView(listOfferAndPointsWrap, queryString, headers, requestName);

        }
    };

    this.points = function() {

        //MAP
        if(map !== null && map !== undefined){

            let headers = {
                'X-Module'      : 'delivery|map'
            };

            let ajaxReq = new Ajax("GET", '', headers);

            ajaxReq.req.onloadstart = function(){
                //deliveryProgressBar[0].style.display  = 'block';
                //deliveryErrorBlock[0].style.display = 'none';
                console.log('start');

            };

            ajaxReq.req.ontimeout = function() {
                //deliveryBestOfferWrap.innerHTML = 'Извините, слишком долгое ожидание ответа';
                console.log('timeout');
            };

            ajaxReq.req.onreadystatechange = function() {

                if (ajaxReq.req.readyState !== 4) return;

                let json  = JSON.parse(ajaxReq.req.responseText);

                initMap(json);
            };

            ajaxReq.sendRequest();

        }

    };

    //FUNCTIONS
    function sendRequestReturnView(wrapBlock, queryString,  headers, requestName){

        let reloadBlock = wrapBlock.getElementsByClassName('reload');

        let listOfferProgressBar    = reloadBlock[0].getElementsByClassName('progress');

        let listOfferErrorBlock     = reloadBlock[0].getElementsByClassName('error');

        let blurBlock               = reloadBlock[0].getElementsByClassName('blur');

        let ajaxReq = new Ajax("GET", queryString, headers, requestName);

        ajaxReq.req.onloadstart = function() {

            listOfferProgressBar[0].style.display   = 'block';

            blurBlock[0].style.opacity = 0.25;

        };

        ajaxReq.req.ontimeout = function() {

            listOfferProgressBar[0].style.display   = 'none';

        };

        ajaxReq.req.onreadystatechange = function() {

            if (ajaxReq.req.readyState !== 4) return;

            reloadBlock[0].innerHTML = String(ajaxReq.req.responseText);

            listOfferProgressBar[0].style.display   = 'none';

            blurBlock[0].style.opacity = 1;

        };

        ajaxReq.sendRequest();
    }

    function getQueryStringWithParcelData(listOfferWrap){

        let qs = '';
        let glue = '&';

        qs +=   'weight='     + listOfferWrap.getAttribute('data-product-weight');
        qs += glue;
        qs +=   'height='     + listOfferWrap.getAttribute('data-product-height');
        qs += glue;
        qs +=   'length='     + listOfferWrap.getAttribute('data-product-length');
        qs += glue;
        qs +=   'width='      + listOfferWrap.getAttribute('data-product-width');
        qs += glue;
        qs +=   'quantity='   + listOfferWrap.getAttribute('data-product-quantity');

        return qs;


    }

}