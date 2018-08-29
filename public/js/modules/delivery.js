function Delivery(){

    this.calculate = function () {

        let form = document.getElementById('delivery-form');

        if(form !== null && form !== undefined){

            let queryString = getQueryString(form);

            let listOfferWrap = document.getElementById( 'delivery-offers' );

            if( listOfferWrap !== null && listOfferWrap !== undefined){

                let headers = {
                    'X-Module'      : 'delivery|offers',
                };

                let component = listOfferWrap.dataset.component;

                if(component !== null && component !== undefined){
                    headers['X-Component'] = component;
                }

                sendRequestReturnView(listOfferWrap, queryString, headers);

            }

            let bestOfferWrap = document.getElementById( 'delivery-best-offer' );

            if( bestOfferWrap !== null && bestOfferWrap !== undefined){

                let headers = {
                    'X-Module'      : 'delivery|best-offer'
                };

                let component = bestOfferWrap.dataset.component;

                if(component !== null && component !== undefined){
                    headers['X-Component'] = component;
                }

                sendRequestReturnView(bestOfferWrap, queryString, headers);
            }

            let listOfferAndPointsWrap = document.getElementById( 'delivery-offers-points' );

            if( listOfferAndPointsWrap !== null && listOfferAndPointsWrap !== undefined){

                let headers = {
                    'X-Module'      : 'delivery|offers-points'
                };

                let component = listOfferAndPointsWrap.dataset.component;

                if(component !== null && component !== undefined){
                    headers['X-Component'] = component;
                }

                sendRequestReturnView(listOfferAndPointsWrap, queryString, headers);

            }

        }
    };

    this.points = function(){

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
    function sendRequestReturnView(wrapBlock, queryString,  headers){

        let listOfferProgressBar    = wrapBlock.getElementsByClassName('progress');

        let listOfferErrorBlock     = wrapBlock.getElementsByClassName('error');

        let blurBlock               = wrapBlock.getElementsByClassName('blur');


        let ajaxReq = new Ajax("GET", queryString, headers);

        ajaxReq.req.onloadstart = function(){

            listOfferProgressBar[0].style.display   = 'block';

            listOfferErrorBlock[0].style.display    = 'none';

            blurBlock[0].style.opacity = 0.25;



        };

        ajaxReq.req.ontimeout = function() {

            listOfferProgressBar[0].style.display   = 'none';

            listOfferErrorBlock[0].style.display    = 'block';

            listOfferErrorBlock[0].innerHTML = 'Извините, слишком долгое ожидание ответа';

        };

        ajaxReq.req.onreadystatechange = function() {

            if (ajaxReq.req.readyState !== 4) return;

            wrapBlock.innerHTML = String(ajaxReq.req.responseText);

            listOfferProgressBar[0].style.display   = 'none';

            listOfferErrorBlock[0].style.display    = 'none';

            blurBlock[0].style.opacity = 1;



        };

        ajaxReq.sendRequest();
    }

    function getQueryString(form){

        let arrQS = [];
        let qs = '';
        let glue = '&';

        for(let i = 0; i  < form.elements.length; i++){
            if(i !== 0){
                qs += glue
            }

            qs += form.elements[i].name + '=' + form.elements[i].value;

        }

        return qs;
    }

}