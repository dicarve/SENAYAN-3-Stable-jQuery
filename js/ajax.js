/**
 * Arie Nugraha 2009
 * Simbio AJAX related functions
 *
 * Require : jQuery library
 */


/**
 * Function to Set AJAX content
 *
 * @param       string      strSelector : string of CSS and XPATH selector
 * @param       string      strURL : URL of AJAX request
 * @return      void
 */
jQuery.fn.simbioAJAX = function(strURL, params)
{
    options = {
        method: 'get',
        insertMode: 'replace',
        addData: {},
        returnType: 'html',
        loadingMessage: 'LOADING CONTENT... PLEASE WAIT'
    };
    jQuery.extend(options, params);

    // callbacks set
    var loader = $(".loader");
    var loaderContent = loader.html();
    loader.addClass('loadingImage');
    loader.html(options.loadingMessage);
    loader.ajaxSuccess(function(){ $(this).html(loaderContent).removeClass('loadingImage'); });
    loader.ajaxError(function(request, settings){ $(this).append("<div class=\"error\">Error requesting page : " + settings.url + "</div>").removeClass('loadingImage'); });

    // send AJAX request
    var ajaxResponse = $.ajax({
        type : options.method, url : strURL,
        data : options.addData, async: false }).responseText;

    // fading out current element
    $(this).hide();
    // add to elements
    if (options.insertMode == 'before') {
        $(this).prepend(ajaxResponse);
    } else if (options.insertMode == 'after') {
        $(this).append(ajaxResponse);
    } else { $(this).html(ajaxResponse).fadeIn('normal'); }

    // re-register events
    registerSimbioTable();
    registerAJAXform();

    return jQuery(this);
}

