/**
 * Created by A.J on 2021/3/26.
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
            $.post("deleteslidegroup", {id: obj.data("id")},
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
});