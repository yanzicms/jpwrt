/*** Created by A.J on 2021/6/9.*/$(document).ready(function(){$(".restore").on("click", function(){var obj = $(this);$.openConfirm(obj.data("title"), obj.data("file") + "，" + obj.data("alert"), $("#jpwrt_yes").text(), $("#jpwrt_cancel").text(), function(){obj.find("div.spinner-border").removeClass("d-none");$.post("restorebackup", {file: obj.data("file")},function(data){obj.find("div.spinner-border").addClass("d-none");if(data == "ok"){$.openAlert($("#restoreok").text(), $("#restorecontent").text(), "success", "", 2);}else{$.openAlert($("#jpwrt_err").text(), data);}});});});$(".delete").on("click", function(){var obj = $(this);$.openConfirm(obj.data("title"), obj.data("file") + "，" + obj.data("alert"), $("#jpwrt_yes").text(), $("#jpwrt_cancel").text(), function(){obj.find("div.spinner-border").removeClass("d-none");$.post("deletebackup", {file: obj.data("file")},function(data){obj.find("div.spinner-border").addClass("d-none");if(data.result == "ok"){obj.parents("tr").remove();}else{$.openAlert($("#jpwrt_err").text(), data.message);}});});});$("#newbackup").on("click", function(){var obj = $(this);$.openConfirm(obj.data("title"), obj.data("alert"), $("#jpwrt_yes").text(), $("#jpwrt_cancel").text(), function(){obj.find("div.spinner-border").removeClass("d-none");$.post("newbackup", {},function(data){obj.find("div.spinner-border").addClass("d-none");if(data.result == "ok"){location.reload();}else{$.openAlert($("#jpwrt_err").text(), data.message);}});});});$("#uploadfile").uploadfile({url: "uploadbackup",type: "zip",data: {},success: function(data){if(data == "ok"){$.openAlert($("#uploadok").text(), $("#uploadcontent").text(), "success", "", 2, function(){location.reload();});}else{$.openAlert($("#jpwrt_err").text(), data);}},before: function(e){e.find("div.spinner-border").removeClass("d-none");},after: function(e){e.find("div.spinner-border").addClass("d-none");}});});