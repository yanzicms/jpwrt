/**
 * Created by A.J on 2021/4/1.
 */
$(document).ready(function(){
    if($("#imgdiv").find("img").attr("src") != ""){
        $("#delimgdiv").removeClass("d-none");
        $("#imgdiv").removeClass("d-none");
        $("#uploadimgdiv").addClass("d-none");
    }
    $("#uploadimg").uploadfile({
        url: "uploadimggeneral",
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
    $("#delimg").unbind().on("click", function(){
        var obj = $(this);
        $.openConfirm(obj.data("title"), obj.data("alert"), $("#jpwrt_yes").text(), $("#jpwrt_cancel").text(), function(){
            obj.find("div.spinner-border").removeClass("d-none");
            $.post("deleteimggeneral", {},
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
});