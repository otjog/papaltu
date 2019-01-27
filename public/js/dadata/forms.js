function DaData(id, type){
    this.token = "23933ff36c0c7e63248d1782df14d07badb394a0";
    this.type = type;
    this.id = id;
    let self = this;
    this.suggestions = function(){
        $("#"+ this.id).suggestions({
            token: this.token,
            type: this.type,
            count: 5,
            onSelect: function(suggestion) {
                $("#" + self.id + "_json").val(JSON.stringify(suggestion.data));

                if(self.type === 'ADDRESS'){

                    let input = document.getElementById(self.id);

                    let eventUpdateDelivery = input.dataset.eventUpdateDelivery;

                    switch(eventUpdateDelivery){

                        case 'click' :

                            let button = document.getElementsByClassName('update-delivery');

                            button[0].addEventListener(eventUpdateDelivery, function (e) {
                                self.updateDelivery(suggestion);
                            });

                            break;

                        default : self.updateDelivery(suggestion);
                    }

                }

            }

        });
    };

    this.updateDelivery = function (suggestion) {

        let queryString = 'address_json=' + JSON.stringify(suggestion.data);

        let headers = {
            'X-Module'      : 'geo|location '
        };

        /**
         * Задаем уникальное имя для нашего ajax-запроса.
         *
         * Это имя мы сохраняем в глобальный объект со всеми запросами,
         * чтобы в дальнейшем мы могли управлять ими (abort и тп.)
         *
         * @type {string}
         */
        let requestName = 'geo';

        let ajaxReq = new Ajax("POST", queryString, headers, requestName);
        //todo проверить, если объекта нет, делать submit формы
        ajaxReq.req.onreadystatechange = function() {

            if (ajaxReq.req.readyState !== 4) return;

            let reloadBlock = document.getElementsByClassName('geo_change_location');

            reloadBlock[0].innerHTML = String(ajaxReq.req.responseText);

            let delivery = new Delivery();

            delivery.calculate();

            delivery.points();

        };

        ajaxReq.sendRequest();
    }
}