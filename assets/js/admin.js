/**
 *  WP Show IDs
 * 
 * @version 1.0.0
 * @author Vijay Hardaha
 * @since 1.0.0
 */

/* global ClipboardJS */

jQuery( function ( $ ) {
  var VHClipboard = new ClipboardJS( '.wp-show-id.vh-has-copy' ),
    successTimeout;

  // Copy the id on click.
  VHClipboard.on( 'success', function ( e ) {
    var triggerElement = $( e.trigger ),
      originalText = triggerElement.attr( 'aria-label' ),
      copiedText = triggerElement.attr( 'data-success-text' );

    // Clear the selection and move focus back to the trigger.
    e.clearSelection();
    // Handle ClipboardJS focus bug, see https://github.com/zenorocha/VHClipboard.js/issues/680
    triggerElement.trigger( 'focus' );

    // Show copied as visual feedback.
    clearTimeout( successTimeout );
    triggerElement.attr( 'aria-label', copiedText );

    // Hide copied visual feedback after 1 seconds since last success.
    successTimeout = setTimeout( function () {
      triggerElement.attr( 'aria-label', originalText );
      // Remove the visually hidden textarea so that it isn't perceived by assistive technologies.
      if ( VHClipboard.clipboardAction.fakeElem && VHClipboard.clipboardAction.removeFake ) {
        VHClipboard.clipboardAction.removeFake();
      }
    }, 1000 );
  } );
} );