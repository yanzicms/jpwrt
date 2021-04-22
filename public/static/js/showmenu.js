/**
 * Created by A.J on 2021/3/26.
 */
$(document).ready(function(){
    $(".delete").on("click", function(){
        var obj = $(this);
        $.openConfirm(obj.data("title"), obj.data("name") + "，" + obj.data("alert"), $("#jpwrt_yes").text(), $("#jpwrt_cancel").text(), function(){
            obj.find("div.spinner-border").removeClass("d-none");
            $.post("deletemenu", {id: obj.data("id")},
                function(data){
                    obj.find("div.spinner-border").addClass("d-none");
                    if(data.result == "ok"){
                        obj.parents("tr").remove();
                    }
                    else{
                        $.openAlert($("#jpwrt_err").text(), data.message);
                    }
                });
        });
    });
    $(".orderclass").on("change", function(){
        $.post("ordermenu", {id: $(this).data("id"), sort: $(this).val()},
            function(data){
                if(data.result != "ok"){
                    $.openAlert($("#jpwrt_err").text(), data.message);
                }
            });
    });
    $(".iconclass").on("change", function(){
        $.post("menuicon", {id: $(this).data("id"), icon: $(this).val()},
            function(data){
                if(data.result != "ok"){
                    $.openAlert($("#jpwrt_err").text(), data.message);
                }
            });
    });
    $('[data-toggle="popover"]').popover();
});