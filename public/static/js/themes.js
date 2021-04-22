/**
 * Created by A.J on 2021/3/23.
 */
$(document).ready(function(){
    $(".activate").on("click", function(){
        var obj = $(this);
        obj.find("div.spinner-border").removeClass("d-none");
        $.post("", {theme: obj.data("name")},
            function(data){
                obj.find("div.spinner-border").addClass("d-none");
                if(data == "ok"){
                    location.reload();
                }
                else{
                    $.openAlert($("#submit").data("error"), data);
                }
            });
    });
    $('#themeinfo').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        $('#themename').text(button.data("name"));
        $('#themeimg').attr("src", button.data("screenshot"));
        $('#author').text(button.data("author"));
        $('#version').text(button.data("version"));
        $('#license').text(button.data("license"));
        $('#licenseurl').text(button.data("licenseurl"));
        $('#description').text(button.data("description"));
        $('#tags').text(button.data("tags"));
    });
});