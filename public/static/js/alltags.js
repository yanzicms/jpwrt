/*** Created by A.J on 2021/3/15.*/$(document).ready(function(){$(".delete").on("click", function(){var obj = $(this);$.openConfirm(obj.data("title"), obj.data("name") + "，" + obj.data("alert"), $("#jpwrt_yes").text(), $("#jpwrt_cancel").text(), function(){obj.find("div.spinner-border").removeClass("d-none");$.post("deletetag", {id: obj.data("id")},function(data){obj.find("div.spinner-border").addClass("d-none");if(data.result == "ok"){obj.parents("tr").remove();}else{$.openAlert($("#jpwrt_err").text(), data.message);}});});});$("#searchposts").on("click", function(){if($("#searchdiv").is(":hidden")){$("#searchdiv").show("normal");$("#searchposts").addClass("d-none");}else{$("#searchdiv").hide("normal");$("#searchposts").removeClass("d-none");}});$("#closebutton").on("click", function(){$("#searchdiv").hide("normal");$("#searchposts").removeClass("d-none");});});