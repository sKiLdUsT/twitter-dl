// Taken from http://stackoverflow.com/a/16245768
// Slightly modified to fit the needs
function b64toBlob(b64Data, contentType, sliceSize) {
    contentType = contentType || '';
    sliceSize = sliceSize || 512;

    var byteCharacters = atob(b64Data);
    var byteArrays = [];

    for (var offset = 0; offset < byteCharacters.length; offset += sliceSize) {
        var slice = byteCharacters.slice(offset, offset + sliceSize);

        var byteNumbers = new Array(slice.length);
        for (var i = 0; i < slice.length; i++) {
            byteNumbers[i] = slice.charCodeAt(i);
        }

        var byteArray = new Uint8Array(byteNumbers);

        byteArrays.push(byteArray);
    }

    return new Blob(byteArrays, {type: contentType});
}

$(function(){
    $('div#loader').fadeOut(500);
    $('#fullpage').fullpage({scrollBar:false,paddingTop:'0',paddingBottom: '0'});
    $('button#submit').on('click', function (e) {
        e.preventDefault();
        var value = $('input#url').val();
        var regex = /http(?:s)?:\/\/twitter\.com\/(?:.*)\/status(?:es)?\/(.*)$/g;
        if(value == '' || !value.match(regex) ){
            Materialize.toast('Input a valid URL!', 3000)
        } else {
            var id = regex.exec(value)[1];
            $('div#main  div#content').fadeOut(300, function(){
                $('div#main  div#preloader').fadeIn();
                $.get({
                    url: '/get?id='+id,
                    dataType: 'json',
                    success: function(response){
                        if(response.result === true){
                            $('div#main  div#preloader').fadeOut(300, function(){
                                switch(response.type){
                                    case 'gif':
                                        var blob = b64toBlob(response.data);
                                        var url = URL.createObjectURL(blob);
                                        $('div#complete > div#c_content').html('<img src="'+url+'" class="responsive-img">');
                                        $('div#complete > a').attr('href', url);
                                        break;
                                    case 'video':
                                        $('div#complete > div#c_content').html('<div class="video-container"><iframe width="853" height="480" src="https://twitter.com/i/videos/'+id+'?embed_source=facebook" frameborder="0" allowfullscreen></div>');
                                        $('div#complete > a').attr('href', response.url);
                                        break;
                                }
                                $('div#main  div#complete').fadeIn();
                            })
                        } else {
                            $('div#main  div#preloader').fadeOut(300, function(){
                                $('#error_msg').text(response.error);
                                $('div#main  div#content').fadeIn();
                            })
                        }
                    },
                    error: function(response){
                        $('div#main  div#preloader').fadeOut(300, function(){
                            $('#error_msg').text(response.error);
                            $('div#main  div#content').fadeIn();
                        })
                    }
                });
            });
        }
    })
});