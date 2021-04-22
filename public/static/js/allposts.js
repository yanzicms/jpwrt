/**
 * Created by A.J on 2021/3/20.
 */
$(document).ready(function(){
    $(".delete").on("click", function(){
        var obj = $(this);
        obj.find("div.spinner-border").removeClass("d-none");
        $.post("totrash", {id: obj.data("id")},
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
    if($("#putontop").length > 0){
        $(".putontop").on("change", function(){
            var obj = $(this);
            var checked = 0;
            if($(this).prop("checked")){
                checked = 1;
            }
            $.post("putontop", {id: obj.data("id"), istop: checked},
                function(data){
                    if(data.result == "ok"){
                        if(checked == 1){
                            obj.next("label").removeClass("text-secondary").addClass("text-primary").find("small").text($("#putontop").text());
                        }
                        else{
                            obj.next("label").removeClass("text-primary").addClass("text-secondary").find("small").text($("#notputontop").text());
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
        $(".recommended").on("change", function(){
            var obj = $(this);
            var checked = 0;
            if($(this).prop("checked")){
                checked = 1;
            }
            $.post("recommended", {id: obj.data("id"), recommended: checked},
                function(data){
                    if(data.result == "ok"){
                        if(checked == 1){
                            obj.next("label").removeClass("text-secondary").addClass("text-primary").find("small").text($("#recommended").text());
                        }
                        else{
                            obj.next("label").removeClass("text-primary").addClass("text-secondary").find("small").text($("#notrecommended").text());
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
        $(".putonreview").on("change", function(){
            var obj = $(this);
            var checked = 0;
            if($(this).prop("checked")){
                checked = 1;
            }
            $.post("review", {id: obj.data("id"), review: checked},
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
    }
});