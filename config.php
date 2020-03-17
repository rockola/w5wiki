<?php

/* Site configuration
 *
 * Change these values to suit your installation */
define("W5_SITE", "W5Wiki");
define("W5_HOME", "/w5wiki/");

/* Software configuration
 *
 * These values should not be changed unless you're forking W5Wiki */
define("W5_SOFTWARE", "W5Wiki");
define("W5_SOFTWARE_URL", "https://github.com/rockola/w5wiki/");
define("W5_VERSION", "0.1");
define("W5_VERSION_DATE", "2020-03-17");

/* Internal configuration
 *
 * Touch these and something will break! */
define("W5_CONTENT", "content/");
define("W5_TAGS", array(
    "SITE" => W5_SITE,
    "HOME" => W5_HOME,
    "SOFTWARE" => W5_SOFTWARE,
    "SOFTWARE_URL" => W5_SOFTWARE_URL,
    "VERSION" => W5_VERSION,
    "VERSION_DATE" => W5_VERSION_DATE,
                        ));

?>
