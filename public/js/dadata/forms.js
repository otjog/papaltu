function DaData(id, type){
    let token = "23933ff36c0c7e63248d1782df14d07badb394a0";
    let button = document.getElementsByClassName('update-geo');
    let geoData = {};
    this.suggestions = function(){
        $("#"+ id).suggestions({
            token: token,
            type: type,
            count: 5,
            onSelect: function(suggestion) {
                $("#" + id + "_json").val(JSON.stringify(suggestion.data));

                if(type === 'ADDRESS'){
                    geoData = suggestion.data;
                    button[0].addEventListener('click', sendRequest, false);

                }

            }

        });
    };

    function sendRequest () {

        let queryString = 'address_json=' + JSON.stringify(geoData);

        let headers = {
            'X-Module'      : 'geo|location '
        };

        let requestName = 'geo';

        let ajaxReq = new Ajax("POST", queryString, headers, requestName);
        //todo проверить, если объекта нет, делать submit формы
        ajaxReq.req.onreadystatechange = function() {

            if (ajaxReq.req.readyState !== 4) return;

            let json = JSON.parse(ajaxReq.req.responseText);

            changeHtml(json);

            updateDeliveryInfo();

        };

        ajaxReq.sendRequest();
    }

    function changeHtml(json){
        let geoLocationLinks = document.getElementsByClassName('geo-location-link');

        for(let i = 0; i < geoLocationLinks.length; i++){

            let linkElements = geoLocationLinks[i].getElementsByTagName('span');

            for(let y = 0; y < linkElements.length; y++){

                if(json.hasOwnProperty(linkElements[y].className)){
                    linkElements[y].innerHTML = json[linkElements[y].className];
                }
            }

        }
    }

    function updateDeliveryInfo(){
        let delivery = new Delivery();

        delivery.getOffers();

        delivery.getPoints();
    }
}