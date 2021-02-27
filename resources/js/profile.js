jQuery( document ).ready( function( $ ) {
    
    Highcharts.setOptions( highcharts_options );
    
    var ptm_chart_cash = Highcharts.stockChart( 'ptm_chart_cash', {
        
        series: [ {
            name: 'realtime cash',
            type: 'areaspline',
            threshold: 0,
            color: ptm.color.good,
            negativeColor: ptm.color.bad,
            data: $( '#ptm_chart_cash' ).data( 'cash' )
        } ]
        
    } );
    
} );
