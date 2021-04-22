/**
 * Created by A.J on 2021/4/6.
 */
$(document).ready(function(){
    $(".delete").on("click", function(){
        var obj = $(this);
        $.openConfirm(obj.data("title"), obj.data("name") + "，" + obj.data("alert"), $("#jpwrt_yes").text(), $("#jpwrt_cancel").text(), function(){
            obj.find("div.spinner-border").removeClass("d-none");
            $.post("deletecomments", {id: obj.data("id")},
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
    $(".putonreview").on("change", function(){
        var obj = $(this);
        var checked = 0;
        if($(this).prop("checked")){
            checked = 1;
        }
        $.post("reviewcomments", {id: obj.data("id"), pid: obj.data("pid"), review: checked},
            function(data){
                if(data.result == "ok"){
                    if(checked == 1){
                        obj.next("label").removeClass("text-secondary").addClass("text-primary").find("small").text($("#passed").text());
                    }
                    else{
                        obj.next("label").removeClass("text-primary").addClass("text-secondary").find("small").text($("#notpass").text());
                    }
                }
                else{
                    if(checked == 1){
                        obj.prop("checked", false);
                    }
                    else{
                        obj.prop("checked", true);
                    }
                    $.openAlert($("#jpwrt_err").text(), data.message);
                }
            });
    });
});