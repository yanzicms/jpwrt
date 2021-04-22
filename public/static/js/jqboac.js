/**
 * jqboac 1.2.4
 * Created by A.J on 2021/1/14.
 */

$.extend({
    openAlert: function(title, content, warning, isstatic, autoclose, func) {
        var isrun = false;
        warning = warning || "warning";
        autoclose = autoclose || 0;
        if(typeof isstatic == "undefined"){
            isstatic = "static";
        }
        $.openToast(title, content, warning, isstatic);
        $.closeToast();
        if($.isFunction(func)){
            isrun = true;
            $('#jqboac_warningmodal').on('hidden.bs.modal', function (event) {
                func();
            });
        }
        if(autoclose > 0){
            setTimeout("$.closeAlert("+isrun+")", autoclose * 1000 );
        }
    },
    openConfirm: function(title, content, isok, isclose, func) {
        $.openToast(title, content, "confirm", "static", isok, isclose);
        $("#jqboac_warningtoastfooterOk").unbind().on("click", function(){
            $('#jqboac_warningmodal').modal('hide').on('hidden.bs.modal', function (event) {
                if($.isFunction(func)){
                    func();
                }
            });
            $.doCloseToast();
        });
        $.closeToast();
    },
    closeAlert: function(isrun) {
        if(isrun){
            $('#jqboac_warningmodal').modal('hide');
        }
        else{
            $('#jqboac_warningmodal').modal('hide').off('hidden.bs.modal');
        }
        $.doCloseToast();
    },
    initJqboac: function(isstatic) {
        if($("#jqboac_warningmodaldiv").length < 1){
            var statichtml = isstatic ? ' data-backdrop="static" data-keyboard="false"' : '';
            $("body").append('<div id="jqboac_warningmodaldiv"><div class="modal fade" id="jqboac_warningmodal" tabindex="-1" data-backdrop="static" data-keyboard="false"><div class="modal-dialog modal-sm modal-dialog-centered"><div class="modal-content border-0"><div class="modal-header d-none"><button type="button" class="close" data-dismiss="modal" aria-label="Close" id="jqboac_warningmodalClose"><span aria-hidden="true">&times;</span></button></div><div class="modal-body p-0"><div class="toast fade show" style="max-width: 100%" role="alert" aria-live="assertive" aria-atomic="true" id="jqboac_warningtoast"><div class="toast-header"><span id="jqboac_warningtoastIcon"></span>&nbsp;<strong class="mr-auto" id="jqboac_warningtoastTitle"></strong><small></small><button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close" id="jqboac_warningtoastclose"><span aria-hidden="true">&times;</span></button></div><div class="toast-body"><div id="jqboac_warningtoastContent"></div><div class="mt-2 pt-2 border-top text-center" id="jqboac_warningtoastfooter"><button type="button" class="btn btn-success btn-sm mr-1" id="jqboac_warningtoastfooterOk"></button><button type="button" class="btn btn-secondary btn-sm ml-1" id="jqboac_warningtoastfooterClose"></button></div></div></div></div></div></div></div></div>');
        }
    },
    openToast: function(title, content, warning, isstatic, isok, isclose) {
        warning = warning || "warning";
        isok = isok || "";
        isclose = isclose || "";
        if(isstatic == "static"){
            $.initJqboac(true);
        }
        else{
            $.initJqboac(false);
        }
        if(!$("#jqboac_warningmodal").hasClass("show")){
            $("#jqboac_warningtoastTitle").text(title);
            $("#jqboac_warningtoastContent").html(content);
            if(isok == "" && isclose == ""){
                $("#jqboac_warningtoastfooter").addClass("d-none");
            }
            else{
                $("#jqboac_warningtoastfooter").removeClass("d-none");
                if(isok != ""){
                    $("#jqboac_warningtoastfooterOk").text(isok).removeClass("d-none");
                }
                else{
                    $("#jqboac_warningtoastfooterOk").addClass("d-none");
                }
                if(isclose != ""){
                    $("#jqboac_warningtoastfooterClose").text(isclose).removeClass("d-none");
                }
                else{
                    $("#jqboac_warningtoastfooterClose").addClass("d-none");
                }
            }
            if(warning == "success"){
                $("#jqboac_warningtoastIcon").html('<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-check2-circle text-success" viewBox="0 0 16 16"><path d="M2.5 8a5.5 5.5 0 0 1 8.25-4.764.5.5 0 0 0 .5-.866A6.5 6.5 0 1 0 14.5 8a.5.5 0 0 0-1 0 5.5 5.5 0 1 1-11 0z"/><path d="M15.354 3.354a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0l7-7z"/></svg>');
            }
            else if(warning == "confirm"){
                $("#jqboac_warningtoastIcon").html('<svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" class="bi bi-question-circle text-info" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286zm1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94z"/></svg>');
            }
            else{
                $("#jqboac_warningtoastIcon").html('<svg class="bi bi-alert-triangle text-warning" width="32" height="32" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M9.938 4.016a.146.146 0 00-.054.057L3.027 15.74a.176.176 0 00-.002.183c.016.03.037.05.054.06.015.01.034.017.066.017h13.713a.12.12 0 00.066-.017.163.163 0 00.055-.06.176.176 0 00-.003-.183L10.12 4.073a.146.146 0 00-.054-.057.13.13 0 00-.063-.016.13.13 0 00-.064.016zm1.043-.45a1.13 1.13 0 00-1.96 0L2.166 15.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L10.982 3.566z"></path><rect width="2" height="2" x="9.002" y="13" rx="1"></rect><path d="M9.1 7.995a.905.905 0 111.8 0l-.35 3.507a.553.553 0 01-1.1 0L9.1 7.995z"></path></svg>');
            }
            $('#jqboac_warningmodal').modal('show');
        }
    },
    doCloseToast: function() {
        $("#jqboac_warningtoastTitle").text("");
        $("#jqboac_warningtoastContent").html("");
    },
    closeToast: function() {
        $("#jqboac_warningtoastclose, #jqboac_warningtoastfooterClose").unbind().on("click", function(){
            $('#jqboac_warningmodal').modal('hide').off('hidden.bs.modal');
            $.doCloseToast();
        });
        $("#jqboac_warningmodal").unbind().on("click", function(){
            if($("#jqboac_warningmodal").data("backdrop") != "static"){
                $.doCloseToast();
            }
        });
    }
});