<?php
/**
 * Plugin Name: Customize Input Validity Constraints Examples
 * Version: 0.1.0
 * Description: Examples of enforcing HTML5 validity constraints via setting validation.
 * Author: Weston Ruter, XWP
 * Author URI: https://make.xwp.co/
 * Domain Path: /languages
 * Plugin URI: https://github.com/xwp/wp-customize-input-validity-constraints
 *
 * Copyright (c) 2016 XWP (https://make.xwp.co/)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 *
 * @package Customize_Input_Validity_Constraints
 */

namespace Customize_Input_Validity_Constraints;

/**
 * Register control.
 *
 * @param \WP_Customize_Manager $wp_customize Manager.
 */
function customize_register( \WP_Customize_Manager $wp_customize ) {

	$wp_customize->add_section( 'input_validity_constraints', array(
		'title' => 'Input Validity Constraints',
	) );

	$wp_customize->add_setting( 'test_number', array(
		'type' => 'demo',
	) );
	$wp_customize->add_control( 'test_number', array(
		'type' => 'number',
		'section' => 'input_validity_constraints',
		'label' => 'Number input',
		'description' => 'The number must be between 1 and 4, inclusive, with an optional ½ step.',
		'settings' => array(
			'default' => 'test_number',
		),
		'input_attrs' => array(
			'min' => '1',
			'max' => '4',
			'step' => '0.5',
		),
	) );
}
add_action( 'customize_register', __NAMESPACE__ . '\customize_register' );
