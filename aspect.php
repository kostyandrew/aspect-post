<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/*
Plugin Name: Aspect Post
Version:     0.1-alpha
 */
Aspect\Type::set('note')
    ->setArgument('public', true);
