<?php
/**
 * Plugin Name: Customize Input Validity Constraints
 * Version: 0.1.0
 * Description: Enforce HTML5 validity constraints via setting validation by looking at associated controls and their type and input_attrs. Feature plugin for <a href="https://core.trac.wordpress.org/ticket/38845">#38845</a>.
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
 * Enforce input validity constraints via setting validate_callbacks.
 *
 * @param \WP_Customize_Manager $wp_customize Manager.
 */
function add_validity_constraint_callbacks( \WP_Customize_Manager $wp_customize ) {
	foreach ( $wp_customize->settings() as $setting ) {
		$validate_callback = __NAMESPACE__ . '\validate_input_constraints';
		if ( ! has_filter( "customize_validate_{$setting->id}", $validate_callback ) ) {
			add_filter( "customize_validate_{$setting->id}", $validate_callback, 10, 3 );
		}
	}
}
add_action( 'customize_register', __NAMESPACE__ . '\add_validity_constraint_callbacks', 1000 );
add_action( 'customize_save_validation_before', __NAMESPACE__ . '\add_validity_constraint_callbacks' );

/**
 * Validate date.
 *
 * @param array $args Date params.
 * @return bool Valid.
 */
function validate_date( $args ) {
	if ( ! isset( $args['year'] ) || ! isset( $args['month'] ) ) {
		return false;
	}
	if ( ! isset( $args['year'] ) ) {
		$args['day'] = 1;
	}
	return checkdate( $args['month'], $args['day'], $args['year'] );
}

/**
 * Validate time.
 *
 * @param array $args Time params.
 * @return bool Valid.
 */
function validate_time( $args ) {
	if ( ! isset( $args['hours'] ) || ! isset( $args['minutes'] ) ) {
		return false;
	}
	if ( $args['hours'] < 0 || $args['hours'] > 23 ) {
		return false;
	}
	if ( $args['minutes'] < 0 || $args['minutes'] > 59 ) {
		return false;
	}
	if ( isset( $matches['seconds'] ) && ( $matches['seconds'] < 0 || $matches['seconds'] > 59 ) ) {
		return false;
	}
	return true;
}

/**
 * Validate input constraints.
 *
 * @param \WP_Error             $validity Validity.
 * @param mixed                 $value    Value.
 * @param \WP_Customize_Setting $setting  Setting.
 * @return \WP_Error Validity.
 */
function validate_input_constraints( \WP_Error $validity, $value, \WP_Customize_Setting $setting ) {

	// Short-circuit if not scalar.
	if ( ! is_scalar( $value ) ) {
		return $validity;
	}

	$string_value = strval( $value );

	// @todo The WP_Customize_Setting itself should have a validity_constraints param.
	$input_types = array();
	$input_attrs = array();
	foreach ( $setting->manager->controls() as $control ) {
		if ( in_array( $setting, $control->settings, true ) ) {
			$input_types[] = $control->type;
			$input_attrs = array_merge( $input_attrs, $control->input_attrs );
		}
	}

	// Validate required.
	if ( '' === $string_value ) {
		if ( ! empty( $input_attrs['required'] ) ) {
			$validity->add( 'valueMissing', __( 'Missing value.', 'customize-input-validity-constraints' ) );
		}
		return $validity;
	}

	// Validate number.
	$numerical_value = null;
	if ( in_array( 'number', $input_types, true ) ) {
		if ( ! is_numeric( $value ) ) {
			$validity->add( 'typeMismatch', __( 'Not a number.', 'customize-input-validity-constraints' ) );
		} elseif ( ! empty( $input_attrs['min'] ) && $value < $input_attrs['min'] ) {
			$validity->add( 'rangeUnderflow', __( 'Number too small.', 'customize-input-validity-constraints' ) );
		} elseif ( ! empty( $input_attrs['max'] ) && $value > $input_attrs['max'] ) {
			$validity->add( 'rangeOverflow', __( 'Number too large.', 'customize-input-validity-constraints' ) );
		} else {
			$numerical_value = doubleval( $value );
		}
	}

	// Validate email.
	if ( in_array( 'email', $input_types, true ) ) {
		if ( ! \is_email( $string_value ) ) {
			$validity->add( 'typeMismatch', __( 'Not an email.', 'customize-input-validity-constraints' ) );
		}
	}

	// Validate URL.
	if ( in_array( 'url', $input_types, true ) ) {
		if ( ! \filter_var( $string_value, \FILTER_VALIDATE_URL ) ) {
			$validity->add( 'typeMismatch', __( 'Not a URL.', 'customize-input-validity-constraints' ) );
		}
	}

	// Validate telephone number.
	if ( in_array( 'tel', $input_types, true ) ) {
		if ( ! preg_match( '/\d/', $string_value ) ) {
			$validity->add( 'typeMismatch', __( 'Does not look like a phone number.', 'customize-input-validity-constraints' ) );
		}
	}

	// Validate date/time.
	$date_pattern = '(?P<year>\d\d\d\d)-(?P<month>\d\d)-(?P<day>\d\d)';
	$time_pattern = '(?P<hours>\d\d):(?P<minutes>\d\d)(:(?P<seconds>\d\d)(\.\d+)?)?';
	if ( in_array( 'date', $input_types, true ) ) {
		if ( ! preg_match( '/^' . $date_pattern . '$/', $string_value, $matches ) || ! validate_date( $matches ) ) {
			$validity->add( 'typeMismatch', __( 'Not a valid date.', 'customize-input-validity-constraints' ) );
		} else {
			$numerical_value = strtotime( $string_value );
		}
	}
	if ( in_array( 'month', $input_types, true ) ) {
		if ( ! preg_match( '/^(?P<year>\d\d\d\d)-(?P<month>\d\d)$/', $string_value, $matches ) || ! validate_date( $matches ) ) {
			$validity->add( 'typeMismatch', __( 'Not a valid month.', 'customize-input-validity-constraints' ) );
		} else {
			$numerical_value = strtotime( $string_value . '-01' );
		}
	}
	if ( in_array( 'week', $input_types, true ) ) {
		if ( ! preg_match( '/^\d\d\d\d-W(?P<week>\d+)$/', $string_value, $matches ) || $matches['week'] <= 0 || $matches['week'] > 52 ) {
			$validity->add( 'typeMismatch', __( 'Not a valid week.', 'customize-input-validity-constraints' ) );
		}
	}
	if ( in_array( 'time', $input_types, true ) ) {
		if ( ! preg_match( '/^' . $time_pattern . '$/', $string_value, $matches ) || validate_time( $matches ) ) {
			$validity->add( 'typeMismatch', __( 'Not a valid time.', 'customize-input-validity-constraints' ) );
		} else {
			$numerical_value = strtotime( '1970-01-01T' . $string_value );
		}
	}
	if ( in_array( 'datetime-local', $input_types, true ) ) {
		if ( ! preg_match( '/^' . $date_pattern . '[T ]' . $time_pattern . '$/', $string_value, $matches ) || ! validate_date( $matches ) || ! validate_time( $matches ) ) {
			$validity->add( 'typeMismatch', __( 'Not a valid date!', 'customize-input-validity-constraints' ) );
		} else {
			$numerical_value = strtotime( $string_value );
		}
	}

	// Validate step.
	if ( isset( $numerical_value ) && ! empty( $input_attrs['step'] ) ) {
		$step = doubleval( $input_attrs['step'] );
		$epsilon = 0.0000001;
		if ( fmod( $numerical_value, $step ) > $epsilon ) {
			$validity->add( 'stepMismatch', __( 'Step mismatch.', 'customize-input-validity-constraints' ) );
		}
	}

	// Validate color.
	if ( in_array( 'color', $input_types, true ) ) {
		if ( ! preg_match( '/^#\d\d\d\d\d\d$/', $string_value ) ) {
			$validity->add( 'typeMismatch', __( 'Not a color.', 'customize-input-validity-constraints' ) );
		}
	}

	// Validate maxlength.
	if ( ! empty( $input_attrs['maxlength'] ) ) {
		if ( strlen( $string_value ) > $input_attrs['maxlength'] ) {
			$validity->add( 'tooLong', __( 'Value too long.', 'customize-input-validity-constraints' ) );
		}
	}

	// Validate minlength.
	if ( ! empty( $input_attrs['minlength'] ) ) {
		if ( strlen( $string_value ) < $input_attrs['minlength'] ) {
			$validity->add( 'tooShort', __( 'Value too short.', 'customize-input-validity-constraints' ) );
		}
	}

	// Validate pattern.
	if ( ! empty( $input_attrs['pattern'] ) ) {
		if ( ! preg_match( '/^(' . $input_attrs['pattern'] . ')$/', $string_value ) ) {
			$validity->add( 'patternMismatch', __( 'Pattern mismatch.', 'customize-input-validity-constraints' ) );
		}
	}

	return $validity;
}

/**
 * Enqueue scripts for controls.
 */
function customize_controls_enqueue_scripts() {
	$handle = 'customize-input-validation-constraints';
	$src = plugin_dir_url( __FILE__ ) . 'customize-input-validation-constraints.js';
	$deps = array( 'customize-controls' );
	wp_enqueue_script( $handle, $src, $deps );

	wp_add_inline_script( $handle, 'CustomizeInputValidityConstraints.init( wp.customize );' );
}
add_action( 'customize_controls_enqueue_scripts', __NAMESPACE__ . '\customize_controls_enqueue_scripts' );
