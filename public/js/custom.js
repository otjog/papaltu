/* Quantity Button ****/

let quantity = {
    'buttons': {
        'increment' : document.getElementsByClassName('quantity_inc'),
        'decrement' : document.getElementsByClassName('quantity_dec')
    },
    'inputs' : document.getElementsByClassName('quantity_input')
};

for(let buttonsType in quantity.buttons){

    if(quantity.buttons.hasOwnProperty(buttonsType)){

        for( let buttonIndex in buttonsType){

           if(quantity.buttons[ buttonsType].hasOwnProperty(buttonIndex)){

               quantity.buttons[ buttonsType ][ buttonIndex ].addEventListener('click', function(e){
                   e = e || event;
                   changeQuantity(e, buttonIndex)
               });

           }

        }

    }

}

function changeQuantity(e, buttonIndex){

    let minValue = e.target.dataset.quantityMinValue;

    if ( e.target.classList.contains('quantity_inc')) {
        ++quantity.inputs[buttonIndex].value;
    } else if(e.target.classList.contains('quantity_dec')){
        if(quantity.inputs[buttonIndex].value > minValue)
            --quantity.inputs[buttonIndex].value;
    }
}
//END Quantity Button


/* FancyBox************/

$(".fancybox").fancybox({
    openEffect	: 'none',
    closeEffect	: 'none'
});
//END FancyBox


/* Tabs **************/
let tabs = document.getElementById('tabs');

if(tabs !== null && tabs !== undefined){
    let activeTab       = tabs.getElementsByClassName('active');
    let content         = document.getElementById('tab-data');
    let contentDatas    = content.getElementsByClassName('tab-data');

    toogelDisplayStyle(content, contentDatas, activeTab[0].dataset.tabindex)

    tabs.addEventListener('click', function (e) {

        e = e || event;

        //узнаем наименование вкладки
        let tabIndex = e.target.dataset.tabindex;

        //включаем у вкладки класс active
        activeTab[0].classList.toggle('active');

        e.target.classList.toggle('active');

        toogelDisplayStyle(content, contentDatas, e.target.dataset.tabindex)

    });
}

function toogelDisplayStyle(content, contentDatas, tabIndex){
    for(let i = 0; i < contentDatas.length; i++){
        contentDatas[ i ].style.display = 'none';
    }

    content.getElementsByClassName('data-' + tabIndex)[0].style.display = 'block';
}
//END Tabs


/* Token *************/

function getToken(){
    return document.getElementsByName('csrf-token')[0].getAttribute('content');
}
//END Token


/* DaData ************/

let forms = document.forms;

for(let f = 0; f < forms.length; f++){

    for(let inp = 0; inp < forms[f].elements.length; inp++){

        for(let i in forms[f].elements[inp].dataset){

            if(i = 'suggestion'){

                let type = forms[f].elements[inp].dataset[i];

                let id = forms[f].elements[inp].id;

                if(id !== ''){

                    let formSuggest = new DaData(id, type);

                    formSuggest.suggestions();

                }

            }

        }

    }

}
//END DaData


/* Maps Google ******/

function initMap( json ) {

    console.log(json);

    var geo = json._geo;

    delete json._geo;

    var location = {lat: +geo.latitude, lng: +geo.longitude};

    var map = new google.maps.Map(
        document.getElementById('map'), {zoom: 12, center: location});

    for(var company in json.points){

        if(json.points.hasOwnProperty(company)){

            for(var terminalType in json.points[company]){

                if(json.points[company].hasOwnProperty(terminalType)){

                    for( var terminal in json.points[company][terminalType] ){

                        if(json.points[company][terminalType].hasOwnProperty(terminal)){

                            var geoShop = json.points[company][terminalType][terminal].geoCoordinates;

                            var locationShop = {lat: +geoShop.latitude, lng: +geoShop.longitude};

                            var image = 'http://myshop.loc/storage/img/elements/delivery/' + company + '/marker-' + terminalType + '.png';

                            var marker = new google.maps.Marker({position: locationShop, map: map, icon: image});

                        }

                    }


                }

            }

        }

    }

    console.log(json);
}
//END Maps Google

/* Delivery Calc And Point */
let delivery = new Delivery();

delivery.calculate();
delivery.points();
//END Delivery