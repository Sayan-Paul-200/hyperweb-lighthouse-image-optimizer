<?php
/**
 * Cross-platform PHP syntax checker for project PHP files.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

$root = dirname( __DIR__ );

$excluded_directories = array(
	'.git',
	'.phpstan',
	'build',
	'coverage',
	'dist',
	'node_modules',
	'release',
	'vendor',
);

$files = new RecursiveIteratorIterator(
	new RecursiveCallbackFilterIterator(
		new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ),
		static function ( SplFileInfo $current ) use ( $excluded_directories ) {
			if ( ! $current->isDir() ) {
				return true;
			}

			return ! in_array( $current->getFilename(), $excluded_directories, true );
		}
	)
);

$php_files = array();

foreach ( $files as $file ) {
	if ( ! $file instanceof SplFileInfo || 'php' !== strtolower( $file->getExtension() ) ) {
		continue;
	}

	$php_files[] = $file->getPathname();
}

sort( $php_files );

$failures = array();

foreach ( $php_files as $php_file ) {
	$command  = escapeshellarg( PHP_BINARY ) . ' -l ' . escapeshellarg( $php_file );
	$output   = array();
	$exitCode = 0;

	exec( $command, $output, $exitCode );

	foreach ( $output as $line ) {
		echo $line . PHP_EOL;
	}

	if ( 0 !== $exitCode ) {
		$failures[] = $php_file;
	}
}

if ( array() !== $failures ) {
	fwrite( STDERR, 'PHP syntax check failed for ' . count( $failures ) . ' file(s).' . PHP_EOL );
	exit( 1 );
}

echo 'PHP syntax check passed for ' . count( $php_files ) . ' file(s).' . PHP_EOL;
