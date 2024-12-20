AOS.init();

$('#picker').datepicker({
    "setDate": new Date(),
    "autoclose": true,
    format:'yyyy/mm/dd',
}).datepicker("setDate", 'now');

$(".sidebar ul li").on('click', function () {
    $(".sidebar ul li.active").removeClass('active');
    $(this).addClass('active');
});

$('.open-btn').on('click', function () {
    $('.sidebar').addClass('active');

});


$('.close-btn').on('click', function () {
    $('.sidebar').removeClass('active');

})


