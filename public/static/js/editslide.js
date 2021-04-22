/**
 * Created by A.J on 2021/3/26.
 */
$(document).ready(function(){
    if($("#imgdiv").find("img").attr("src") != ""){
        $("#delimgdiv").removeClass("d-none");
        $("#imgdiv").removeClass("d-none");
        $("#uploadimgdiv").addClass("d-none");
    }
    $("#uploadimg").uploadfile({
        url: $("#editpost_uploadimgedit").text(),
        type: "png,jpg,jpeg,gif,webp",
        data: {id: $("#id").val()},
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
    $("#delimg").unbind().on("click", function(){
        var obj = $(this);
        $.openConfirm(obj.data("title"), obj.data("alert"), $("#jpwrt_yes").text(), $("#jpwrt_cancel").text(), function(){
            obj.find("div.spinner-border").removeClass("d-none");
            $.post($("#editpost_deleteimgedit").text(), {id: $("#id").val()},
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
    $("#editslidesubmit").unbind().on("click", function(){
        var obj = $(this);
        obj.find("div.spinner-border").removeClass("d-none");
        $.post("editslideexec", obj.parents("form").serialize(),
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