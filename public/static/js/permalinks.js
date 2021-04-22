/**
 * Created by A.J on 2021/4/1.
 */
$(document).ready(function(){
    $("#tags").on("click", "button", function(){
        if($("#custom").val() == ""){
            $("#custom").val($(this).text());
        }
        else{
            $("#custom").val($("#custom").val() + "/" + $(this).text());
        }
    });
});