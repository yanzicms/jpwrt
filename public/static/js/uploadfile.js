/**
 * Project: uploadfile
 * Description: jQuery file upload plugin
 * Author: A.J <804644245@qq.com>
 * Copyright: http://www.jpwrt.com All rights reserved.
 * Version: 2.1.1
 */

(function ($){
    $.fn.uploadfile = function(options){
        options = options || {};
        var opts = $.extend($.fn.uploadfile.defaults, options);
        return this.each(function() {
            $.fn.uploadfile.jpwrtupload($(this), opts);
        });
    };
    $.fn.uploadfile.jpwrtupload = function(obj, opts) {
        if(obj.prop("nodeName").toLowerCase() == "form"){
            if(typeof obj.attr("enctype") == "undefined"){
                obj.attr("enctype", "multipart/form-data");
            }
            obj.submit(function (e) {
                e.preventDefault();
                if(typeof opts.before == "function"){
                    opts.before(obj);
                }
                var formobj = new FormData(obj[0]);
                $.each(opts.data, function(index, value){
                    formobj.append(index, value);
                });
                $.ajax({
                    url: opts.url,
                    type: "post",
                    data: formobj,
                    cache: false,
                    processData:false,
                    contentType:false,
                    success: function(res) {
                        if(typeof opts.after == "function"){
                            opts.after(obj);
                        }
                        if(typeof opts.success == "function"){
                            opts.success(res);
                        }
                    },
                    error: function(err) {
                        if(typeof opts.after == "function"){
                            opts.after(obj);
                        }
                        if(typeof opts.error == "function"){
                            opts.error(err);
                        }
                    }
                });
            });
        }
        else{
            if($("#_jpwrt_upload_form").length < 1){
                $("body").append('<form id="_jpwrt_upload_form" enctype="multipart/form-data" style="display: none"><input type="file" name="file" id="_jpwrt_file"></form>');
            }
            obj.unbind().on("click", function (e) {
                e.preventDefault();
                $("#_jpwrt_file").click();
            });
            $("#_jpwrt_file").unbind().on("change", function(){
                var allow = true;
                if(opts.type != ""){
                    var file = $("#_jpwrt_file").val();
                    var ext = file.substring(file.lastIndexOf(".") + 1).toLowerCase();
                    var typearr = opts.type.split(",");
                    var arrlen = typearr.length;
                    allow = false;
                    for(var i = 0; i < arrlen; i ++){
                        if(typearr[i].trim().toLowerCase() == ext){
                            allow = true;
                        }
                    }
                }
                if(allow){
                    if(typeof opts.before == "function"){
                        opts.before(obj);
                    }
                    var formobj = new FormData($('#_jpwrt_upload_form')[0]);
                    $.each(opts.data, function(index, value){
                        formobj.append(index, value);
                    });
                    $.ajax({
                        url: opts.url,
                        type: "post",
                        data: formobj,
                        cache: false,
                        processData:false,
                        contentType:false,
                        success: function(res) {
                            if(typeof opts.after == "function"){
                                opts.after(obj);
                            }
                            if(typeof opts.success == "function"){
                                opts.success(res);
                            }
                        },
                        error: function(err) {
                            if(typeof opts.after == "function"){
                                opts.after(obj);
                            }
                            if(typeof opts.error == "function"){
                                opts.error(err);
                            }
                        }
                    });
                }
                else{
                    if(opts.errormsg != ""){
                        alert(opts.errormsg);
                    }
                    else{
                        alert("File type is not allowed");
                    }
                }
            });
        }
    };
    $.fn.uploadfile.defaults = {
        url: "",
        data: {},
        type: "",
        errormsg: "",
        error: "",
        success: "",
        before: "",
        after: ""
    };
})(jQuery);