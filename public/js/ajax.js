function Ajax(method, queryString, headers, requestName){

    this.origin     = window.location.origin;

    this.path       = this.origin + '/ajax';

    this.method     = method;

    this.headers    = headers;

    this.requestName = requestName;

    this.timeout    = 30000;

    this.queryString = queryString;

    this.req        = getXmlHttpRequest();

    this.sendRequest = function(){
        if(ajaxRequests[this.requestName] !== undefined){
            ajaxRequests[this.requestName].abortRequest();

        }

        this.req.timeout = this.timeout;

        if(this.method === "GET"){

            if(this.queryString !== ''){
                this.queryString = '?'+ this.queryString;
            }

            this.req.open(this.method, this.path + this.queryString, true);

            this.setHeaders();

            this.req.send(null);

        }else if(this.method === "POST"){

            this.req.open(this.method, this.path, true);

            this.setHeaders();

            this.req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            this.req.send(this.queryString);
        }

        this.addRequest()

    };

    this.setHeaders = function(){
        this.req.setRequestHeader('X-CSRF-TOKEN', getToken());
        this.req.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        for(let header in this.headers){
            if(this.headers.hasOwnProperty(header)){
                this.req.setRequestHeader(header, this.headers[header]);
            }
        }
    };

    this.addRequest = function () {
        ajaxRequests[this.requestName] = this;
    };

    this.abortRequest = function () {
        this.req.abort();
    }
    
}

/**
 * Здесь храним созданный объекты ajax-запросов
 * @type {{}}
 */
let ajaxRequests = {};