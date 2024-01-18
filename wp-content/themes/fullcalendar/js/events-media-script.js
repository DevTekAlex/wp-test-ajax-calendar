jQuery(document).ready(function($) {
    var image_frame;

    $('#events_image_upload').click(function(e) {
        e.preventDefault();

        if ( image_frame ) {
            image_frame.open();
            return;
        }

        image_frame = wp.media({
            title: 'Select or Upload an Image for the Event',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        image_frame.on( 'select', function() {
            var attachment = image_frame.state().get('selection').first().toJSON();

            $('#events_image').val(attachment.url);

            $('#events_image_preview').html('<img src="' + attachment.url + '" alt="Event Image">');
        });

        image_frame.open();
    });
});