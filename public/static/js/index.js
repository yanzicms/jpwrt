/**
 * Created by A.J on 2021/4/10.
 */
$(document).ready(function(){
    $.post($("#events").text(), {},
        function(data){
            if(data.result == "ok"){
                Twoway.set('events', data.events, '#eventsnews');
            }
        });
    $("#dismiss").on("click", function(){
        $.post($(this).data("url"), {});
        $("#dashboardguide").addClass("d-none");
    });
});
Twoway.bind({
    id: '#eventsnews',
    data: {
        events: []
    }
});
