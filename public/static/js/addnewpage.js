/**
 * Created by A.J on 2021/3/16.
 */
$(document).ready(function(){
    var lang = $("#language").text() == "zh-cn" ? "zh-jian" : "en";
    var he = HE.getEditor('editor', {
        skin : 'simple',
        uploadPhoto : true,
        uploadPhotoHandler : 'heuploadeditor',
        uploadPhotoSize : 0,
        uploadPhotoType : 'gif,png,jpg,jpeg',
        uploadPhotoSizeError : '不能上传大于××KB的图片',
        uploadPhotoTypeError : '只能上传gif,png,jpg,jpeg格式的图片',
        lang : lang
    });
    $("#visibility").on("change", function(){
        if($("#visibility").val() == "1"){
            $("#passwordiv").removeClass("d-none");
        }
        else{
            $("#passwordiv").addClass("d-none");
        }
    });
    if($("#uploadimg").length > 0){
        $("#uploadimg").uploadfile({
            url: "uploadimgeditor",
            type: "png,jpg,jpeg,gif,webp",
            success: function(data){
                $("#imguptxt").addClass("d-none");
                $("#imgdiv").removeClass("d-none").find("img").attr("src", $("#webroot").text() + data);
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
                $.post("deleteimgeditor", {},
                    function(data){
                        obj.find("div.spinner-border").addClass("d-none");
                        if(data.result == "ok"){
                            $("#imgdiv").addClass("d-none").find("img").attr("src", "");
                            $("#imguptxt").removeClass("d-none");
                        }
                        else{
                            $.openAlert($("#jpwrt_err").text(), data.message);
                        }
                    });
            });
        });
    }
    $("#submitpost").on("click", function(){
        he.sync();
        if($("#summary").val() == ""){
            var text = he.getText();
            text = text.trim().replace(/\n/g, ' ');
            if(text.length > 500){
                text = text.substr(0, 500) + "...";
            }
            $("#summary").val(text);
        }
        $.jpwrtsubmit($(this));
    });
});