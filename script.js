jQuery( document ).ready( function () {

/* Hovering a language in the shortlist highlights it in the long list */
jQuery( "#langshortlist" ).children().each((i, elt) => {
    elt.addEventListener('mouseover', event => {
        let lang = elt.textContent;
        jQuery( elt ).addClass('active');
        jQuery( `#langlonglist li[val=${lang}]` ).addClass('active')
            .parent().closest('li').addClass('active');
    });
    elt.addEventListener('mouseout', event => {
        let lang = elt.textContent;
        jQuery( elt ).removeClass('active');
        jQuery( `#langlonglist li[val=${lang}]` ).removeClass('active')
            .parent().closest('li').removeClass('active');
    });
});

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

/* Changing the text in the form checks the right checkboxes
 * (and animates them) */
jQuery( "input[name='langdelete_w']" ).on('input', event => {
    let t = jQuery( event.target ).val();
    let langs = jQuery( "#langshortlist input[type='checkbox']" )
        .map((i, elt) => elt.labels[0].textContent )
        .toArray();
    let to_check = t.split(',');

	for (let lang of langs) {
        let $e = jQuery( `input#shortlang-${lang}` );
        let will_check = to_check.indexOf(lang) != -1;
        if ($e.prop("checked") ^ will_check) {
            $e
                .prop("checked", will_check)
                .addClass('ani-ld-text-added');
        }
	}
});
// Remove animation class when done
jQuery( "#langshortlist input[type='checkbox']" ).on("animationend", event => {
    if (event.originalEvent.animationName == 'ld-text-added') {
        jQuery( event.target ).removeClass('ani-ld-text-added');
    }
});
/* */
});


