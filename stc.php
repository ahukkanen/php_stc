<?php
/**
 * Usage:
 * 1. Copy this script to a folder below which you want to
 *    convert all files with a ".php" extension from short 
 *    tags to full PHP tags
 * 2. Navigate to that folder and run:
 *    php -d short_open_tag=On stc.php
 * 3. After finished converting the files, remove this
 *    script.
 *
 * @license MIT
 * @author Antti Hukkanen <antti.hukkanen@mainiotech.fi>
 */

function println($ln) {
	echo $ln . PHP_EOL;
}

function convert_dir($dir, $echo = false) {
	$converted_files = array();
	if ($handle = opendir($dir)) {
		while (false !== ($entry = readdir($handle))) {
			if ($entry != '.' && $entry != '..') {
				$full = $dir . '/' . $entry;
				if (is_dir($full)) {
					$converted_files = array_merge($converted_files, convert_dir($full, $echo));
				} else {
					if (end(explode('.', $full)) == 'php') {
						if (convert_file($full)) {
							$converted_files[] = $full;
							if ($echo) {
								println("Converted: " . $full);
							}
						}
					}
				}
			}
		}
		closedir($handle);
	}
	return $converted_files;
}

function add_space(&$content) {
	// With long tags it's not possible to have
	// tags starting like <?phpecho "ok", so we 
	// need a space with the <?php tag.
	if (!preg_match('/^[\s\n]/', $content)) {
		$content = ' ' . $content;
	}
}

function convert_file($file) {
	$changed = false;
	$cont = file_get_contents($file);
	
	$newcont = "";
	$phpstart = false;
	foreach (token_get_all($cont) as $token) {
		if(is_array($token)) {
			@list($index, $code, $line) = $token;
			if ($index === T_OPEN_TAG) {
				if (strpos($code, '<?php') !== 0) {
					$changed = true;
					$after = substr($code, 2);
					$newcont .= '<?php' . $after;
					$phpstart = true;
				} else {
					if ($phpstart) {
						add_space($code);
						$phpstart = false;
					}
					$newcont .= $code;
				}
			} else if ($index === T_OPEN_TAG_WITH_ECHO) {
				$newcont .= '<?php echo';
				if (strpos($code, ' ') !== 3) {
					$newcont .= ' ';
				}
				$changed = true;
			} else {
				if ($phpstart) {
					add_space($code);
					$phpstart = false;
				}
				$newcont .= $code;
			}
		} else {
			if ($phpstart) {
				add_space($token);
				$phpstart = false;
			}
			$newcont .= $token;
		}
	}
	unset($cont);
	
	$ret = false;
	if ($changed) {
		file_put_contents($file, $newcont);
		$ret = true;
	}
	unset($newcont);
	return $ret;
}

ob_implicit_flush(true);

set_time_limit(0);
convert_dir(dirname(__FILE__), true);