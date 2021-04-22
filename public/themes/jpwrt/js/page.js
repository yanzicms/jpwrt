/**
 * Created by A.J on 2021/4/16.
 */
$(document).ready(function(){
    $("#submitpassword").on("click", function(){
        $.post($("#url_password").text(), $(this).parents("form").serialize(),
            function(data){
                if(data.result == "ok"){
                    if(data.thumbnail != ""){
                        $("#content").html('<img class="img-fluid" src="' + data.thumbnail + '">' + data.message);
                    }
                    else{
                        $("#content").html(data.message);
                    }
                }
                else{
                    $.openAlert($("#error").text(), data.message);
                }
            });
    });
});