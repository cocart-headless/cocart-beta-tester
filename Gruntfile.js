module.exports = function(grunt) {
	'use strict';

	require( 'load-grunt-tasks' )( grunt );

	// Project configuration.
	grunt.initConfig(
		{
			pkg: grunt.file.readJSON( 'package.json' ),

			// Minify JavaScript
			uglify: {
				options: {
					compress: {
						global_defs: {
							"EO_SCRIPT_DEBUG": false
						},
						dead_code: true
					},
					banner: '/*! <%= pkg.title %> v<%= pkg.version %> <%= grunt.template.today("dddd dS mmmm yyyy HH:MM:ss TT Z") %> */'
				},
				build: {
					files: [{
						expand: true,
						cwd: 'assets/js',
						src: [
							'*.js',
							'!*.min.js'
						],
						dest: 'assets/js',
						ext: '.min.js'
					}]
				}
			},

			// Generate .pot file
			makepot: {
				target: {
					options: {
						cwd: '',
						domainPath: 'languages',                                  // Where to save the POT file.
						exclude: [
							'releases',
							'node_modules',
							'vendor'
						],
						mainFile: '<%= pkg.name %>.php', // Main project file.
						potComments: 'Copyright (c) {year} CoCart Headless, LLC\nThis file is distributed under the same license as the CoCart package.', // The copyright at the beginning of the POT file.
						potFilename: '<%= pkg.name %>.pot', // Name of the POT file.
						potHeaders: {
							'poedit': true,                                       // Includes common Poedit headers.
							'x-poedit-keywordslist': true,                        // Include a list of all possible gettext functions.
							'Report-Msgid-Bugs-To': 'support@cocartapi.com',
							'language-team': 'CoCart Headless, LLC <support@cocartapi.com>',
							'language': 'en_US'
						},
						processPot: function( pot ) {
							var translation,
							excluded_meta = [
								'Plugin Name of the plugin/theme',
								'Plugin URI of the plugin/theme',
								'Description of the plugin/theme',
								'Author of the plugin/theme',
								'Author URI of the plugin/theme'
							];

							for ( translation in pot.translations[''] ) {
								if ( 'undefined' !== typeof pot.translations[''][ translation ].comments.extracted ) {
									if ( excluded_meta.indexOf( pot.translations[''][ translation ].comments.extracted ) >= 0 ) {
										console.log( 'Excluded meta: ' + pot.translations[''][ translation ].comments.extracted );
										delete pot.translations[''][ translation ];
									}
								}
							}

							return pot;
						},
						type: 'wp-plugin',                                        // Type of project.
						updateTimestamp: true,                                    // Whether the POT-Creation-Date should be updated without other changes.
					}
				}
			},

			// Check strings for localization issues
			checktextdomain: {
				options:{
					text_domain: '<%= pkg.name %>', // Project text domain.
					keywords: [
						'__:1,2d',
						'_e:1,2d',
						'_x:1,2c,3d',
						'esc_html__:1,2d',
						'esc_html_e:1,2d',
						'esc_html_x:1,2c,3d',
						'esc_attr__:1,2d',
						'esc_attr_e:1,2d',
						'esc_attr_x:1,2c,3d',
						'_ex:1,2c,3d',
						'_n:1,2,4d',
						'_nx:1,2,4c,5d',
						'_n_noop:1,2,3d',
						'_nx_noop:1,2,3c,4d'
					]
				},
				files: {
					src:  [
						'*.php',
						'**/*.php', // Include all files
						'!node_modules/**', // Exclude node_modules/
						'!vendor/**' // Exclude vendor/
					],
					expand: true
				},
			},

			potomo: {
				dist: {
					options: {
						poDel: false
					},
					files: [{
						expand: true,
						cwd: 'languages',
						src: ['*.po'],
						dest: 'languages',
						ext: '.mo',
						nonull: false
					}]
				}
			},

			// Bump version numbers (replace with version in package.json)
			replace: {
				php: {
					src: [
						'<%= pkg.name %>.php'
					],
					overwrite: true,
					replacements: [
						{
							from: /Description:.*$/m,
							to: "Description: <%= pkg.description %>"
						},
						{
							from: /Requires at least:.*$/m,
							to: "Requires at least: <%= pkg.requires %>"
						},
						{
							from: /Requires PHP:.*$/m,
							to: "Requires PHP: <%= pkg.requires_php %>"
						},
						{
							from: /Tested up to:.*$/m,
							to: 'Tested up to: <%= pkg.tested_up_to %>'
						},
						{
							from: /Version:.*$/m,
							to: "Version:     <%= pkg.version %>"
						},
						{
							from: /public static \$version = \'.*.'/m,
							to: "public static $version = '<%= pkg.version %>'"
						},
						{
							from: /public static \$required_wp = \'.*.'/m,
							to: "public static $required_wp = '<%= pkg.requires %>'"
						},
						{
							from: /COCART_BETA_TESTER_VERSION\', \'.*.'/m,
							to: "\COCART_BETA_TESTER_VERSION', '<%= pkg.version %>'"
						}
					]
				},
			},

			// Copies the plugin to create deployable plugin.
			copy: {
				build: {
					files: [
						{
							expand: true,
							src: [
								'**',
								'!.*',
								'!**/*.{gif,jpg,jpeg,json,log,lock,md,png,scss,sh,txt,xml,zip}',
								'!.*/**',
								'!.DS_Store',
								'!.htaccess',
								'assets/images/**',
								'!assets/scss/**',
								'!assets/**/*.scss',
								'!bin/**',
								'!<%= pkg.name %>-git/**',
								'!<%= pkg.name %>-svn/**',
								'!node_modules/**',
								'!releases/**',
								'!tests/**',
								'!vendor/**',
								'!unit-tests/**',
								'!Gruntfile.js',
								'readme.txt'
							],
							dest: 'build/',
							dot: true
						}
					]
				}
			},

			// Compresses the deployable plugin folder.
			compress: {
				zip: {
					options: {
						archive: './releases/<%= pkg.name %>-v<%= pkg.version %>.zip',
						mode: 'zip'
					},
					files: [
						{
							expand: true,
							cwd: './build/',
							src: '**',
							dest: '<%= pkg.name %>'
						}
					]
				}
			},

			// Deletes the deployable plugin folder once zipped up.
			clean: {
				build: [ 'build/' ]
			}
		}
	);

	// Set the default grunt command to run test cases.
	grunt.registerTask( 'default', [ 'test' ] );

	// Checks for errors.
	grunt.registerTask( 'test', [ 'checktextdomain' ] );

	// Minify JS and runs i18n tasks.
	grunt.registerTask( 'build', [ 'uglify', 'update-pot' ] );

	// Update version of plugin.
	grunt.registerTask( 'version', [ 'replace:php' ] );

	/**
	 * Run i18n related tasks.
	 *
	 * This includes extracting translatable strings, updating the master pot file.
	 * If this is part of a deploy process, it should come before zipping everything up.
	 */
	grunt.registerTask( 'update-pot', [ 'checktextdomain', 'makepot' ] );

	/**
	 * Creates a deployable plugin zipped up ready to upload
	 * and install on a WordPress installation.
	 */
	grunt.registerTask( 'zip', [ 'copy:build', 'compress', 'clean:build' ] );
};
