/**
 * Created by A.J on 2021/3/28.
 */
$(document).ready(function(){
    $('#contentmodal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        $('#contentname').text(button.data("name"));
        var id = button.data("id");
        if(typeof id != "undefined"){
            $.post(button.data("url"), {id: id},
                function(data){
                    $("#contentload").addClass("d-none");
                    $("#contentdiv").html(data).removeClass("d-none");
                });
        }
        else{
            $.post(button.data("url"), {},
                function(data){
                    $("#contentload").addClass("d-none");
                    $("#contentdiv").html(data).removeClass("d-none");
                });
        }
    });
    $(".delete").on("click", function(){
        var obj = $(this);
        $.openConfirm(obj.data("title"), obj.data("name") + "，" + obj.data("alert"), $("#jpwrt_yes").text(), $("#jpwrt_cancel").text(), function(){
            obj.find("div.spinner-border").removeClass("d-none");
            $.post("deletelink", {id: obj.data("id")},
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
        $.post("orderlinkshow", {id: $(this).data("id"), sort: $(this).val()},
            function(data){
                if(data.result != "ok"){
                    $.openAlert($("#jpwrt_err").text(), data.message);
                }
            });
    });
    $(".putonhome").on("change", function(){
        var obj = $(this);
        var checked = 0;
        if($(this).prop("checked")){
            checked = 1;
        }
        $.post("putonhome", {id: obj.data("id"), home: checked},
            function(data){
                if(data.result == "ok"){
                    if(checked == 1){
                        obj.next("label").removeClass("text-secondary").addClass("text-primary").find("small").text($("#putonhome").text());
                    }
                    else{
                        obj.next("label").removeClass("text-primary").addClass("text-secondary").find("small").text($("#notputonhome").text());
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
    $('[data-toggle="popover"]').popover();
});