jQuery( document ).ready( function () {

/* Checking the shortlist boxes updates the text form */
jQuery("#langshortlist input").each((i, elt) => {
    elt.addEventListener('change', event => {
        let checkboxes = jQuery( "#langshortlist input" ).toArray();
        let text = checkboxes.filter(e => e.checked)
            .map(e => e.labels[0].textContent)
            .join();
        jQuery( "input[name='langdelete_w']" )
            .val(text)
            .addClass('ani-ld-text-added');
    });
});
// Remove animation class when done
jQuery( "input[name='langdelete_w']" ).on("animationend", event => {
    if (event.originalEvent.animationName == 'ld-text-added') {
        jQuery( event.target ).removeClass('ani-ld-text-added');
    }
});

/* */
});


