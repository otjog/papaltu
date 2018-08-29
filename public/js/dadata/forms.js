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
                $("#" + self.id + "_json").val(JSON.stringify(suggestion));

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

        let queryString = 'address_json=' + JSON.stringify(suggestion);

        let headers = {
            'X-Module'      : 'geo|'
        };

        let ajaxReq = new Ajax("POST", queryString, headers);
        //todo проверить, если объекта нет, делать submit формы
        ajaxReq.req.onreadystatechange = function() {

            if (ajaxReq.req.readyState !== 4) return;

            let delivery = new Delivery();

            delivery.calculate();

            delivery.points();

        };

        ajaxReq.sendRequest();
    }
}