(function () {

    var i = 0;

    document.getElementById("suspensionBox").onclick = function () {

        if(i == 0){

            url = document.getElementById("parentDIV").getAttribute('data-url');

            ajax(url, {
                success: function (xhr) {
                    div = document.createElement('div');
                    div.innerHTML = xhr.responseText;
                    $("#parentDIV").append(div);
                    $("#suspensionBox").hide();
                    i++;
                },
                error: function (xhr) {
                }
            });
        }else{

            $("#suspensionBox").hide();
            $("#content-box").show();
        }
    };
    $(document).on("click", ".x-right", function(){

        $("#suspensionBox").show();
        $("#content-box").hide();
    });

    $(document).on("click",".tab1",function(){

        $(".tab1").css({"background-color":"#0f0"});
        $(".tab2").css({"background-color":"#000000"});
        $(".tab3").css({"background-color":"#000000"});

    });
    $(document).on("click",".tab2",function(){

        $(".tab2").css({"background-color":"#0f0"});
        $(".tab1").css({"background-color":"#000000"});
        $(".tab3").css({"background-color":"#000000"});

    });
    $(document).on("click",".tab3",function(){

        $(".tab3").css({"background-color":"#0f0"});
        $(".tab1").css({"background-color":"#000000"});
        $(".tab2").css({"background-color":"#000000"});

    });
    ajax = function (url, settings) {
        var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
        settings = settings || {};
        xhr.open(settings.method || 'GET', url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'text/html');
        xhr.onreadystatechange = function (state) {
            if (xhr.readyState === 4) {
                if (xhr.status === 200 && settings.success) {
                    settings.success(xhr);
                } else if (xhr.status != 200 && settings.error) {
                    settings.error(xhr);
                }
            }
        };
        xhr.send(settings.data || '');
    }

})();
