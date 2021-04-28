/**
 * Created by A.J on 2021/4/6.
 */
$(document).ready(function(){
    $("#submit").on("click", function(){
        $.post("", $(this).parents("form").serialize(),
            function(data){
                if(data.result == "ok"){
                    $.openAlert($("#successfully").text(), $("#submitted").text(), "success", "", 2, function(){
                        location.reload();
                    });
                }
                else{
                    $.openAlert($("#error").text(), data.message);
                }
            });
    });
    $(".reply").on("click", function(){
        var obj = $(this);
        if(obj.prev("form").hasClass("d-none")){
            obj.prev("form").removeClass("d-none");
            obj.addClass("d-none");
        }
    });
    $(".cancelreply").on("click", function(){
        var obj = $(this).parents("form");
        obj.addClass("d-none");
        obj.next(".reply").removeClass("d-none");
    });
    $(".submitreply").on("click", function(){
        $.post("", $(this).parents("form").serialize(),
            function(data){
                if(data.result == "ok"){
                    $.openAlert($("#successfully").text(), $("#submitted").text(), "success", "", 2, function(){
                        location.reload();
                    });
                }
                else{
                    $.openAlert($("#error").text(), data.message);
                }
            });
    });
    var liked = false, disliked = false;
    $("#like").on("click", function(){
        var obj = $(this), like = parseInt($(this).find("span").text());
        if(liked){
            $.openAlert($("#successfully").text(), $("#feedback").text(), "success", "", 2);
        }
        else{
            $.post($("#url_like").text(), {id: $("#id").val()},
                function(data){
                    if(data.result == "ok"){
                        liked = true;
                        $.openAlert($("#successfully").text(), $("#feedback").text(), "success", "", 2, function(){
                            obj.find("span").text(like + 1);
                        });
                    }
                    else{
                        $.openAlert($("#error").text(), data.message);
                    }
                });
        }
    });
    $("#dislike").on("click", function(){
        var obj = $(this), dislike = parseInt($(this).find("span").text());
        if(disliked){
            $.openAlert($("#successfully").text(), $("#feedback").text(), "success", "", 2);
        }
        else{
            $.post($("#url_dislike").text(), {id: $("#id").val()},
                function(data){
                    if(data.result == "ok"){
                        disliked = true;
                        $.openAlert($("#successfully").text(), $("#feedback").text(), "success", "", 2, function(){
                            obj.find("span").text(dislike + 1);
                        });
                    }
                    else{
                        $.openAlert($("#error").text(), data.message);
                    }
                });
        }
    });
    $("#submitpassword").on("click", function(){
        $.post($("#url_password").text(), $(this).parents("form").serialize(),
            function(data){
                if(data.result == "ok"){
                    $("#content").html(data.message);
                }
                else{
                    $.openAlert($("#error").text(), data.message);
                }
            });
    });
    $("#content").find("img").addClass("img-fluid");
    prettyPrint();
});