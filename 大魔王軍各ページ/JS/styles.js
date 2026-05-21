// Initalize page
setup();

// Reset button
$('.reset').click( function() {
    $('.menu__item').removeClass('menu__item--current');
    $('.header__avatar').removeClass('drop');
    $('.reset').blur();
    setup();
});

function setup() {
    $('.menu__item').first().addClass('menu__item--current')
    $('.header__avatar').addClass('drop');
};

// Menu fx on click 
$('.menu__link').click( function() {
    
    // Menu button current selection
    $('.menu__item').removeClass('menu__item--current');
    $(this).parent().addClass('menu__item--current');
    
    // addClass will put back and trigger animation, so we remove it first
    $('.header__avatar').removeClass('drop');
    
    // Trigger spin, wait for animation to finish and remove class.
    $('.header__avatar').addClass('spin').delay(400).queue(function(next){
        $(this).removeClass('spin');
        next();
    });
    
}); 