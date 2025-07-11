$(function() {
    const $container = $('.secondary-nav-container');
    const menuId = $container.data('menu-id');
    const $menu = $('#' + menuId);
    const $menuUl = $menu.find('ul');
    const $leftArrow = $('.nav-arrow--left');
    const $rightArrow = $('.nav-arrow--right');

    const step = 200;

    function updateArrows() {
        if($menuUl.length <= 0) return;
        var scrollLeft = $menuUl.scrollLeft();
        var maxScroll = $menuUl[0].scrollWidth - $menuUl.outerWidth();

        $leftArrow.toggleClass('hidden', scrollLeft <= 0);
        $rightArrow.toggleClass('hidden', scrollLeft + 1 >= maxScroll);
    }

    $leftArrow.on('click', function (){
        $menuUl.animate({ scrollLeft: '-=' + step }, 'smooth', updateArrows);
    });

    $rightArrow.on('click', function (){
        $menuUl.animate({ scrollLeft: '+=' + step }, 'smooth', updateArrows);
    });

    $menuUl.on('scroll', updateArrows);

    $menuUl.on('wheel', function(event) {
        event = event.originalEvent;
        if (event.ctrlKey) return; // Ignore pinch zoom

        // If horizontal scroll (deltaX) exists, do nothing (allow natural scroll)
        if (Math.abs(event.deltaX) > 0) {
            return;
        }

        // If no deltaX, prevent vertical scrolling and allow natural horizontal scrolling
        if ($(this).is(":hover") && event.deltaY !== 0) {
            event.preventDefault(); // Stop vertical scrolling
            let scrollAmount = event.deltaMode === 1 ? event.deltaY * 30 : event.deltaY *3;
            requestAnimationFrame(() => {
                this.scrollLeft += scrollAmount;
            });
        }

        updateArrows();
    });
    var startX;
    $menuUl.on('touchstart', function(event) {
        startX = event.originalEvent.touches[0].clientX;
    });

    $menuUl.on('touchmove', function(event) {
        var moveX = event.originalEvent.touches[0].clientX;
        $(this).scrollLeft($(this).scrollLeft() - (moveX - startX) * 8);
        startX = moveX;
        updateArrows();
    });

    function adjustNavAlignment() {
        if ($menuUl[0].scrollWidth > $menu.width()) {
            $menuUl.css("justify-content", "flex-start"); // Align left if scrolling needed
        } else {
            $menuUl.css("justify-content", "center"); // Center if all items fit
        }
    }

    $(window).on('resize', () => {
        updateArrows();
        adjustNavAlignment();
    });

    $(window).on('load', () => {
        updateArrows();
        adjustNavAlignment();
    });
});
