/*** Created by A.J on 2021/3/9.*/$(document).ready(function(){$("#submit").on("click", function(){$("#submitloading").removeClass("d-none");$.post("", $(this).parents("form").serialize(),function(data){$("#submitloading").addClass("d-none");if(data.result == "ok"){$.openAlert($("#submit").data("ok"), $("#submit").data("content"), "success", "", 2, function(){location.href = $("#submit").data("next");});}else{$.openAlert($("#submit").data("error"), data.message);$("#captcha").click();$("#inputcaptcha").val("");}});});});