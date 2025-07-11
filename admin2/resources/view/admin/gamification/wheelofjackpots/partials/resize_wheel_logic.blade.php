<script>
    // Code taken from wheel.js in phive
    function resizeWheel(fs){

        if(fs == true){
            // Check if mobile, if mobile get the size of the whole screen
            if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
                winHeight = window.screen.height;
                winWidth = window.screen.width;
            } else {
                winHeight = $(window).height();
                winWidth = $(window).width();
            }
        } else {
            // -20 for the box padding
            // -30 for page padding
            // -50 for side box
            winHeight = $(window).height() - 20;
            winWidth = $(window).width() - 20 - 30 - 50  ;
        }


        // Check if the height or width is greater than the aspect ratio
        if( (winHeight/winWidth) <  compWheelRatio){
            scale = winHeight / compWheelHeight;
        } else {
            scale = winWidth / compWheelWidth;     //384    1902
        }

        if(scale > 1) {
            scale = 1;
        }

        // Set the transform position at the top left corner in order to scale from there
        $('#wheelContainer').css('transform-origin','left top');
        // Scale the container
        $('#wheelContainer').css('transform', 'scale(' + scale + ')');
        // Force the height on the external container, otherwise it will not scale and a huge white space is left on the bottom
        $('#wheelExtContainer').css('height', (901*scale + 20)+'px') ;
        var center_left_px = ($('#wheelExtContainer').width() - $('#wheelContainer').width() * scale+20)/2 ;
        $('#wheelExtContainer').css('position', 'relative');
        $('#wheelContainer').css('position', 'absolute');
        $('#wheelContainer').css('left', center_left_px+'px');
    }

    $(window).resize( function(){
        resizeWheel();
    });

    $(function() {
        // get the height, width and aspect ratio of the wheel
        // container at the begining of the loading of page
        compWheelHeight = $('#wheelContainer').height();
        compWheelWidth = $('#wheelContainer').width();
        compWheelRatio = compWheelHeight / compWheelWidth;

        resizeWheel();
    });

</script>