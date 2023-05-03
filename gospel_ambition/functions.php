<?php

class GO_Sats {

    public function __construct(){
        //refresh stats every day at midnight
        if ( !wp_next_scheduled( 'go_refresh_stats' ) ){
            wp_schedule_event( strtotime( 'midnight' ), 'daily', 'go_refresh_stats' );
        }
        add_action( 'go_refresh_stats', [ $this, 'go_refresh_stats' ] );

        //save stats snapshot every day at 1am
        if ( !wp_next_scheduled( 'go_save_stats_snapshot' ) ){
            wp_schedule_event( strtotime( 'tomorrow 1am' ), 'daily', 'go_save_stats_snapshot' );
        }
        add_action( 'go_save_stats_snapshot', [ $this, 'save_stats_snapshot' ] );
    }

    public function go_refresh_stats(){
        self::get_all_projects( false );
    }

    public static function get_all_projects( $use_cache = true ){
        $dt_stats = dt_cached_api_call( 'https://disciple.tools/wp-json/go/v1/stats', 'GET', [], HOUR_IN_SECONDS, $use_cache );
        $dt_stats = json_decode( $dt_stats, true );

        $p4m_stats = dt_cached_api_call( 'https://pray4movement.org/wp-json/go/v1/stats', 'GET', [], HOUR_IN_SECONDS, $use_cache );
        $p4m_stats = json_decode( $p4m_stats, true );

        $pg_stats = dt_cached_api_call( 'https://prayer.global/wp-json/go/v1/stats?', 'GET', [], HOUR_IN_SECONDS, $use_cache );
        $pg_stats = json_decode( $pg_stats, true );

        $zume_stats = dt_cached_api_call( 'https://zume.training/wp-json/go/v1/stats', 'GET', [], HOUR_IN_SECONDS, $use_cache );
        $zume_stats = json_decode( $zume_stats, true );

        $kt_stats = dt_cached_api_call( 'https://kingdom.training/wp-json/go/v1/stats', 'GET', [], HOUR_IN_SECONDS, $use_cache );
        $kt_stats = json_decode( $kt_stats, true );


        return [
            'disciple_tools' => $dt_stats,
            'pray4movement' => $p4m_stats,
            'prayer_global' => $pg_stats,
            'zume_training' => $zume_stats,
            'kingdom_training' => $kt_stats
        ];
    }

    public function save_stats_snapshot(){
        global $wpdb;

        $projects = self::get_all_projects( true );

        foreach ( $projects as $project_key => $project ){
            $values = '';
            $project_key = esc_sql( $project_key );
            foreach ( $project['stats'] as $stat_key => $value ) {
                if ( is_array( $value['value'] ) ){
                    $value['value'] = maybe_serialize( $value['value'] );
                }
                $v = esc_sql( $value['value'] );
                $stat_key = esc_sql( $stat_key );
                $values .= "( '$project_key', '$stat_key', CURDATE(), UNIX_TIMESTAMP(), '$v'),";
            }
            $values = rtrim( $values, ',' );

            //phpcs:disable
            $test = $wpdb->query( "INSERT INTO {$wpdb->prefix}go_reports 
                (project, stat_key, stat_date, stat_timestamp, stat_value)
                VALUES
                $values
            ");
            //phpcs:enable

        }
    }
}
new GO_Sats();