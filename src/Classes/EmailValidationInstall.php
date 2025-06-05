<?php

$table_name = $wpdb->prefix . "cstore_source_links";
$sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    link text NOT NULL,
    target varchar(50) NOT NULL,
    datetime datetime NOT NULL,
    PRIMARY KEY  (id)
) $charset_collate;";
dbDelta($sql);
