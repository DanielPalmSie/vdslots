clicked_key = null;
jsoneditor = null;
timeOut = null;

$(document).ready(function () {
    editor('form');

    $(document.body).on("click", ":not(#jsoneditor, #jsoneditor *)", function (e) {
        $("#jsoneditor").hide();
    });

    openHandler();
    editHandler();
});


function editor(mode) {
    if (jsoneditor) {
        jsoneditor.destroy();
    }

    const container = document.getElementById("jsoneditor");
    const options = {
        'mode': mode,
        'search': false,
        'navigationBar': false,
        'mainMenuBar': false,
        'enableSort': false,
        'enableTransform': false,

        onChangeJSON: function (json) {
            clearInterval(timeOut);

            $("#informer span.loader").show();
            $("#informer span.open").hide();

            timeOut = setInterval(function () {
                clearInterval(timeOut);
                var data = {action: "save", key: clicked_key, data: JSON.stringify(json)};
                $.post("",
                {
                    ...data,
                    csrf_token: document.querySelector('meta[name="csrf_token"]').content
                }, function (data) {
                    console.log(data);
                    setTimeout(function (){
                        $("#informer span.loader").hide();
                        $("#informer span.open").show();
                    }, 3000);
                }, "json").fail(function (jqxhr, textStatus, error) {
                    var err = textStatus + ", " + error;
                    console.error("Request Failed: " + err);
                    alert("An error occurred while saving. Please try again."); // Optional user notification
                });
            }, 1000);

        }
    }

    jsoneditor = new JSONEditor(container, options)
}

function editHandler() {
    $("#overriden .nice_r_c").click();
    $(".nice_r > .nice_r_c").append("<span class='edit'>📝</span>");

    $('.edit').click(function (event) {
        editor('form');
        var key = $(".nice_r_k", $(this).parent()).html();
        clicked_key = key;

        $.getJSON("", {action: 'read', key: key})
            .done(function (json) {
                jsoneditor.set(json);
                $("#jsoneditor").show();
            })
            .fail(function (jqxhr, textStatus, error) {
                var err = textStatus + ", " + error;
                console.log("Request Failed: " + err);
            });

        event.stopPropagation();
    })

}

function openHandler() {
    $("#informer span").on("click", "", function (e) {
        clicked_key = null;

        $.getJSON("", {action: 'open'})
            .done(function (json) {
                editor('tree');
                jsoneditor.set(json);
                $("#jsoneditor").show();
            })
            .fail(function (jqxhr, textStatus, error) {
                var err = textStatus + ", " + error;
                console.log("Request Failed: " + err);
            });
    });
}
