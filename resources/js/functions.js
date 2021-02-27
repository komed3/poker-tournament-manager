jQuery( document ).ready( function( $ ) {
    
    $( document ).on( 'click', '.ptm_pager button:not(:disabled)', function() {
        
        var offset = $( this ).data( 'offset' ),
            limit = $( this ).parent( '.ptm_pager' ).data( 'limit' );
        
        location.href = '?offset=' + offset + '&limit=' + limit;
        
    } );
    
} );
