<?php
// Check for base table existence
if( $wpdb->get_var("SHOW TABLES LIKE '".$wpdb->prefix."oai'") !=  $wpdb->prefix.'oai' ) {
    
	$sql = 'CREATE TABLE `'.$wpdb->prefix.'oai` (
	  `ID` int(11) NOT NULL,
	  `title` text NOT NULL,
	  `partner_name` varchar(125) DEFAULT NULL,
	  `is_ever_publicly_published` tinyint(1) DEFAULT \'0\',
	  `is_publicly_published` tinyint(1) UNSIGNED DEFAULT \'0\',
	  `is_deleted` tinyint(1) UNSIGNED DEFAULT \'0\',
	  `created_date` datetime NOT NULL,
	  `published_date` datetime DEFAULT NULL,
	  `modified_date` datetime DEFAULT NULL,
	  `modified_date_entered` datetime DEFAULT NULL,
	  `deleted_date` datetime DEFAULT NULL,
	  `post_type` varchar(20) NOT NULL,
	  `permalink` varchar(255) NOT NULL,
	  `post_excerpt` text NOT NULL
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
	dbDelta( $sql );

	$sql = 'CREATE TABLE `'.$wpdb->prefix.'oai_taxonomy` (
	  `tax_id` int(11) NOT NULL,
	  `taxonomy` varchar(125) NOT NULL
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
	dbDelta( $sql );

	$sql = 'CREATE TABLE `'.$wpdb->prefix.'oai_terms` (
	  `term_id` int(11) NOT NULL,
	  `tax_id` int(11) NOT NULL,
	  `term` varchar(180) NOT NULL
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
	dbDelta( $sql );

	$sql = 'CREATE TABLE `'.$wpdb->prefix.'oai_term_relationships` (
	  `term_id` int(11) NOT NULL,
	  `oai_id` int(11) NOT NULL
	) ENGINE=InnoDB DEFAULT CHARSET=utf8;';
	dbDelta( $sql );

	$sql = 'ALTER TABLE `'.$wpdb->prefix.'oai`
	  ADD PRIMARY KEY (`ID`),
	  ADD KEY `post_type` (`post_type`);';
	$wpdb->query( $sql );

	$sql = 'ALTER TABLE `'.$wpdb->prefix.'oai_taxonomy`
	  ADD PRIMARY KEY (`tax_id`);';
	$wpdb->query( $sql );

	$sql = 'ALTER TABLE `'.$wpdb->prefix.'oai_terms`
	  ADD PRIMARY KEY (`term_id`),
	  ADD UNIQUE KEY `tax_id` (`tax_id`,`term`);';
	$wpdb->query( $sql );

	$sql = 'ALTER TABLE `'.$wpdb->prefix.'oai_term_relationships`
	  ADD PRIMARY KEY (`term_id`,`oai_id`),
	  ADD KEY `oai_id` (`oai_id`),
	  ADD KEY `term_id` (`term_id`);';
	$wpdb->query( $sql );

	$sql = 'ALTER TABLE `'.$wpdb->prefix.'oai_taxonomy`
	  MODIFY `tax_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;';
	$wpdb->query( $sql );

	$sql = 'ALTER TABLE `'.$wpdb->prefix.'oai_terms`
	  MODIFY `term_id` int(11) NOT NULL AUTO_INCREMENT;';
	$wpdb->query( $sql );

	$sql = 'INSERT INTO `'.$wpdb->prefix.'oai_taxonomy` (`tax_id`, `taxonomy`) VALUES
	(1, \'sector\'),
	(3, \'post_competence\'),
	(4, \'post_tag\');';
	$wpdb->query( $sql );
}