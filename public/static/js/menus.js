/**
 * Created by A.J on 2021/3/24.
 */
$(document).ready(function(){
    $("#primarymenu").on("click", function(){
        var obj = $(this);
        obj.find("div.spinner-border").removeClass("d-none");
        $.post("saveprimarymenu", $(this).parents("form").serialize(),
            function(data){
                obj.find("div.spinner-border").addClass("d-none");
                if(data != "ok"){
                    $.openAlert($("#submit").data("error"), data);
                }
            });
    });
    $("#secondarymenu").on("click", function(){
        var obj = $(this);
        obj.find("div.spinner-border").removeClass("d-none");
        $.post("savesecondarymenu", $(this).parents("form").serialize(),
            function(data){
                obj.find("div.spinner-border").addClass("d-none");
                if(data != "ok"){
                    $.openAlert($("#submit").data("error"), data);
                }
            });
    });
    $("#primarycategories").on("click", function(){
        var obj = $(this);
        $.openConfirm(obj.data("title"), obj.data("alert"), $("#jpwrt_yes").text(), $("#jpwrt_cancel").text(), function(){
            obj.find("div.spinner-border").removeClass("d-none");
            $.post("primarycategoriesmenu", {},
                function(data){
                    obj.find("div.spinner-border").addClass("d-none");
                    if(data == "ok"){
                        $.openAlert($("#jpwrt_ok").text(), obj.data("content"), "success", "", 2);
                    }
                    else{
                        $.openAlert($("#jpwrt_err").text(), data);
                    }
                });
        });
    });
    $("#secondarycategories").on("click", function(){
        var obj = $(this);
        $.openConfirm(obj.data("title"), obj.data("alert"), $("#jpwrt_yes").text(), $("#jpwrt_cancel").text(), function(){
            obj.find("div.spinner-border").removeClass("d-none");
            $.post("secondarycategoriesmenu", {},
                function(data){
                    obj.find("div.spinner-border").addClass("d-none");
                    if(data == "ok"){
                        $.openAlert($("#jpwrt_ok").text(), obj.data("content"), "success", "", 2);
                    }
                    else{
                        $.openAlert($("#jpwrt_err").text(), data);
                    }
                });
        });
    });
    $("#primarypages").on("click", function(){
        var obj = $(this);
        $.openConfirm(obj.data("title"), obj.data("alert"), $("#jpwrt_yes").text(), $("#jpwrt_cancel").text(), function(){
            obj.find("div.spinner-border").removeClass("d-none");
            $.post("primarypagesmenu", {},
                function(data){
                    obj.find("div.spinner-border").addClass("d-none");
                    if(data == "ok"){
                        $.openAlert($("#jpwrt_ok").text(), obj.data("content"), "success", "", 2);
                    }
                    else{
                        $.openAlert($("#jpwrt_err").text(), data);
                    }
                });
        });
    });
    $("#secondarypages").on("click", function(){
        var obj = $(this);
        $.openConfirm(obj.data("title"), obj.data("alert"), $("#jpwrt_yes").text(), $("#jpwrt_cancel").text(), function(){
            obj.find("div.spinner-border").removeClass("d-none");
            $.post("secondarypagesmenu", {},
                function(data){
                    obj.find("div.spinner-border").addClass("d-none");
                    if(data == "ok"){
                        $.openAlert($("#jpwrt_ok").text(), obj.data("content"), "success", "", 2);
                    }
                    else{
                        $.openAlert($("#jpwrt_err").text(), data);
                    }
                });
        });
    });
    $('#addmenu').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        $('#addmenuname').text(button.data("name"));
        $.post("addmenu", {menutype: button.data("menutype")},
            function(data){
                $("#addmenuload").addClass("d-none");
                $("#addmenudiv").html(data).removeClass("d-none");
            });
    });
    $('#showmenu').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        $('#showmenuname').text(button.data("name"));
        $.post("showmenu", {menutype: button.data("menutype")},
            function(data){
                $("#showmenuload").addClass("d-none");
                $("#showmenudiv").html(data).removeClass("d-none");
            });
    });
    $("#showmenudiv").on("click", "#menureload", function(){
        $("#showmenudiv").addClass("d-none");
        $("#showmenuload").removeClass("d-none");
        $.post("showmenu", {menutype: $(this).data("menutype")},
            function(data){
                $("#showmenuload").addClass("d-none");
                $("#showmenudiv").html(data).removeClass("d-none");
            });
    });
});