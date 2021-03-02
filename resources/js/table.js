jQuery( document ).ready( function( $ ) {
    
    Highcharts.setOptions( highcharts_options );
    
    var ptm_chart_stacks = Highcharts.stockChart( 'ptm_chart_stacks', {
        
        plotOptions: {
            series: {
                step: 'center'
            }
        }
        
    } );
    
    ptm_chart_stacks_seats.forEach( function( seat, idx ) {
        
        ptm_chart_stacks.addSeries( {
            name: seat,
            data: ptm_chart_stacks_data[ idx ]
        } ).redraw();
        
    } );
    
} );
