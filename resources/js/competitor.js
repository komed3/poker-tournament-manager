jQuery( document ).ready( function( $ ) {
    
    Highcharts.setOptions( highcharts_options );
    
    var ptm_chart_stack = Highcharts.stockChart( 'ptm_chart_stack', {
        
        series: [ {
            name: 'realtime stack size',
            type: 'areaspline',
            threshold: null,
            yAxis: 0,
            data: $( '#ptm_chart_stack' ).data( 'stack' )
        }, {
            name: 'stack changes',
            type: 'column',
            threshold: 0,
            color: ptm.color.good,
            negativeColor: ptm.color.bad,
            yAxis: 1,
            data: $( '#ptm_chart_stack' ).data( 'change' )
        } ],
        
        yAxis: [ {
            height: '59%'
        }, {
            height: '39%',
            top: '61%'
        } ]
        
    } );
    
} );
