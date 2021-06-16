function request(request, arguments="", id=false) {
    /*
    * request: request string
    * arguments: any ~ seperated arguments to be used in ajax
    * id: id of where the response must be placed
    */
   var load = false;
   
   var formData = new FormData();
    if(request == 'post-newsletter' || request == 'save-newsletter'){
        var items = document.getElementsByClassName("newsletter-input");
        for(var i=0, l=items.length; i<l; i++){
            if(i>0){arguments += "~";}
            arguments +=  escape(items[i].value);
        }
        load = "load-newsletters";
        
        var fileSelect = document.getElementById('newsletter-image');
        var files = fileSelect.files;
        if (files.length > 0){var file = files[0];
            formData.append('newsletter-image', file, file.name);
        }
        fileSelect = document.getElementById('newsletter-file');
        files = fileSelect.files;
        if (files.length > 0){var file = files[0];
            formData.append('newsletter-file', file, file.name);
        }
    }else if(request == 'unsubscribe' || request == 'subscribe'){
        // load = "load-subscribers";
    }
    if (request.length == 0) { 
        popUp('danger','Error: Unknown request.');
        return;
    } else {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                if(!id){
                    if(this.responseText.includes("Error:")){
                        popUp("danger", this.responseText);
                        console.log(this.response);
                    }
                    else{
                        popUp("success", this.responseText);
                        console.log(this.response);
                        closeDashboardForm();

                    }
                } else if (id.includes('populate-newsletter-editor')) {
                    var data = this.responseText.split(" [~] ");
                    var items = document.getElementsByClassName("newsletter-input");
                    for (var i=0, l=items.length; i<l; i++){
                        if (items[i].id != 'newsletter-image' && items[i].id != 'newsletter-file'){
                            items[i].value = data[i];
                        }
                    }
                } else if (id.includes('populate-newsletter-modal')) {
                    var data = this.responseText.split(" [~] ");
                    console.log(data[0]);
                    $("#view-newsletter-modal h2").html(data[0]);
                    $("#view-newsletter-modal p").html(data[1]);
                    $("#view-newsletter-modal img").attr("src", data[2]);
                    $("#view-newsletter-modal .view").attr("href", data[3]);
                    $("#view-newsletter-modal .download").attr("href", data[3]);
                } else {
                    document.getElementById(id).innerHTML = this.responseText;
                }
            }else{
                // popUp("danger", this.responseText);
            }
        };
        xmlhttp.open("POST", "./assets/php/server.php?request=" + request + "&arguments=" + arguments, true);
        xmlhttp.send(formData);
    }
}

function forward_request(request, arguments="", id=false){
    request(request, arguments, id);
}

function popUp(type, msg){
    var div = document.getElementById('small-toast');
    div.innerHTML = msg ?? "An Error has occured.";
    if(div.classList.contains('hide')){
        div.classList.remove('hide');
    }
    if(type == "success"){
        if(div.classList.contains("danger-bg")){
            div.classList.remove("danger-bg");
        }
        if(!div.classList.contains("success-bg")){
            div.classList.add("success-bg");
            div.classList.add("white-txt");
        }
    }
    else if(type == "danger"){
        if(div.classList.contains("success-bg")){
            div.classList.remove("success-bg");
        }
        if(!div.classList.contains("danger-bg")){
            div.classList.add("danger-bg");
            div.classList.add("white-txt");
        }
    }

    setTimeout(closePopUp, 10000);

}

function closePopUp(){
    var div = document.getElementById('small-toast');
    div.innerHTML = "";
    if(!div.classList.contains('hide')){
        div.classList.add('hide');
    }
    if(div.classList.contains("success-bg")){
        div.classList.remove("success-bg");
        div.classList.remove("white-txt");
    }else if(div.classList.contains("danger-bg")){
        div.classList.remove("danger-bg");
        div.classList.remove("white-txt");
    }
}