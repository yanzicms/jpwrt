/**
 * Created by A.J on 2021/4/2.
 */
$(document).ready(function(){
    if($("#homepage1").prop("checked")){
        $("#staticpagediv").addClass("d-none");
        $("#homeshowdiv").removeClass("d-none");
    }
    else if($("#homepage2").prop("checked")){
        $("#homeshowdiv").addClass("d-none");
        $("#staticpagediv").removeClass("d-none");
    }
    $("#homepage1").on("click", function(){
        $("#staticpagediv").addClass("d-none");
        $("#homeshowdiv").removeClass("d-none");
    });
    $("#homepage2").on("click", function(){
        $("#homeshowdiv").addClass("d-none");
        $("#staticpagediv").removeClass("d-none");
    });
});