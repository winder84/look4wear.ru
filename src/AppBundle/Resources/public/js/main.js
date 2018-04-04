var bLazy = new Blazy({
    breakpoints: [{
        width: 420 // Max-width
        , src: 'data-src-small'
    }]
    , success: function(element){
        setTimeout(function(){
            // We want to remove the loader gif now.
            // First we find the parent container
            // then we remove the "loading" class which holds the loader image
            var parent = element.parentNode;
            parent.className = parent.className.replace(/\bloading\b/,'');
        }, 200);
    }
});

$('.productLink').hover(function () {
    $(this).prop('href', '/goods/buy/' + $(this).data('productAlias'));
}, function () {
    $(this).prop('href', '#');
});

$(function() {
    $('.menu__icon').on('click', function() {
        $(this).closest('.mobileBlock').toggleClass('menu_state_open');
    });
});