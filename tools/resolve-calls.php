<?php
/**
 * Resolve every function call in a plugin tree against what actually exists.
 *
 * Usage: php tools/resolve-calls.php <plugin-dir> [--quiet]
 *
 * Why this exists. PHPCS checks style and known-bad patterns; it does not check
 * that the code can run. A call to a function that is defined nowhere is a fatal
 * the first time that line executes, and on an activation path it is a plugin
 * that cannot be activated. Unit tests do not catch it either when the harness
 * stubs the missing name, which is how one got into this repository: the
 * rotation matcher called adz_get_all_ad_ids(), nothing defined it, the tests
 * stubbed it, and the tests passed.
 *
 * What it reports:
 *
 *   1. Calls resolving to nothing in the tree and nothing in PHP core. Most are
 *      supplied by WordPress, which this process cannot see, so this is a list
 *      to read rather than a verdict. It is short enough to read.
 *   2. Calls to the tree's own functions with too few or too many arguments.
 *   3. File-scope functions defined more than once, which is fatal on the
 *      second definition. Class methods are tracked per class and excluded.
 *
 * Exit status is 1 when (2) or (3) found anything, so it can gate a commit.
 *
 * @package adz-world-tools
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$root  = isset( $argv[1] ) ? rtrim( $argv[1], '/' ) : '';
$quiet = in_array( '--quiet', $argv, true );

if ( '' === $root || ! is_dir( $root ) ) {
	fwrite( STDERR, "Usage: php tools/resolve-calls.php <plugin-dir> [--quiet]\n" );
	exit( 2 );
}

/**
 * Every PHP file under a directory, sorted.
 *
 * @param string $dir Directory.
 * @return string[]
 */
function adzt_php_files( $dir ) {
	$files = array();
	$walk  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );

	foreach ( $walk as $file ) {
		if ( $file->isFile() && 'php' === strtolower( $file->getExtension() ) ) {
			$files[] = $file->getPathname();
		}
	}

	sort( $files );

	return $files;
}

/**
 * Walk a parameter list and count what it requires and accepts.
 *
 * @param array $tokens Token stream.
 * @param int   $start  Index at or before the opening parenthesis.
 * @return array{required:int,total:int,end:int}
 */
function adzt_params( $tokens, $start ) {
	$n = count( $tokens );
	$i = $start;

	while ( $i < $n && '(' !== $tokens[ $i ] ) {
		++$i;
	}

	$depth    = 0;
	$required = 0;
	$total    = 0;
	$has_def  = false;
	$seen_var = false;
	$variadic = false;

	for ( ; $i < $n; $i++ ) {
		$t = $tokens[ $i ];

		if ( '(' === $t ) {
			++$depth;
			continue;
		}

		if ( ')' === $t ) {
			--$depth;

			if ( 0 === $depth ) {
				if ( $seen_var ) {
					++$total;

					if ( ! $has_def ) {
						++$required;
					}
				}
				break;
			}
			continue;
		}

		if ( 1 !== $depth ) {
			continue;
		}

		if ( is_array( $t ) && T_ELLIPSIS === $t[0] ) {
			$variadic = true;
		}

		if ( is_array( $t ) && T_VARIABLE === $t[0] ) {
			$seen_var = true;
		}

		if ( '=' === $t ) {
			$has_def = true;
		}

		if ( ',' === $t ) {
			++$total;

			if ( ! $has_def ) {
				++$required;
			}

			$has_def  = false;
			$seen_var = false;
		}
	}

	return array(
		'required' => $required,
		'total'    => $variadic ? PHP_INT_MAX : $total,
		'end'      => $i,
	);
}

/**
 * Count the top-level arguments of a call.
 *
 * @param array $tokens Token stream.
 * @param int   $paren  Index of the opening parenthesis.
 * @return int
 */
function adzt_argc( $tokens, $paren ) {
	$n     = count( $tokens );
	$depth = 0;
	$argc  = 0;
	$seen  = false;

	for ( $i = $paren; $i < $n; $i++ ) {
		$t = $tokens[ $i ];

		if ( '(' === $t || '[' === $t ) {
			++$depth;
			continue;
		}

		if ( ')' === $t || ']' === $t ) {
			--$depth;

			if ( 0 === $depth ) {
				if ( $seen ) {
					++$argc;
				}
				break;
			}
			continue;
		}

		if ( 1 !== $depth ) {
			continue;
		}

		if ( ',' === $t ) {
			++$argc;
			$seen = false;
			continue;
		}

		if ( ! ( is_array( $t ) && in_array( $t[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) ) {
			$seen = true;
		}
	}

	return $argc;
}

$functions = array(); // Name => definition, file scope only.
$methods   = array(); // "Class::method" => definition.
$calls     = array(); // Name => list of call sites.

foreach ( adzt_php_files( $root ) as $path ) {
	$tokens = token_get_all( (string) file_get_contents( $path ) );
	$n      = count( $tokens );
	$rel    = ltrim( substr( $path, strlen( $root ) ), '/' );

	$class       = '';
	$class_depth = -1;
	$depth       = 0;

	for ( $i = 0; $i < $n; $i++ ) {
		$t = $tokens[ $i ];

		/*
		 * Track brace depth so class scope can be closed again.
		 *
		 * String interpolation is the trap here. "{$obj->prop}" opens with
		 * T_CURLY_OPEN and "${name}" with T_DOLLAR_OPEN_CURLY_BRACES, both
		 * array tokens, but each closes with a plain '}' string token. Counting
		 * only the plain braces drives the depth negative, class scope is lost,
		 * and every method in the file is then reported as a file-scope
		 * function -- which read as a duplicate-definition fatal in four
		 * different plugin trees before this was fixed.
		 */
		if ( is_array( $t ) && in_array( $t[0], array( T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES ), true ) ) {
			++$depth;
			continue;
		}

		if ( '{' === $t ) {
			++$depth;
			continue;
		}

		if ( '}' === $t ) {
			--$depth;

			if ( '' !== $class && $depth <= $class_depth ) {
				$class       = '';
				$class_depth = -1;
			}
			continue;
		}

		if ( ! is_array( $t ) ) {
			continue;
		}

		// Entering a class, interface or trait.
		if ( in_array( $t[0], array( T_CLASS, T_INTERFACE, T_TRAIT ), true ) ) {
			$j = $i + 1;

			while ( $j < $n && is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) {
				++$j;
			}

			if ( is_array( $tokens[ $j ] ) && T_STRING === $tokens[ $j ][0] ) {
				$class       = $tokens[ $j ][1];
				$class_depth = $depth;
			}
			continue;
		}

		// A function or method definition.
		if ( T_FUNCTION === $t[0] ) {
			$j = $i + 1;

			while ( $j < $n && ( ( is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) || '&' === $tokens[ $j ] ) ) {
				++$j;
			}

			if ( ! is_array( $tokens[ $j ] ) || T_STRING !== $tokens[ $j ][0] ) {
				continue; // Closure.
			}

			$name   = $tokens[ $j ][1];
			$params = adzt_params( $tokens, $j + 1 );
			$entry  = array(
				'required' => $params['required'],
				'total'    => $params['total'],
				'where'    => $rel . ':' . $t[2],
				'dupes'    => array(),
			);

			if ( '' !== $class ) {
				$methods[ $class . '::' . $name ] = $entry;
				continue;
			}

			$key = strtolower( $name );

			if ( isset( $functions[ $key ] ) ) {
				$functions[ $key ]['dupes'][] = $rel . ':' . $t[2];
			} else {
				$functions[ $key ] = $entry;
			}
			continue;
		}

		// A call site.
		if ( T_STRING === $t[0] ) {
			$j = $i + 1;

			while ( $j < $n && is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) {
				++$j;
			}

			if ( ! isset( $tokens[ $j ] ) || '(' !== $tokens[ $j ] ) {
				continue;
			}

			$p = $i - 1;

			while ( $p >= 0 && is_array( $tokens[ $p ] ) && T_WHITESPACE === $tokens[ $p ][0] ) {
				--$p;
			}

			$skip = array( T_FUNCTION, T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NEW );

			if ( defined( 'T_NULLSAFE_OBJECT_OPERATOR' ) ) {
				$skip[] = T_NULLSAFE_OBJECT_OPERATOR;
			}

			if ( isset( $tokens[ $p ] ) && is_array( $tokens[ $p ] ) && in_array( $tokens[ $p ][0], $skip, true ) ) {
				continue;
			}

			$calls[ strtolower( $t[1] ) ][] = array(
				'where' => $rel . ':' . $t[2],
				'argc'  => adzt_argc( $tokens, $j ),
			);
		}
	}
}

$internal   = array_flip( get_defined_functions()['internal'] );
$constructs = array_flip(
	array(
		'array',
		'die',
		'echo',
		'empty',
		'eval',
		'exit',
		'fn',
		'include',
		'include_once',
		'isset',
		'list',
		'match',
		'parent',
		'print',
		'require',
		'require_once',
		'self',
		'static',
		'unset',
	)
);

$unresolved = array();

foreach ( $calls as $name => $sites ) {
	if ( isset( $functions[ $name ] ) || isset( $internal[ $name ] ) || isset( $constructs[ $name ] ) ) {
		continue;
	}

	// A method of one of this tree's own classes, called without an object in
	// the token stream (for example inside the class itself).
	foreach ( $methods as $key => $unused ) {
		if ( 0 === strcasecmp( substr( $key, strpos( $key, '::' ) + 2 ), $name ) ) {
			continue 2;
		}
	}

	$unresolved[ $name ] = $sites;
}

ksort( $unresolved );

$arity = array();

foreach ( $calls as $name => $sites ) {
	if ( ! isset( $functions[ $name ] ) ) {
		continue;
	}

	$def = $functions[ $name ];

	foreach ( $sites as $site ) {
		if ( $site['argc'] < $def['required'] || $site['argc'] > $def['total'] ) {
			$arity[] = array( $name, $site, $def );
		}
	}
}

$dupes = array();

foreach ( $functions as $name => $def ) {
	if ( ! empty( $def['dupes'] ) ) {
		$dupes[ $name ] = $def;
	}
}

if ( ! $quiet ) {
	echo "=== Unresolved calls (WordPress supplies most; read, do not assume) ===\n\n";

	foreach ( $unresolved as $name => $sites ) {
		printf( "  %-44s %2d call(s)  %s\n", $name . '()', count( $sites ), $sites[0]['where'] );
	}

	if ( empty( $unresolved ) ) {
		echo "  none\n";
	}
}

echo "\n=== Arity mismatches against this tree's own functions ===\n\n";

foreach ( $arity as $row ) {
	list( $name, $site, $def ) = $row;
	printf(
		"  %s called with %d at %s; declares %d required / %s accepted at %s\n",
		$name . '()',
		$site['argc'],
		$site['where'],
		$def['required'],
		PHP_INT_MAX === $def['total'] ? 'variadic' : $def['total'],
		$def['where']
	);
}

if ( empty( $arity ) ) {
	echo "  none\n";
}

echo "\n=== File-scope functions defined twice (fatal on the second) ===\n\n";

foreach ( $dupes as $name => $def ) {
	printf( "  %s first at %s, again at %s\n", $name . '()', $def['where'], implode( ', ', $def['dupes'] ) );
}

if ( empty( $dupes ) ) {
	echo "  none\n";
}

printf(
	"\n%d file-scope functions, %d methods, %d call targets, %d unresolved, %d arity, %d duplicate\n",
	count( $functions ),
	count( $methods ),
	count( $calls ),
	count( $unresolved ),
	count( $arity ),
	count( $dupes )
);

exit( ( empty( $arity ) && empty( $dupes ) ) ? 0 : 1 );
