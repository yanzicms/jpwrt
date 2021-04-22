/**
 * Created by A.J on 2021/3/10.
 */
$(document).ready(function(){
    $("#jsnpp_switch").on("click", function(){
        if($("#jsnpp_side").is(':hidden')){
            $("#jsnpp_side").show("normal");
            if($(window).width() < 576){
                $("table").hide();
                $("#jsnpp_mark").show();
            }
        }
        else{
            $("#jsnpp_side").hide("normal");
            $("#jsnpp_mark").hide();
            if($(window).width() < 576){
                $("table").show();
            }
        }
    });
    var dropdown, current;
    $(".firstlevel").on("mouseover", function(){
        if(!$(this).hasClass("bg-danger") && $(this).siblings("nav:first").is(':hidden')){
            current = $(this);
            if(typeof dropdown == "object"){
                dropdown.remove();
            }
            current.data("toggle", "dropdown");
            dropdown = $("<div></div>");
            dropdown.addClass("dropdown-menu m-0 rounded-0 border-left-0");
            current.siblings("nav:first").children("a").each(function(){
                var son = $(this).clone();
                son.removeClass("nav-link text-white-50 text-white").addClass("dropdown-item").children("span").removeClass("ml-3");
                son.appendTo(dropdown);
            });
            current.after(dropdown);
            dropdown.addClass("show");
            var over = dropdown.offset().top - $(window).scrollTop() + dropdown.height() - $(window).height();
            if(over > 0){
                dropdown.css("top", "-" + (over + 20) + "px");
            }
            if(!current.hasClass("bg-danger")){
                current.addClass("jsnpp_active bg-info");
            }
            dropdown.on("mouseover", function(){
                if(!current.hasClass("bg-danger")){
                    current.addClass("jsnpp_active bg-info");
                }
                dropdown.addClass("show");
            });
            dropdown.on("mouseleave", function(){
                dropdown.removeClass("show").remove();
                if(!current.hasClass("bg-danger")){
                    current.removeClass("jsnpp_active bg-info");
                }
            });
        }
        else{
            $(this).addClass("bg-info");
        }
    });
    $(".firstlevel").on("mouseleave", function(){
        if(typeof dropdown == "object"){
            dropdown.removeClass("show");
            if(!current.hasClass("bg-danger")){
                current.removeClass("jsnpp_active bg-info");
            }
        }
        $(this).removeClass("bg-info");
    });
    $("#submit").on("click", function(){
        var action = $(this).data("action");
        if(typeof action != "undefined"){
            $.jpwrtsubmit($(this), $(this).data("action"));
        }
        else{
            $.jpwrtsubmit($(this));
        }
    });
});
$.extend({
    jpwrtsubmit: function(obj, url){
        url = url || "";
        $("#submitloading").removeClass("d-none");
        $.post(url, obj.parents("form").serialize(),
            function(data){
                $("#submitloading").addClass("d-none");
                if(data.result == "ok"){
                    var msg;
                    if(typeof obj.data("ok") != "undefined"){
                        msg = obj.data("ok");
                    }
                    else{
                        msg = $("#jpwrt_saveok").text();
                    }
                    $.openAlert($("#jpwrt_ok").text(), msg, "success", "", 2, function(){
                        if(obj.hasClass("reload")){
                            location.reload();
                        }
                    });
                }
                else{
                    $.openAlert($("#jpwrt_err").text(), data.message);
                }
            });
    }
});