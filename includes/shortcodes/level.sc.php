<?php
    
    function ptm_sc_level() {
        
        global $wpdb;
        
        if( !isset( $_GET['tm'] ) )
            return ptm_cs_level_tournaments();
        
        $tm = $wpdb->get_row( '
            SELECT  *
            FROM    ' . $wpdb->prefix . 'tournament
            WHERE   tm_id = ' . $_GET['tm']
        );
        
        $levels = [];
        $nlevel = 1;
        
        foreach( $wpdb->get_results( '
            SELECT      *
            FROM        ' . $wpdb->prefix . 'level
            WHERE       l_tournament = ' . $tm->tm_id . '
            ORDER BY    l_level ASC
        ' ) as $level ) {
            
            $levels[] = '<tr>
                <td><input type="number" name="level" value="' . $level->l_level . '" /></td>
                <td><input type="number" name="sb" value="' . $level->l_sb . '" /></td>
                <td><input type="number" name="bb" value="' . $level->l_bb . '" /></td>
                <td><input type="number" name="ante" value="' . $level->l_ante . '" /></td>
            </tr>';
            
            $nlevel++;
            
        }
        
        return _ptm( '
            <div class="ptm_level_header ptm_header">
                ' . _ptm_link( 'level', __( 'back', 'ptm' ), [], 'ptm_button ptm_hlink' ) . '
                <h1>' . ucfirst( __( 'levels for ', 'ptm' ) ) . _ptm_link( 'tournament', $tm->tm_name, [ 'id' => $tm->tm_id ] ) . '</h1>
            </div>
            <div class="ptm_level_blind_structure">
                <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
                    <table class="ptm_list">
                        <thead>
                            <tr>
                                <th>' . __( 'level', 'ptm' ) . '</th>
                                <th>' . __( 'Small Blind (SB)', 'ptm' ) . '</th>
                                <th>' . __( 'Big Blind (BB)', 'ptm' ) . '</th>
                                <th>' . __( 'Ante', 'ptm' ) . '</th>
                            </tr>
                        </thead>
                        <tbody>' . implode( '', $levels ) . '</tbody>
                    </table>
                    <button type="submit" name="update" value="1">' . __( 'update blind structure', 'ptm' ) . '</button>
                </form>
            </div>
            <div class="ptm_level_new">
                <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
                    <table class="ptm_list">
                        <tbody>
                            <tr>
                                <td><input type="number" name="level" value="' . $nlevel . '" readonly /></td>
                                <td><input type="number" name="sb" value="" /></td>
                                <td><input type="number" name="bb" value="" /></td>
                                <td><input type="number" name="ante" value="" /></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="submit" name="add" value="1">' . __( 'add new level with blind structure', 'ptm' ) . '</button>
                </form>
            </div>
        ', 'ptm_level_grid ptm_page' );
        
    }
    
    function ptm_cs_level_tournaments() {
        
        global $wpdb;
        
        $offset = !isset( $_GET['offset'] ) || !is_numeric( $_GET['offset'] ) ? 0 : $_GET['offset'];
        $limit = !isset( $_GET['limit'] ) || !is_numeric( $_GET['limit'] ) ? 25 : $_GET['limit'];
        
        $max = $wpdb->get_row( '
            SELECT  COUNT( tm_id ) AS cnt
            FROM    ' . $wpdb->prefix . 'tournament
        ' )->cnt;
        
        $pager = _ptm_pager( $offset, $limit, $max );
        
        $list = [];
        $i = 0;
        
        foreach( $wpdb->get_results( '
            SELECT      *
            FROM        ' . $wpdb->prefix . 'tournament
            ORDER BY    tm_date DESC
            LIMIT       ' . $offset . ', ' . $limit
        ) as $tm ) {
            
            $levels = $wpdb->get_row( '
                SELECT  COUNT( l_level ) AS cnt
                FROM    ' . $wpdb->prefix . 'level
                WHERE   l_tournament = ' . $tm->tm_id
            )->cnt;
            
            $list[] = '<tr>
                <td>' . _ptm_link( 'level', $tm->tm_name, [ 'tm' => $tm->tm_id ] ) . '</td>
                <td>' . _ptm_date( $tm->tm_date ) . '</td>
                <td>' . ( $levels == 0 ? '–' : $tm->tm_level . __( ' of ', 'ptm' ) . $levels ) . '</td>
            </tr>';
            
        }
        
        return _ptm( '
            <div class="ptm_level_list_header ptm_header">
                <h1>' . ucfirst( __( 'tournament levels and Blind structure', 'ptm' ) ) . '</h1>
            </div>
            ' . $pager . '
            <div class="ptm_level_list">
                <table class="ptm_list">
                    <thead>
                        <tr>
                            <th>' . __( 'tournament', 'ptm' ) . '</th>
                            <th>' . __( 'date', 'ptm' ) . '</th>
                            <th>' . __( 'level', 'ptm' ) . '</th>
                        </tr>
                    </thead>
                    <tbody>' . implode( '', $list ) . '</tbody>
                </table>
            </div>
            ' . $pager . '
        ', 'ptm_level_list_grid ptm_page' );
        
    }
    
    add_shortcode( 'ptm_level', 'ptm_sc_level' );
    
?>
