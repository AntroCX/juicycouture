$(function () {
    var $blockSocLink = $('.b-social-links');

    $blockSocLink.on('click', 'a', function(e){
        e.preventDefault();
        var link = $(this).attr('href');
        $(location).attr('href',link);
    });

});