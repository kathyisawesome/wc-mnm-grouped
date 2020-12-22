( function( $ ) {



  /**
   * Main container object.
   */
  function WC_MNM_Grouped( $wrapper ) {

    var grouped = this;

    this.$wrapper  = $wrapper;
    this.$selector = $wrapper.find( 'ul.products' );
    this.$result   = $wrapper.find( '.wc-grouped-mnm-result' );

    /**
     * Object initialization.
     */
    this.initialize = function() {

      /**
       * Bind event handlers.
       */
      this.bind_event_handlers();

    };


    /**
     * Events.
     */
    this.bind_event_handlers = function() {
      this.$selector.on( 'click', ' .woocommerce-loop-product__link', this.loadAjax );
      this.$selector.on( 'click', ' .add_to_cart_button', this.loadAjax );
    };

    /**
     * Load the selected MNM product.
     */

    this.loadAjax = function(e) {

      e.preventDefault();

      var current_selection = grouped.$selector.data( 'current_selection' );
      var product_id = $(this).data( 'product_id' );
      var security   = grouped.$wrapper.data( 'security' );

      // If currently processing... or clicking on same item, quit now.
      if ( grouped.$wrapper.is( '.processing' ) || product_id === current_selection ) {
        return false;
      } else if ( ! grouped.$wrapper.is( '.processing' ) ) {
        grouped.$wrapper.addClass( 'processing' ).block( {
          message: null,
          overlayCSS: {
            background: '#fff',
            opacity: 0.6
          }
        } );
      }

      grouped.$wrapper.addClass( 'has-selection' ).find( '.product' ).removeClass( 'selected' );

      $(this).closest( '.product' ).addClass( 'selected' );

      grouped.$selector.data('current_selection', product_id);

      $.ajax( {
          url: WC_MNM_GROUPED_PARAMS.wc_ajax_url.toString().replace( '%%endpoint%%', 'get_mix_and_match' ),
          type: 'POST',
          data: { 
            product_id: product_id,
            security: security
          },
          success: function( response ) {
            if ( 'success' === response.result ) {

              grouped.$result.html( response.html ).fadeIn();

              // Switch the URL
              grouped.updateUrl( product_id );

              // Initilize MNM scripts.
              grouped.$result.find( '.mnm_form' ).each( function() {
                $(this).wc_mnm_form();
              } );

            }
            
          },
          complete: function() {
            grouped.$wrapper.removeClass( 'processing' ).unblock();
          }
      } );

    };

    this.updateUrl = function( product_id ) {
      
      let url = window.location.href;

      let regex = /\/mnm\/([0-9]+)?/;
      let found = url.match(regex);

      if ( found ) {
        url = url.replace(regex, '/mnm/' + product_id );
      } else {
         url = url.replace(/\/$/, "") + '/mnm/' + product_id;
      }

      let state = {
          mnm: product_id,
      }
      let title = product_id;

      history.pushState(state, title, url);
    }


    // Launch.
    this.initialize();

  } // End WC_MNM_Grouped.

  /*-----------------------------------------------------------------*/
  /*  Initialization.                                                */
  /*-----------------------------------------------------------------*/

  $( '.wc-grouped-mnm-wrapper' ).each( function(i) {
    $grouped = new WC_MNM_Grouped( $(this) );
  } );

} ) ( jQuery );
