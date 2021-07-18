function LGLIB_setQstatus(q_id, newstatus)
{
    var dataS = {
		"action": "setQstatus",
        "q_id": q_id,
        "newstatus": newstatus,
    };
    data = $.param(dataS);
    $.ajax({
        type: "POST",
        dataType: "json",
        url: site_admin_url + "/plugins/lglib/ajax.php",
        data: data,
        success: function(result) {
            console.log(result);
            try {
                if (result.statusMessage != '') {
                    LGLib.notify(result.statusMessage, 'success');
                }
            } catch(err) {
            }
        }
    });
    return false;
}

