/**
 * Created by A.J on 2021/3/25.
 */
$(document).ready(function(){
    $("#menuurl").on("change", function(){
        if($(this).val() == "custom"){
            $("#customurldiv").removeClass("d-none");
        }
        else{
            $("#customurldiv").addClass("d-none");
        }
    });
    $("#addmenusubmit").on("click", function(){
        var obj = $(this);
        obj.find("div.spinner-border").removeClass("d-none");
        $.post("addmenuexec", obj.parents("form").serialize(),
            function(data){
                obj.find("div.spinner-border").addClass("d-none");
                if(data.result == "ok"){
                    $.openAlert($("#jpwrt_ok").text(), obj.data("content"), "success", "", 2, function(){
                        $('#addmenu').modal('hide');
                    });
                }
                else{
                    $.openAlert($("#submit").data("error"), data.message);
                }
            });
    });
});