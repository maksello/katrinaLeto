$(document).ready(function() {

    $(window).scroll(function() {
        // Высота проявления кнопки
        if ($(this).scrollTop() > 100) {
            $('#go-to-top').fadeIn();
        } else {
            $('#go-to-top').fadeOut();
        }
    });

    $('#go-to-top').click(function() {
        $('body,html').animate({
            scrollTop: 0
            // Скорость подъема
        }, 2000);
        return false;
    });

});