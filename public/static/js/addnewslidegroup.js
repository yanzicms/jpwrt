/**
 * Created by A.J on 2021/3/26.
 */
$(document).ready(function(){
    $("#addnewslidegroupsubmit").on("click", function(){
        var obj = $(this);
        obj.find("div.spinner-border").removeClass("d-none");
        $.post("addnewslidegroupexec", obj.parents("form").serialize(),
            function(data){
                obj.find("div.spinner-border").addClass("d-none");
                if(data.result == "ok"){
                    $.openAlert($("#jpwrt_ok").text(), obj.data("content"), "success", "", 2, function(){
                        $('#contentmodal').modal('hide');
                        location.reload();
                    });
                }
                else{
                    $.openAlert($("#submit").data("error"), data.message);
                }
            });
    });
});