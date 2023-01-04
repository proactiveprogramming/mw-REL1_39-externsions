var fileobj;
function upload_file(e) {
    e.preventDefault();
    fileobj = e.dataTransfer.files[0];
    ajax_file_upload(fileobj);
}
 
function file_explorer() {       
    document.getElementById('selectfile').click();
    document.getElementById('selectfile').onchange = function() {
        fileobj = document.getElementById('selectfile').files[0];             
        ajax_file_upload(fileobj);
    };
}
 
function ajax_file_upload(file_obj) {    
    var id = document.getElementsByName('bt_issueid')[0].value;  

    if(file_obj != undefined) {
        var form_data = new FormData();                  
        form_data.append('file', file_obj);

        $('.msg').hide();
        $('.progress').show();   
        var percent = 0;     
        $('#progressBar').attr('aria-valuenow', percent).css('width', percent + '%').text(percent + '%');

        $.ajax({
            xhr : function() {
				var xhr = new window.XMLHttpRequest();
				xhr.upload.addEventListener('progress', function(e){
					if(e.lengthComputable){
                        
						console.log('Bytes Loaded : ' + e.loaded);
						console.log('Total Size : ' + e.total);
						console.log('Persen : ' + (e.loaded / e.total));
						
						var percent = Math.round((e.loaded / e.total) * 100);						
						$('#progressBar').attr('aria-valuenow', percent).css('width', percent + '%').text(percent + '%');
					}
				});
				return xhr;
			},
            type: 'POST',
            url: 'extensions/ITaskTracker/Views/uploadibug_.php?id='+id,
            contentType: false,
            processData: false,
            data: form_data,
            success:function(response) {                
                var obj = JSON.parse(response);
                if (obj.success==true) {
                $("#uploaded_").append(obj.link_);                   
                $("#msg1").html('');              
                }else{                        
                $("#msg1").css("display", "block");        
                $("#msg1").html(obj.message); 
                }
                //$('#selectfile').val('');
                $('.progress').hide();
                $('.msg').show();
            }
        });
    }
}

function del_file(url) {    
    var regex = /[?&]([^=#]+)=([^&#]*)/g,
    url_ = url,
    params = {},
    match;
    while(match = regex.exec(url_)) {
        params[match[1]] = match[2];
    }

    var fileId = params.fileid;
    $.ajax({
        type: "POST",
        url: url,
        async: true,
        data: { logDownload: true, 
            file: $(this).attr("name") 
            },
            success:function(response) {
                var obj = JSON.parse(response);
                if (obj.success==true) {
                    $('#file'+fileId).remove();   
                    $("#msg1").html('');                        
                }else{                
                    $("#msg1").html(obj.message); 
                }       
            }
        });
    return false;
}