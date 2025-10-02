<?php
//IMathAS:  Core randomizers and display macros
//(c) 2006 David Lippman

require_once __DIR__ . '/../includes/Rand.php';
$GLOBALS['RND'] = new Rand();

// expose native PHP functions
array_push(
    $GLOBALS['allowedmacros'],
    'exp',
    'nthlog',
    'sinn',
    'cosn',
    'tann',
    'secn',
    'cscn',
    'cotn',
    'is_array',
    'count',
    'in_array',
    'array_flip',
    'array_reverse',
    'root',
    'is_numeric',
    'is_nan',
    'sign',
    'sgn',
    'dechex',
    'hexdec',
    'print_r',
    'fmod',
    'safepow',
    'strtoupper',
    'strtolower',
    'ucfirst',
    'lcfirst',
    'sprintf',
    'htmlentities',
    'substr',
    'substr_count',
    'str_replace',
    'explode',
    'array_unique',
    'rawurlencode',
    'array_values',
    'array_keys',
    'isset',
    'atan2',
    'preg_match',
    'preg_match_all',
    'preg_replace',
    'intval',
    'floatval',
    'uniqid'
);

require_once __DIR__ . '/macros/randomizers.php';
require_once __DIR__ . '/macros/array.php';
require_once __DIR__ . '/macros/format.php';
require_once __DIR__ . '/macros/math.php';
require_once __DIR__ . '/macros/conditional.php';
require_once __DIR__ . '/macros/feedback.php';
require_once __DIR__ . '/macros/graph.php';
require_once __DIR__ . '/macros/parsers.php';
require_once __DIR__ . '/macros/helpers.php';
require_once __DIR__ . '/macros/html.php';
require_once __DIR__ . '/macros/string.php';
require_once __DIR__ . '/macros/table.php';

function tryWrapEvalTodo($todo, $func = '') {
    return 'try { ' . $todo . '} catch(Throwable $t) {
        if ($GLOBALS[\'myrights\'] > 10 && !empty($GLOBALS[\'inQuestionTesting\'])) {
            echo "Parse error in ' . $func . ': ".$t->getMessage().". ";
        }
    }';
}
