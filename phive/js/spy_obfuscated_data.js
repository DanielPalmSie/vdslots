$(document).ready(function() {
    $(".spy-obfuscated").click(function() {
        var user_id = $(this).data('user');
        var field = $(this).data('target');

        $target_element = $("[data-key='"+field+"']");

        $.ajax({
            method: "POST",
            url: "/admin2/userprofile/personal-details/show/",
            data: {
                user_id: user_id,
                field: field
            },
        })
        .done(function(data) {
            /* data: { success: true/false, key: 'the_key', value: 'the real value' } */
            if (data.success === true) {
                if (data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                $target_element.text(data.value);

            } else {
                alert('There was an internal error.');
            }
        });
    });
});
