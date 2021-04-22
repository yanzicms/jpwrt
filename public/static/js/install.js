/**
 * Created by A.J on 2021/2/26.
 */
$(document).ready(function(){
    if($("#install").length > 0){
        if($("#langselect").val() != ""){
            $("#next").removeClass("d-none");
        }
        $("#langselect").on("change", function(){
            if($(this).val() != ""){
                $.post("", { lang: $(this).val() },
                    function(data){
                        location.reload();
                    });
            }
            else{
                $("#next").addClass("d-none");
            }
        });
    }
    if($("#checking").length > 0 && !$("#checking").hasClass("d-none")){
        $.post("", {},
            function(data){
                if(data.result == "ok"){
                    location.href = $("#checking").data("next");
                }
                else{
                    $("#checking").addClass("d-none");
                    Twoway.set('detect', data.data, '.table');
                    $("#detectresult").removeClass("d-none");
                }
            });
    }
    if($("#dbinfo").length > 0){
        $("#dbselect").on("change", function(){
            if($(this).val() == "mysql"){
                $(".mysql").removeClass("d-none");
            }
            else if($(this).val() == "sqlite"){
                $(".mysql").addClass("d-none");
            }
        });
        $("#submit").on("click", function(){
            $("#submit").attr("disabled", true);
            $("#wait").removeClass("d-none");
            $.post("", $(this).parents("form").serialize(),
                function(data){
                    $("#submit").attr("disabled", false);
                    $("#wait").addClass("d-none");
                    if(data.result == "ok"){
                        location.href = $("#submit").data("next");
                    }
                    else if(data.code == 2){
                        alert(data.message);
                    }
                    else{
                        alert($("#submit").data("error"));
                    }
                });
        });
    }
    if($("#account").length > 0){
        $("#submit").on("click", function(){
            $("#submit").attr("disabled", true);
            $("#wait").removeClass("d-none");
            $.post("", $(this).parents("form").serialize(),
                function(data){
                    $("#submit").attr("disabled", false);
                    $("#wait").addClass("d-none");
                    if(data.result == "ok"){
                        location.href = $("#submit").data("next");
                    }
                    else{
                        alert(data.message);
                    }
                });
        });
    }
    Twoway.bind({
        id: '.table',
        data: {
            detect: []
        }
    });
});
