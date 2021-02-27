var ptm = {
    color: {
        good: '#448800',
        bad: '#bb3300'
    }
};

var highcharts_options = {
    
    credits: { enabled: false },
    rangeSelector: { inputEnabled: false },
    title: { text: null }
    
};

jQuery( document ).ready( function( $ ) {
    
    $( document ).on( 'click', '.ptm_pager button:not(:disabled)', function() {
        
        var offset = $( this ).data( 'offset' ),
            limit = $( this ).parent( '.ptm_pager' ).data( 'limit' );
        
        location.href = '?offset=' + offset + '&limit=' + limit;
        
    } );
    
} );
