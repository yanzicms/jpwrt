/**
 * Created by A.J on 2021/3/28.
 */
$(document).ready(function(){
    $("#uploadimg").uploadfile({
        url: "uploadimglink",
        type: "png,jpg,jpeg,gif,webp",
        success: function(data){
            $("#imgdiv").removeClass("d-none").find("img").attr("src", $("#webroot").text() + data);
            $("#delimgdiv").removeClass("d-none");
            $("#uploadimgdiv").addClass("d-none");
        },
        before: function(e){
            e.find("div.spinner-border").removeClass("d-none");
        },
        after: function(e){
            e.find("div.spinner-border").addClass("d-none");
        }
    });
    $("#delimg").on("click", function(){
        var obj = $(this);
        $.openConfirm(obj.data("title"), obj.data("alert"), $("#jpwrt_yes").text(), $("#jpwrt_cancel").text(), function(){
            obj.find("div.spinner-border").removeClass("d-none");
            $.post("deleteimglink", {},
                function(data){
                    obj.find("div.spinner-border").addClass("d-none");
                    if(data.result == "ok"){
                        $("#imgdiv").addClass("d-none").find("img").attr("src", "");
                        $("#delimgdiv").addClass("d-none");
                        $("#uploadimgdiv").removeClass("d-none");
                    }
                    else{
                        $.openAlert($("#jpwrt_err").text(), data.message);
                    }
                });
        });
    });
    $("#addnewlinksubmit").on("click", function(){
        var obj = $(this);
        obj.find("div.spinner-border").removeClass("d-none");
        $.post("addnewlinkexec", obj.parents("form").serialize(),
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