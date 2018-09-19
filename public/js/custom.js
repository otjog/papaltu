/* Quantity Button ****/

let quantity = {
    'buttons': {
        'increment' : document.getElementsByClassName('quantity_inc'),
        'decrement' : document.getElementsByClassName('quantity_dec'),
        'delete'    : document.getElementsByClassName('quantity_del'),
        'update'    : document.getElementsByClassName('quantity_upd')
    },
    'inputs'    : document.getElementsByClassName('quantity_input'),
    'form'      : document.getElementById('basket_form')
};

for(let buttonsType in quantity.buttons){

    if(quantity.buttons.hasOwnProperty(buttonsType)){

        for( let buttonIndex in buttonsType){

           if(quantity.buttons[ buttonsType].hasOwnProperty(buttonIndex)){

               switch(buttonsType){
                   case 'increment':
                   case 'decrement':
                       quantity.buttons[ buttonsType ][ buttonIndex ].addEventListener('click', function(e){
                           e = e || event;
                           changeQuantity(e, buttonIndex)
                       });
                       break;
                   case 'delete':
                       quantity.buttons[ buttonsType ][ buttonIndex ].addEventListener('click', function(e){
                           quantity.inputs[buttonIndex].value = 0;
                           quantity.form.submit();
                       });
                       break;
                   case 'update':
                       quantity.buttons[ buttonsType ][ buttonIndex ].addEventListener('click', function(e){
                           //любая кнопка обновляет все товары
                           quantity.form.submit();
                       });
                       break;
               }



           }

        }

    }

}

function changeQuantity(e, buttonIndex){
console.log(buttonIndex);
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

    let geo = json._geo;

    delete json._geo;

    let location = {lat: +geo.latitude, lng: +geo.longitude};

    let map = new google.maps.Map(
        document.getElementById('map'), {zoom: 12, center: location});

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
//END Maps Google

/* Delivery Calc And Point */
let delivery = new Delivery();

delivery.calculate();
delivery.points();
//END Delivery